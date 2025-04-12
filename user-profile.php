<?php
// Strona profilu użytkownika
require_once 'includes/config.php';

// Sprawdź, czy użytkownik jest zalogowany
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Pobierz dane użytkownika
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Obsługa formularza aktualizacji profilu
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = clean_input($_POST['email']);
    $display_name = clean_input($_POST['display_name']);
    $bio = clean_input($_POST['bio']);

    // Walidacja
    if (empty($email)) {
        $error_message = __('profile_error_email_required');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = __('profile_error_invalid_email');
    } else {
        // Aktualizuj dane użytkownika
        $sql = "UPDATE users SET email = ?, display_name = ?, bio = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssi', $email, $display_name, $bio, $user_id);

        if ($stmt->execute()) {
            $success_message = __('profile_update_success');

            // Odśwież dane użytkownika
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        } else {
            $error_message = __('profile_update_error');
        }
    }
}

// Zmiana hasła
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Walidacja
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = __('profile_error_password_required');
    } elseif ($new_password !== $confirm_password) {
        $error_message = __('profile_error_password_mismatch');
    } elseif (strlen($new_password) < 8) {
        $error_message = __('profile_error_password_too_short');
    } elseif (!password_verify($current_password, $user['password'])) {
        $error_message = __('profile_error_current_password_incorrect');
    } else {
        // Zmień hasło
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $hashed_password, $user_id);

        if ($stmt->execute()) {
            $success_message = __('profile_password_change_success');
        } else {
            $error_message = __('profile_password_change_error');
        }
    }
}

// Dodajemy brakujące tłumaczenia
if (!isset($lang['profile_title'])) $lang['profile_title'] = 'Profil użytkownika';
if (!isset($lang['profile_tools_submitted'])) $lang['profile_tools_submitted'] = 'Dodane narzędzia';
if (!isset($lang['profile_favorites'])) $lang['profile_favorites'] = 'Ulubione';
if (!isset($lang['profile_joined'])) $lang['profile_joined'] = 'Dołączył';
if (!isset($lang['profile_edit_profile'])) $lang['profile_edit_profile'] = 'Edytuj profil';
if (!isset($lang['profile_change_password'])) $lang['profile_change_password'] = 'Zmień hasło';
if (!isset($lang['profile_your_submissions'])) $lang['profile_your_submissions'] = 'Twoje zgłoszenia';
if (!isset($lang['profile_username'])) $lang['profile_username'] = 'Nazwa użytkownika';
if (!isset($lang['profile_email'])) $lang['profile_email'] = 'Email';
if (!isset($lang['profile_display_name'])) $lang['profile_display_name'] = 'Wyświetlana nazwa';
if (!isset($lang['profile_bio'])) $lang['profile_bio'] = 'O sobie';
if (!isset($lang['profile_update'])) $lang['profile_update'] = 'Aktualizuj profil';
if (!isset($lang['profile_current_password'])) $lang['profile_current_password'] = 'Aktualne hasło';
if (!isset($lang['profile_new_password'])) $lang['profile_new_password'] = 'Nowe hasło';
if (!isset($lang['profile_confirm_password'])) $lang['profile_confirm_password'] = 'Potwierdź nowe hasło';
if (!isset($lang['profile_change_password_button'])) $lang['profile_change_password_button'] = 'Zmień hasło';
if (!isset($lang['profile_tool_name'])) $lang['profile_tool_name'] = 'Nazwa narzędzia';
if (!isset($lang['profile_tool_status'])) $lang['profile_tool_status'] = 'Status';
if (!isset($lang['profile_tool_date'])) $lang['profile_tool_date'] = 'Data';
if (!isset($lang['profile_tool_actions'])) $lang['profile_tool_actions'] = 'Akcje';
if (!isset($lang['profile_tool_view'])) $lang['profile_tool_view'] = 'Zobacz';
if (!isset($lang['profile_no_submissions'])) $lang['profile_no_submissions'] = 'Nie masz jeszcze dodanych narzędzi.';
if (!isset($lang['profile_username_note'])) $lang['profile_username_note'] = 'Nazwa użytkownika nie może być zmieniona';
if (!isset($lang['profile_update_success'])) $lang['profile_update_success'] = 'Profil został zaktualizowany';
if (!isset($lang['profile_update_error'])) $lang['profile_update_error'] = 'Błąd podczas aktualizacji profilu';
if (!isset($lang['profile_password_change_success'])) $lang['profile_password_change_success'] = 'Hasło zostało zmienione';
if (!isset($lang['profile_password_change_error'])) $lang['profile_password_change_error'] = 'Błąd podczas zmiany hasła';
if (!isset($lang['profile_error_email_required'])) $lang['profile_error_email_required'] = 'Email jest wymagany';
if (!isset($lang['profile_error_invalid_email'])) $lang['profile_error_invalid_email'] = 'Podany email jest nieprawidłowy';
if (!isset($lang['profile_error_password_required'])) $lang['profile_error_password_required'] = 'Wszystkie pola hasła są wymagane';
if (!isset($lang['profile_error_password_mismatch'])) $lang['profile_error_password_mismatch'] = 'Nowe hasła nie są identyczne';
if (!isset($lang['profile_error_password_too_short'])) $lang['profile_error_password_too_short'] = 'Hasło musi mieć co najmniej 8 znaków';
if (!isset($lang['profile_error_current_password_incorrect'])) $lang['profile_error_current_password_incorrect'] = 'Aktualne hasło jest nieprawidłowe';

// Dołącz nagłówek
include_once 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class="profile-title"><?php echo __('profile_title'); ?></h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card profile-sidebar">
                <div class="card-body">
                    <div class="profile-avatar">
                        <img src="<?php echo !empty($user['avatar']) ? $user['avatar'] : 'images/default-avatar.png'; ?>" alt="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    <h3 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h3>
                    <p class="profile-role"><?php echo ucfirst($user['role']); ?></p>
                    <p class="profile-joined"><?php echo __('profile_joined'); ?>: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-label"><?php echo __('profile_tools_submitted'); ?></span>
                            <span class="stat-value">
                                <?php
                                // Sprawdzamy czy istnieje kolumna submitted_by w tabeli tools
                                $check_column = $mysqli->query("SHOW COLUMNS FROM tools LIKE 'submitted_by'");
                                if ($check_column->num_rows > 0) {
                                    $stmt = $mysqli->prepare("SELECT COUNT(*) FROM tools WHERE submitted_by = ?");
                                    $stmt->bind_param('i', $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    echo $result->fetch_row()[0];
                                } else {
                                    // Jeśli kolumna nie istnieje, pokazujemy 0
                                    echo "0";
                                }
                                ?>
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label"><?php echo __('profile_favorites'); ?></span>
                            <span class="stat-value">
                                <?php
                                $stmt = $mysqli->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
                                $stmt->bind_param('i', $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                echo $result->fetch_row()[0];
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#profile"><?php echo __('profile_edit_profile'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#password"><?php echo __('profile_change_password'); ?></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#submissions"><?php echo __('profile_your_submissions'); ?></a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Edycja profilu -->
                        <div class="tab-pane fade show active" id="profile">
                            <form action="user-profile.php" method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label"><?php echo __('profile_username'); ?></label>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <small class="form-text text-muted"><?php echo __('profile_username_note'); ?></small>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label"><?php echo __('profile_email'); ?></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="display_name" class="form-label"><?php echo __('profile_display_name'); ?></label>
                                    <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo isset($user['display_name']) ? htmlspecialchars($user['display_name']) : ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="bio" class="form-label"><?php echo __('profile_bio'); ?></label>
                                    <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo isset($user['bio']) ? htmlspecialchars($user['bio']) : ''; ?></textarea>
                                </div>

                                <button type="submit" name="update_profile" class="btn btn-primary"><?php echo __('profile_update'); ?></button>
                            </form>
                        </div>

                        <!-- Zmiana hasła -->
                        <div class="tab-pane fade" id="password">
                            <form action="user-profile.php" method="post">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label"><?php echo __('profile_current_password'); ?></label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label"><?php echo __('profile_new_password'); ?></label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label"><?php echo __('profile_confirm_password'); ?></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>

                                <button type="submit" name="change_password" class="btn btn-primary"><?php echo __('profile_change_password_button'); ?></button>
                            </form>
                        </div>

                        <!-- Przesłane narzędzia -->
                        <div class="tab-pane fade" id="submissions">
                            <?php
                            // Sprawdzamy czy istnieje kolumna submitted_by w tabeli tools
                            $check_column = $mysqli->query("SHOW COLUMNS FROM tools LIKE 'submitted_by'");
                            if ($check_column->num_rows > 0) {
                                $stmt = $mysqli->prepare("SELECT * FROM tools WHERE submitted_by = ? ORDER BY created_at DESC");
                                $stmt->bind_param('i', $user_id);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-hover">';
                                    echo '<thead><tr><th>' . __('profile_tool_name') . '</th><th>' . __('profile_tool_status') . '</th><th>' . __('profile_tool_date') . '</th><th>' . __('profile_tool_actions') . '</th></tr></thead>';
                                    echo '<tbody>';

                                    while ($tool = $result->fetch_assoc()) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($tool['name']) . '</td>';
                                        echo '<td>' . ucfirst($tool['status']) . '</td>';
                                        echo '<td>' . date('Y-m-d', strtotime($tool['created_at'])) . '</td>';
                                        echo '<td><a href="tool.php?id=' . $tool['id'] . '" class="btn btn-sm btn-primary">' . __('profile_tool_view') . '</a></td>';
                                        echo '</tr>';
                                    }

                                    echo '</tbody>';
                                    echo '</table>';
                                    echo '</div>';
                                } else {
                                    echo '<p class="text-muted">' . __('profile_no_submissions') . '</p>';
                                }
                            } else {
                                // Jeśli kolumna nie istnieje, pokazujemy informację
                                echo '<p class="text-muted">' . __('profile_no_submissions') . '</p>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-title {
    margin-bottom: 30px;
}
.profile-sidebar {
    margin-bottom: 30px;
}
.profile-avatar {
    text-align: center;
    margin-bottom: 15px;
}
.profile-avatar img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
}
.profile-username {
    text-align: center;
    margin-bottom: 5px;
}
.profile-role {
    text-align: center;
    color: #6c757d;
    margin-bottom: 10px;
}
.profile-joined {
    text-align: center;
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 20px;
}
.profile-stats {
    display: flex;
    justify-content: space-around;
    padding-top: 15px;
    border-top: 1px solid #eee;
}
.stat-item {
    text-align: center;
}
.stat-label {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
}
.stat-value {
    display: block;
    font-size: 1.2rem;
    font-weight: 600;
}
</style>

<script>
// Aktywacja zakładek Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    // Znajdź wszystkie linki zakładek
    var tabLinks = document.querySelectorAll('#profileTabs .nav-link');

    // Dodaj obsługę kliknięć
    tabLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();

            // Usuń aktywną klasę ze wszystkich linków
            tabLinks.forEach(function(tabLink) {
                tabLink.classList.remove('active');
            });

            // Dodaj aktywną klasę do klikniętego linku
            this.classList.add('active');

            // Ukryj wszystkie panele zakładek
            var tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(function(pane) {
                pane.classList.remove('show', 'active');
            });

            // Pokaż odpowiedni panel
            var targetId = this.getAttribute('href').substring(1);
            var targetPane = document.getElementById(targetId);
            targetPane.classList.add('show', 'active');
        });
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>
