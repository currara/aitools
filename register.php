<?php
// Include header
include_once 'includes/header.php';

// Initialize variables
$errors = [];
$success = false;
$username = '';
$email = '';

// Check if user is already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = isset($_POST['username']) ? clean_input($_POST['username']) : '';
    $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validate form data
    if (empty($username)) {
        $errors[] = 'Username is required';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = 'Username must be between 3 and 20 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    // Check if username already exists
    $sql = "SELECT id FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $errors[] = 'Username already exists';
    }

    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $errors[] = 'Email already exists';
    }

    // If no errors, register user
    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $sql = "INSERT INTO users (username, email, password) VALUES (
            '" . $conn->real_escape_string($username) . "',
            '" . $conn->real_escape_string($email) . "',
            '" . $hashed_password . "'
        )";

        if ($conn->query($sql)) {
            $success = true;
        } else {
            $errors[] = 'Failed to register user: ' . $conn->error;
        }
    }
}
?>

<!-- Register Section -->
<section class="section section-register">
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h1>Create an Account</h1>
                <p>Join our community to discover and share AI tools with thousands of users.</p>
            </div>

            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h3>Registration Successful!</h3>
                    <p>Your account has been created successfully. You can now login to your account.</p>
                    <a href="login.php" class="btn btn-primary">Login Now</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="error-container">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="post" class="register-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        <div class="form-help">Username can only contain letters, numbers, and underscores.</div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <div class="form-help">Password must be at least 8 characters long.</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="form-group form-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>.</label>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Register</button>
                    </div>

                    <div class="form-links">
                        <span>Already have an account?</span>
                        <a href="login.php">Login</a>
                    </div>
                </form>

                <div class="register-or">
                    <span>OR</span>
                </div>

                <div class="social-register">
                    <button class="btn btn-google">
                        <i class="fab fa-google"></i> Register with Google
                    </button>
                    <button class="btn btn-facebook">
                        <i class="fab fa-facebook-f"></i> Register with Facebook
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    /* Additional CSS for Register Page */
    .section-register {
        padding: 60px 0;
        background-color: var(--white);
    }

    .register-container {
        max-width: 500px;
        margin: 0 auto;
    }

    .register-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .register-header h1 {
        font-size: 2rem;
        margin-bottom: 10px;
        color: var(--gray-900);
    }

    .register-header p {
        font-size: 1rem;
        color: var(--gray-600);
    }

    .register-form {
        background-color: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 30px;
        margin-bottom: 20px;
        border: 1px solid var(--gray-200);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--gray-800);
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        border-radius: var(--border-radius);
        border: 1px solid var(--gray-300);
        font-size: 16px;
        transition: var(--transition);
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="email"]:focus,
    .form-group input[type="password"]:focus {
        border-color: var(--primary-color);
        outline: none;
        box-shadow: 0 0 0 3px rgba(118, 89, 242, 0.2);
    }

    .form-help {
        margin-top: 5px;
        font-size: 14px;
        color: var(--gray-600);
    }

    .form-checkbox {
        display: flex;
        align-items: center;
    }

    .form-checkbox input {
        margin-right: 10px;
    }

    .form-checkbox label {
        margin-bottom: 0;
        font-weight: normal;
        font-size: 14px;
    }

    .btn-block {
        display: block;
        width: 100%;
        padding: 15px;
        font-size: 16px;
    }

    .form-links {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
        color: var(--gray-700);
    }

    .form-links a {
        color: var(--primary-color);
        font-weight: 500;
        margin-left: 5px;
    }

    .error-container {
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: var(--border-radius);
        margin-bottom: 20px;
    }

    .error-container ul {
        margin: 0;
        padding-left: 20px;
    }

    .success-message {
        text-align: center;
        padding: 40px 20px;
        background-color: #d4edda;
        color: #155724;
        border-radius: var(--border-radius);
        margin-bottom: 30px;
    }

    .success-message i {
        font-size: 60px;
        margin-bottom: 20px;
    }

    .success-message h3 {
        font-size: 1.8rem;
        margin-bottom: 15px;
    }

    .success-message p {
        font-size: 1.1rem;
        margin-bottom: 30px;
    }

    .register-or {
        text-align: center;
        position: relative;
        margin: 20px 0;
    }

    .register-or::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background-color: var(--gray-300);
        z-index: 1;
    }

    .register-or span {
        display: inline-block;
        padding: 0 10px;
        background-color: var(--white);
        position: relative;
        z-index: 2;
        color: var(--gray-600);
        font-size: 14px;
    }

    .social-register {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .btn-google, .btn-facebook {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px;
        border-radius: var(--border-radius);
        font-weight: 500;
        border: 1px solid var(--gray-300);
        background-color: var(--white);
        color: var(--gray-700);
        transition: var(--transition);
    }

    .btn-google:hover, .btn-facebook:hover {
        background-color: var(--gray-100);
    }

    .btn-google i, .btn-facebook i {
        margin-right: 10px;
    }

    .btn-google i {
        color: #DB4437;
    }

    .btn-facebook i {
        color: #4267B2;
    }

    @media screen and (max-width: 576px) {
        .section-register {
            padding: 40px 0;
        }

        .register-header h1 {
            font-size: 1.8rem;
        }

        .register-form {
            padding: 20px;
        }
    }
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
