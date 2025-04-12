<?php
// Włącz buforowanie outputu, aby uniknąć błędów z nagłówkami
ob_start();

// Include header
include_once 'includes/header.php';

// Initialize variables
$user = get_user($_SESSION['user_id']);
$errors = [];
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $current_password = isset($_POST['current_password']) ? trim($_POST['current_password']) : '';
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    // Validate required fields
    if (empty($username)) {
        $errors[] = 'Nazwa użytkownika jest wymagana.';
    }

    if (empty($email)) {
        $errors[] = 'Adres e-mail jest wymagany.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Podaj prawidłowy adres e-mail.';
    }

    // Check if username is already taken by another user
    if ($username !== $user['username']) {
        $check_username = $mysqli->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $check_username->bind_param('si', $username, $user['id']);
        $check_username->execute();
        $username_result = $check_username->get_result();

        if ($username_result->num_rows > 0) {
            $errors[] = 'Ta nazwa użytkownika jest już zajęta.';
        }
    }

    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $check_email = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param('si', $email, $user['id']);
        $check_email->execute();
        $email_result = $check_email->get_result();

        if ($email_result->num_rows > 0) {
            $errors[] = 'Ten adres e-mail jest już przypisany do konta.';
        }
    }

    // If changing password, validate current password and the new one
    if (!empty($new_password) || !empty($confirm_password)) {
        // Verify current password
        if (empty($current_password)) {
            $errors[] = 'Aktualne hasło jest wymagane do zmiany hasła.';
        } else {
            // Get user's current hashed password
            $get_password = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
            $get_password->bind_param('i', $user['id']);
            $get_password->execute();
            $password_result = $get_password->get_result();
            $hashed_password = $password_result->fetch_assoc()['password'];

            if (!password_verify($current_password, $hashed_password)) {
                $errors[] = 'Aktualne hasło jest nieprawidłowe.';
            }
        }

        // Validate new password
        if (strlen($new_password) < 8) {
            $errors[] = 'Nowe hasło musi mieć co najmniej 8 znaków.';
        } else if ($new_password !== $confirm_password) {
            $errors[] = 'Nowe hasła nie są zgodne.';
        }
    }

    // If no errors, update user profile
    if (empty($errors)) {
        if (!empty($new_password)) {
            // With password change
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
            $stmt->bind_param('sssi', $username, $email, $hashed_password, $user['id']);
        } else {
            // Without password change
            $stmt = $mysqli->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->bind_param('ssi', $username, $email, $user['id']);
        }

        if ($stmt->execute()) {
            // Log activity
            log_activity($_SESSION['user_id'], 'update', 'profile', $user['id']);

            $success_message = 'Profil został zaktualizowany pomyślnie.';

            // Update session with new username if changed
            if ($username !== $user['username']) {
                $_SESSION['username'] = $username;
            }

            // Refresh user data
            $user = get_user($_SESSION['user_id']);
        } else {
            $errors[] = 'Błąd podczas aktualizacji profilu: ' . $mysqli->error;
        }
    }
}
?>

<!-- Page Content -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="margin-bottom: 0;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="alert-close"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="alert-close"><i class="fas fa-times"></i></button>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div class="admin-card-header">
        <h2>Mój profil</h2>
    </div>
    <div class="admin-card-body">
        <form method="post" action="profile.php">
            <div class="admin-form-row">
                <div class="admin-form-group">
                    <label for="username" class="admin-form-label">Nazwa użytkownika *</label>
                    <input type="text" id="username" name="username" class="admin-form-input" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>

                <div class="admin-form-group">
                    <label for="email" class="admin-form-label">Adres e-mail *</label>
                    <input type="email" id="email" name="email" class="admin-form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="admin-form-group">
                    <label for="role" class="admin-form-label">Rola</label>
                    <input type="text" id="role" class="admin-form-input" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" readonly>
                    <div class="admin-form-help">
                        Twoja rola w systemie. Zmiana roli wymaga interwencji administratora.
                    </div>
                </div>

                <div class="admin-form-divider">
                    <h3>Zmiana hasła</h3>
                    <p>Wypełnij poniższe pola tylko, jeśli chcesz zmienić swoje hasło.</p>
                </div>

                <div class="admin-form-group">
                    <label for="current_password" class="admin-form-label">Aktualne hasło</label>
                    <input type="password" id="current_password" name="current_password" class="admin-form-input">
                    <div class="admin-form-help">
                        Wymagane do zmiany hasła.
                    </div>
                </div>

                <div class="admin-form-group">
                    <label for="new_password" class="admin-form-label">Nowe hasło</label>
                    <input type="password" id="new_password" name="new_password" class="admin-form-input">
                    <div class="admin-form-help">
                        Minimum 8 znaków.
                    </div>
                </div>

                <div class="admin-form-group">
                    <label for="confirm_password" class="admin-form-label">Potwierdź nowe hasło</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="admin-form-input">
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label">Informacje o koncie</label>
                    <div style="display: flex; gap: 30px; margin-top: 10px;">
                        <div>
                            <strong>Data rejestracji:</strong> <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                        </div>
                        <div>
                            <strong>Ostatnie logowanie:</strong> <?php echo !empty($user['last_login']) ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nigdy'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="admin-form-actions">
                <a href="index.php" class="btn btn-secondary">Anuluj</a>
                <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
            </div>
        </form>
    </div>
</div>

<style>
.admin-form-divider {
    margin: 30px 0 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

.admin-form-divider h3 {
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.admin-form-divider p {
    color: #6c757d;
    font-size: 0.9rem;
}
</style>

<?php
// Flush output buffer before including footer
ob_end_flush();

// Include footer
include_once 'includes/footer.php';
?>
