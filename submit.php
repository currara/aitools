<?php
// Include header
include_once 'includes/header.php';

// Initialize variables
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = isset($_POST['name']) ? clean_input($_POST['name']) : '';
    $description = isset($_POST['description']) ? clean_input($_POST['description']) : '';
    $website_url = isset($_POST['website_url']) ? clean_input($_POST['website_url']) : '';
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

    // Validate form data
    if (empty($name)) {
        $errors[] = 'Tool name is required';
    }

    if (empty($description)) {
        $errors[] = 'Tool description is required';
    }

    if (empty($website_url)) {
        $errors[] = 'Tool website URL is required';
    } elseif (!filter_var($website_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Invalid website URL';
    }

    if ($category_id <= 0) {
        $errors[] = 'Please select a category';
    }

    // Process logo upload if provided
    $logo = '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['logo']['type'], $allowed_types)) {
            $errors[] = 'Logo must be a JPEG, PNG, or GIF image';
        } elseif ($_FILES['logo']['size'] > $max_size) {
            $errors[] = 'Logo size must be less than 2MB';
        } else {
            // Generate a unique filename
            $logo = time() . '_' . basename($_FILES['logo']['name']);
            $upload_dir = 'images/logos/';

            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Move uploaded file
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo)) {
                $errors[] = 'Failed to upload logo';
                $logo = '';
            }
        }
    }

    // If no errors, insert the tool
    if (empty($errors)) {
        // Create slug from name
        $slug = create_slug($name);

        // Check if slug already exists
        $sql = "SELECT id FROM tools WHERE slug = '$slug'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            // Append a random number to the slug to make it unique
            $slug .= '-' . rand(100, 999);
        }

        // Insert the tool
        $sql = "INSERT INTO tools (name, slug, description, logo, website_url, category_id, new_launch) VALUES (
            '" . $conn->real_escape_string($name) . "',
            '" . $conn->real_escape_string($slug) . "',
            '" . $conn->real_escape_string($description) . "',
            '" . $conn->real_escape_string($logo) . "',
            '" . $conn->real_escape_string($website_url) . "',
            " . (int)$category_id . ",
            TRUE
        )";

        if ($conn->query($sql)) {
            // Update category count
            $sql = "UPDATE categories SET count = count + 1 WHERE id = " . (int)$category_id;
            $conn->query($sql);

            $success = true;
        } else {
            $errors[] = 'Failed to submit tool: ' . $conn->error;
        }
    }
}

// Get all categories
$categories = get_categories();
?>

<!-- Submit Tool Section -->
<section class="section section-submit">
    <div class="container">
        <div class="submit-container">
            <div class="submit-header">
                <h1>Submit Your AI Tool</h1>
                <p>Share your AI tool with our community and get discovered by thousands of users looking for innovative AI solutions.</p>
            </div>

            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h3>Thank You!</h3>
                    <p>Your tool has been submitted successfully. Our team will review it and publish it soon.</p>
                    <div class="action-buttons">
                        <a href="index.php" class="btn btn-primary">Back to Home</a>
                        <a href="submit.php" class="btn btn-secondary">Submit Another Tool</a>
                    </div>
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

                <form action="submit.php" method="post" enctype="multipart/form-data" class="submit-form">
                    <div class="form-group">
                        <label for="name">Tool Name *</label>
                        <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" rows="5" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <div class="form-help">Provide a detailed description of what your tool does and its key features.</div>
                    </div>

                    <div class="form-group">
                        <label for="website_url">Website URL *</label>
                        <input type="url" id="website_url" name="website_url" required value="<?php echo isset($_POST['website_url']) ? htmlspecialchars($_POST['website_url']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="logo">Logo</label>
                        <input type="file" id="logo" name="logo" accept="image/jpeg, image/png, image/gif">
                        <div class="form-help">Upload a logo for your tool (JPEG, PNG, or GIF, max 2MB). Recommended size: 200x200 pixels.</div>
                    </div>

                    <div class="form-group form-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>.</label>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Submit Tool</button>
                    </div>
                </form>

                <div class="submit-info">
                    <h3>Submission Guidelines</h3>
                    <ul>
                        <li>Your tool must be AI-related and fully functional.</li>
                        <li>Provide accurate and detailed information about your tool.</li>
                        <li>Make sure your website URL is valid and accessible.</li>
                        <li>Submissions may take up to 48 hours to be reviewed and published.</li>
                        <li>We reserve the right to reject submissions that do not meet our standards.</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
    /* Additional CSS for Submit Page */
    .section-submit {
        padding: 60px 0;
        background-color: var(--white);
    }

    .submit-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .submit-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .submit-header h1 {
        font-size: 2.2rem;
        margin-bottom: 15px;
        color: var(--gray-900);
    }

    .submit-header p {
        font-size: 1.1rem;
        color: var(--gray-600);
        max-width: 600px;
        margin: 0 auto;
    }

    .submit-form {
        background-color: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 30px;
        margin-bottom: 30px;
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
    .form-group input[type="url"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border-radius: var(--border-radius);
        border: 1px solid var(--gray-300);
        font-size: 16px;
        transition: var(--transition);
    }

    .form-group input[type="text"]:focus,
    .form-group input[type="url"]:focus,
    .form-group select:focus,
    .form-group textarea:focus {
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

    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    .submit-info {
        background-color: var(--gray-100);
        border-radius: var(--border-radius);
        padding: 20px;
    }

    .submit-info h3 {
        font-size: 1.2rem;
        margin-bottom: 15px;
        color: var(--gray-800);
    }

    .submit-info ul {
        padding-left: 20px;
    }

    .submit-info ul li {
        margin-bottom: 10px;
        color: var(--gray-700);
    }

    @media screen and (max-width: 768px) {
        .section-submit {
            padding: 40px 0;
        }

        .submit-header h1 {
            font-size: 1.8rem;
        }

        .submit-form {
            padding: 20px;
        }

        .action-buttons {
            flex-direction: column;
        }

        .action-buttons .btn {
            width: 100%;
            margin-bottom: 10px;
        }
    }
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
