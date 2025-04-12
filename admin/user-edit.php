<?php
// Włącz buforowanie outputu, aby uniknąć błędów z nagłówkami
ob_start();

// Include header
include_once 'includes/header.php';

// Only admin can edit users
if (!is_admin()) {
    $_SESSION['alert'] = [
        'type' => 'danger',
        'message' => 'Nie masz uprawnień do edycji użytkowników.'
    ];
    header('Location: index.php');
    exit;
}

// Initialize variables
$user = [
    'id' => '',
    'username' => '',
    'email' => '',
    'role' => 'user',
    'active' => 1,
    'created_at' => '',
    'last_login' => ''
];

$errors = [];

// Check if we're editing an existing user
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Użytkownik nie został znaleziony.'
        ];
        header('Location: users.php');
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $user['username'] = isset($_POST['username']) ? trim($_POST['username']) : '';
    $user['email'] = isset($_POST['email']) ? trim($_POST['email']) : '';
    $user['role'] = isset($_POST['role']) ? trim($_POST['role']) : 'user';
    $user['active'] = isset($_POST['active']) ? 1 : 0;
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    // Validate required fields
    if (empty($user['username'])) {
        $errors[] = 'Nazwa użytkownika jest wymagana.';
    }

    if (empty($user['email'])) {
        $errors[] = 'Adres e-mail jest wymagany.';
    } else if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Podaj prawidłowy adres e-mail.';
    }

    // Check if username is already taken (when adding new user or changing username)
    if (empty($user['id']) || $user['username'] !== get_user_by_id($user['id'])['username']) {
        $check_username = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $check_username->bind_param('s', $user['username']);
        $check_username->execute();
        $username_result = $check_username->get_result();

        if ($username_result->num_rows > 0) {
            $errors[] = 'Ta nazwa użytkownika jest już zajęta.';
        }
    }

    // Check if email is already taken (when adding new user or changing email)
    if (empty($user['id']) || $user['email'] !== get_user_by_id($user['id'])['email']) {
        $check_email = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param('s', $user['email']);
        $check_email->execute();
        $email_result = $check_email->get_result();

        if ($email_result->num_rows > 0) {
            $errors[] = 'Ten adres e-mail jest już przypisany do konta.';
        }
    }

    // Validate password for new user or if changing password
    if (empty($user['id']) || !empty($password)) {
        if (empty($password)) {
            $errors[] = 'Hasło jest wymagane.';
        } else if (strlen($password) < 8) {
            $errors[] = 'Hasło musi mieć co najmniej 8 znaków.';
        } else if ($password !== $confirm_password) {
            $errors[] = 'Hasła nie są zgodne.';
        }
    }

    // If no errors, save user
    if (empty($errors)) {
        if (!empty($user['id'])) {
            // Update existing user
            if (!empty($password)) {
                // With password change
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET username = ?, email = ?, role = ?, active = ?, password = ? WHERE id = ?");
                $stmt->bind_param('sssisi', $user['username'], $user['email'], $user['role'], $user['active'], $hashed_password, $user['id']);
            } else {
                // Without password change
                $stmt = $mysqli->prepare("UPDATE users SET username = ?, email = ?, role = ?, active = ? WHERE id = ?");
                $stmt->bind_param('sssii', $user['username'], $user['email'], $user['role'], $user['active'], $user['id']);
            }

            if ($stmt->execute()) {
                // Log activity
                log_activity($_SESSION['user_id'], 'update', 'user', $user['id']);

                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => 'Użytkownik został zaktualizowany pomyślnie.'
                ];

                // Poprawka do przekierowania
                ob_end_clean(); // Wyczyść bufory outputu
                session_write_close(); // Zapisz sesję
                header('Location: users.php');
                exit;
            } else {
                $errors[] = 'Błąd podczas aktualizacji użytkownika: ' . $mysqli->error;
            }
        } else {
            // Add new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param('ssss', $user['username'], $user['email'], $hashed_password, $user['role']);

            if ($stmt->execute()) {
                $new_user_id = $mysqli->insert_id;

                // Log activity
                log_activity($_SESSION['user_id'], 'create', 'user', $new_user_id);

                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => 'Nowy użytkownik został dodany pomyślnie.'
                ];

                // Poprawka do przekierowania
                ob_end_clean(); // Wyczyść bufory outputu
                session_write_close(); // Zapisz sesję
                header('Location: users.php');
                exit;
            } else {
                $errors[] = 'Błąd podczas dodawania użytkownika: ' . $mysqli->error;
            }
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

<form action="user-edit.php<?php echo isset($user['id']) ? '?id=' . $user['id'] : ''; ?>" method="post">
    <div class="admin-form-row">
        <div class="admin-form-group">
            <label for="username" class="admin-form-label">Nazwa użytkownika *</label>
            <input type="text" id="username" name="username" class="admin-form-input" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            <div class="admin-form-help">
                Unikalna nazwa, która będzie używana do logowania.
            </div>
        </div>

        <div class="admin-form-group">
            <label for="email" class="admin-form-label">Adres e-mail *</label>
            <input type="email" id="email" name="email" class="admin-form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            <div class="admin-form-help">
                Adres e-mail użytkownika. Używany do powiadomień i odzyskiwania hasła.
            </div>
        </div>

        <div class="admin-form-group">
            <label for="role" class="admin-form-label">Rola</label>
            <select id="role" name="role" class="admin-form-select">
                <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>Użytkownik</option>
                <option value="editor" <?php echo ($user['role'] === 'editor') ? 'selected' : ''; ?>>Redaktor</option>
                <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Administrator</option>
            </select>
            <div class="admin-form-help">
                Rola określa uprawnienia użytkownika w systemie.
            </div>
        </div>

        <div class="admin-form-group" style="display: flex; align-items: center; margin-top: 20px;">
            <input type="checkbox" id="active" name="active" value="1" <?php echo (isset($user['active']) && $user['active'] ? 'checked' : ''); ?>>
            <label for="active" style="margin-left: 10px; margin-bottom: 0;">Konto aktywne</label>
            <div class="admin-form-help" style="margin-left: 30px;">
                Nieaktywne konta nie mogą się logować do systemu.
            </div>
        </div>

        <div class="admin-form-group">
            <label for="password" class="admin-form-label"><?php echo empty($user['id']) ? 'Hasło *' : 'Hasło (pozostaw puste, aby nie zmieniać)'; ?></label>
            <input type="password" id="password" name="password" class="admin-form-input" <?php echo empty($user['id']) ? 'required' : ''; ?>>
            <div class="admin-form-help">
                <?php echo empty($user['id']) ? 'Hasło musi mieć co najmniej 8 znaków.' : 'Wypełnij tylko, jeśli chcesz zmienić hasło. Minimum 8 znaków.'; ?>
            </div>
        </div>

        <div class="admin-form-group">
            <label for="confirm_password" class="admin-form-label"><?php echo empty($user['id']) ? 'Potwierdź hasło *' : 'Potwierdź hasło'; ?></label>
            <input type="password" id="confirm_password" name="confirm_password" class="admin-form-input" <?php echo empty($user['id']) ? 'required' : ''; ?>>
            <div class="admin-form-help">
                Wpisz hasło ponownie, aby potwierdzić.
            </div>
        </div>

        <?php if (!empty($user['id'])): ?>
        <div class="admin-form-group">
            <label class="admin-form-label">Informacje</label>
            <div style="display: flex; gap: 30px; margin-top: 10px;">
                <div>
                    <strong>Data rejestracji:</strong> <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                </div>
                <div>
                    <strong>Ostatnie logowanie:</strong> <?php echo !empty($user['last_login']) ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nigdy'; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Form Actions -->
    <div class="admin-form-actions">
        <a href="users.php" class="btn btn-secondary">Anuluj</a>
        <button type="submit" class="btn btn-primary">
            <?php echo isset($user['id']) ? 'Zapisz zmiany' : 'Dodaj użytkownika'; ?>
        </button>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const usernameField = document.getElementById('username');
        const emailField = document.getElementById('email');
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm_password');

        form.addEventListener('submit', function(e) {
            let hasErrors = false;
            const errorMessages = [];

            // Check required fields
            if (!usernameField.value.trim()) {
                errorMessages.push('Nazwa użytkownika jest wymagana.');
                hasErrors = true;
            }

            if (!emailField.value.trim()) {
                errorMessages.push('Adres e-mail jest wymagany.');
                hasErrors = true;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value.trim())) {
                errorMessages.push('Podaj prawidłowy adres e-mail.');
                hasErrors = true;
            }

            // Password validation for new user or if changing password
            if (<?php echo empty($user['id']) ? 'true' : 'false'; ?> || passwordField.value.trim()) {
                if (<?php echo empty($user['id']) ? 'true' : 'false'; ?> && !passwordField.value.trim()) {
                    errorMessages.push('Hasło jest wymagane.');
                    hasErrors = true;
                } else if (passwordField.value.trim().length < 8) {
                    errorMessages.push('Hasło musi mieć co najmniej 8 znaków.');
                    hasErrors = true;
                } else if (passwordField.value !== confirmPasswordField.value) {
                    errorMessages.push('Hasła nie są zgodne.');
                    hasErrors = true;
                }
            }

            if (hasErrors) {
                e.preventDefault();

                // Display error messages
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger';

                const ul = document.createElement('ul');
                ul.style.marginBottom = '0';

                errorMessages.forEach(msg => {
                    const li = document.createElement('li');
                    li.textContent = msg;
                    ul.appendChild(li);
                });

                alertDiv.appendChild(ul);

                const closeBtn = document.createElement('button');
                closeBtn.className = 'alert-close';
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                alertDiv.appendChild(closeBtn);

                // Insert alert at the top of the form
                form.insertAdjacentElement('beforebegin', alertDiv);

                // Scroll to error message
                alertDiv.scrollIntoView({ behavior: 'smooth' });

                // Initialize close button
                closeBtn.addEventListener('click', function() {
                    alertDiv.remove();
                });
            }
        });
    });
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
