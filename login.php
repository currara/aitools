<?php
// Include configuration
require_once 'includes/config.php';

// Dodajemy brakujące tłumaczenia
if (!isset($lang['login_title'])) $lang['login_title'] = 'Logowanie';
if (!isset($lang['login_username'])) $lang['login_username'] = 'Nazwa użytkownika';
if (!isset($lang['login_password'])) $lang['login_password'] = 'Hasło';
if (!isset($lang['login_remember_me'])) $lang['login_remember_me'] = 'Zapamiętaj mnie';
if (!isset($lang['login_button'])) $lang['login_button'] = 'Zaloguj się';
if (!isset($lang['login_no_account'])) $lang['login_no_account'] = 'Nie masz jeszcze konta?';
if (!isset($lang['login_register_link'])) $lang['login_register_link'] = 'Zarejestruj się';
if (!isset($lang['login_error_username_required'])) $lang['login_error_username_required'] = 'Nazwa użytkownika jest wymagana';
if (!isset($lang['login_error_password_required'])) $lang['login_error_password_required'] = 'Hasło jest wymagane';
if (!isset($lang['login_error_invalid_credentials'])) $lang['login_error_invalid_credentials'] = 'Nieprawidłowa nazwa użytkownika lub hasło';

// Initialize variables
$errors = [];
$username = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// Check if user is already logged in
if (is_logged_in()) {
    if ($redirect === 'admin' && (is_admin() || is_editor())) {
        header('Location: admin/index.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;

    // Validate fields
    if (empty($username)) {
        $errors[] = __('login_error_username_required');
    }

    if (empty($password)) {
        $errors[] = __('login_error_password_required');
    }

    // If no errors, attempt login
    if (empty($errors)) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Update last login time
                $update_sql = "UPDATE users SET last_login = NOW() WHERE id = " . $user['id'];
                $mysqli->query($update_sql);

                // Log activity
                log_activity($user['id'], 'login');

                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expires = time() + 60 * 60 * 24 * 30; // 30 days

                    // Store token in database (you would need to create a tokens table for this)
                    // $sql = "INSERT INTO tokens (user_id, token, expires) VALUES ({$user['id']}, '$token', FROM_UNIXTIME($expires))";
                    // $mysqli->query($sql);

                    // Set cookie
                    setcookie('remember_token', $token, $expires, '/');
                }

                // Redirect to appropriate page
                if ($redirect === 'admin' && (is_admin() || is_editor())) {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $errors[] = __('login_error_invalid_credentials');
            }
        } else {
            $errors[] = __('login_error_invalid_credentials');
        }
    }
}

// Include header (po całym przetwarzaniu formularza)
include_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card login-card">
                <div class="card-body">
                    <h2 class="text-center"><?php echo __('login_title'); ?></h2>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="login.php<?php echo $redirect ? '?redirect=' . urlencode($redirect) : ''; ?>" method="post">
                        <div class="form-group mb-3">
                            <label for="username"><?php echo __('login_username'); ?></label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="password"><?php echo __('login_password'); ?></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember"><?php echo __('login_remember_me'); ?></label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary"><?php echo __('login_button'); ?></button>
                        </div>
                    </form>

                    <div class="mt-3 text-center">
                        <p><?php echo __('login_no_account'); ?> <a href="register.php"><?php echo __('login_register_link'); ?></a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
