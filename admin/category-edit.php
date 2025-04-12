<?php
// Włącz buforowanie outputu, aby uniknąć błędów z nagłówkami
ob_start();

// Include header
include_once 'includes/header.php';

// Initialize variables
$category = [
    'id' => '',
    'name' => '',
    'slug' => '',
    'description' => '',
    'icon' => ''
];

$translations = [];
$errors = [];

// Check if we're editing an existing category
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    $category_data = get_category($category_id);

    if ($category_data) {
        $category = $category_data;
        $translations = get_category_translations($category_id);
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Kategoria nie została znaleziona.'
        ];
        header('Location: categories.php');
        exit;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $category['name'] = isset($_POST['name']) ? trim($_POST['name']) : '';
    $category['slug'] = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $category['description'] = isset($_POST['description']) ? trim($_POST['description']) : '';
    $category['icon'] = isset($_POST['icon']) ? trim($_POST['icon']) : '';

    // Validate required fields
    if (empty($category['name'])) {
        $errors[] = 'Nazwa kategorii jest wymagana.';
    }

    // Generate slug if empty
    if (empty($category['slug'])) {
        $category['slug'] = create_slug($category['name']);
    }

    // Process icon upload if any
    if (isset($_FILES['icon_file']) && $_FILES['icon_file']['error'] === 0) {
        $allowed_types = ['image/svg+xml', 'image/png', 'image/jpeg', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (in_array($_FILES['icon_file']['type'], $allowed_types) && $_FILES['icon_file']['size'] <= $max_size) {
            $file_ext = pathinfo($_FILES['icon_file']['name'], PATHINFO_EXTENSION);
            $file_name = create_slug($category['name']) . '-icon.' . $file_ext;
            $target_path = '../images/icons/' . $file_name;

            if (move_uploaded_file($_FILES['icon_file']['tmp_name'], $target_path)) {
                $category['icon'] = $file_name;
            } else {
                $errors[] = 'Nie udało się przesłać ikony.';
            }
        } else {
            $errors[] = 'Nieprawidłowy typ pliku lub rozmiar ikony. Dozwolone formaty: SVG, PNG, JPG, GIF. Maksymalny rozmiar: 2MB.';
        }
    }

    // Get translations
    $trans_data = [];
    foreach ($available_languages as $lang_code => $lang_info) {
        if ($lang_code === $default_language) continue; // Skip default language

        $trans_name = isset($_POST['trans_name_' . $lang_code]) ? trim($_POST['trans_name_' . $lang_code]) : '';
        $trans_description = isset($_POST['trans_description_' . $lang_code]) ? trim($_POST['trans_description_' . $lang_code]) : '';

        if (!empty($trans_name) || !empty($trans_description)) {
            $trans_data[$lang_code] = [
                'name' => $trans_name,
                'description' => $trans_description
            ];
        }
    }

    // If no errors, save category
    if (empty($errors)) {
        $save_data = [
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'],
            'icon' => $category['icon']
        ];

        if (isset($category['id']) && !empty($category['id'])) {
            $save_data['id'] = $category['id'];
        }

        $result = save_category($save_data, $trans_data);

        if ($result['success']) {
            // Log activity
            $action = isset($category['id']) && !empty($category['id']) ? 'update' : 'create';
            log_activity($_SESSION['user_id'], $action, 'category', $result['id']);

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => $result['message']
            ];

            // Upewnij się, że nie było wcześniejszego outputu i wyczyść bufory
            if (!headers_sent()) {
                ob_end_clean(); // Czyści bufor wyjścia
                session_write_close(); // Zapisuje sesję
                header('Location: categories.php');
                exit;
            } else {
                // Jeśli nagłówki już zostały wysłane, użyj JavaScript do przekierowania
                echo '<script>window.location.href = "categories.php";</script>';
                exit;
            }
        } else {
            $errors[] = $result['message'];
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

<form action="category-edit.php<?php echo isset($category['id']) ? '?id=' . $category['id'] : ''; ?>" method="post" enctype="multipart/form-data">
    <!-- Tabs Navigation -->
    <div class="admin-tabs">
        <div class="admin-tab active" data-tab="tab-general">Ogólne</div>
        <div class="admin-tab" data-tab="tab-translations">Tłumaczenia</div>
        <div class="admin-tab" data-tab="tab-icon">Ikona</div>
    </div>

    <!-- Tab Content -->
    <div id="tab-general" class="admin-tab-content active">
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="name" class="admin-form-label">Nazwa kategorii *</label>
                <input type="text" id="name" name="name" class="admin-form-input auto-slug-source" data-slug-target="#slug" value="<?php echo htmlspecialchars($category['name']); ?>" required>
            </div>

            <div class="admin-form-group">
                <label for="slug" class="admin-form-label">Slug</label>
                <input type="text" id="slug" name="slug" class="admin-form-input auto-slug" value="<?php echo htmlspecialchars($category['slug']); ?>">
                <div class="admin-form-help">
                    Identyfikator URL kategorii. Pozostaw puste, aby wygenerować automatycznie z nazwy.
                </div>
            </div>

            <div class="admin-form-group">
                <label for="description" class="admin-form-label">Opis</label>
                <textarea id="description" name="description" class="admin-form-textarea"><?php echo htmlspecialchars($category['description']); ?></textarea>
                <div class="admin-form-help">
                    Krótki opis kategorii wyświetlany na stronie z listą kategorii.
                </div>
            </div>
        </div>
    </div>

    <div id="tab-translations" class="admin-tab-content">
        <!-- Language Tabs -->
        <div class="language-tabs">
            <?php
            $first_lang = true;
            foreach ($available_languages as $lang_code => $lang_info):
                if ($lang_code === $default_language) continue; // Skip default language
            ?>
                <div class="language-tab <?php echo $first_lang ? 'active' : ''; ?>" data-lang="<?php echo $lang_code; ?>">
                    <?php echo $lang_info['native_name']; ?>
                </div>
            <?php
                $first_lang = false;
            endforeach;
            ?>
        </div>

        <!-- Language Content -->
        <?php
        $first_lang = true;
        foreach ($available_languages as $lang_code => $lang_info):
            if ($lang_code === $default_language) continue; // Skip default language

            $trans_name = isset($translations[$lang_code]['name']) ? $translations[$lang_code]['name'] : '';
            $trans_description = isset($translations[$lang_code]['description']) ? $translations[$lang_code]['description'] : '';
        ?>
            <div class="language-content <?php echo $first_lang ? 'active' : ''; ?>" data-lang="<?php echo $lang_code; ?>">
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="trans_name_<?php echo $lang_code; ?>" class="admin-form-label">Nazwa kategorii (<?php echo $lang_info['native_name']; ?>)</label>
                        <input type="text" id="trans_name_<?php echo $lang_code; ?>" name="trans_name_<?php echo $lang_code; ?>" class="admin-form-input" value="<?php echo htmlspecialchars($trans_name); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label for="trans_description_<?php echo $lang_code; ?>" class="admin-form-label">Opis (<?php echo $lang_info['native_name']; ?>)</label>
                        <textarea id="trans_description_<?php echo $lang_code; ?>" name="trans_description_<?php echo $lang_code; ?>" class="admin-form-textarea"><?php echo htmlspecialchars($trans_description); ?></textarea>
                    </div>
                </div>
            </div>
        <?php
            $first_lang = false;
        endforeach;
        ?>
    </div>

    <div id="tab-icon" class="admin-tab-content">
        <div class="admin-form-row">
            <div class="admin-form-group">
                <label for="icon" class="admin-form-label">Nazwa pliku ikony</label>
                <input type="text" id="icon" name="icon" class="admin-form-input" value="<?php echo htmlspecialchars($category['icon'] ?? ''); ?>">
                <div class="admin-form-help">
                    Nazwa pliku ikony znajdującej się w katalogu images/icons/
                </div>
            </div>

            <div class="admin-form-group">
                <label for="icon_file" class="admin-form-label">Prześlij ikonę</label>
                <input type="file" id="icon_file" name="icon_file" class="admin-form-file-input" accept=".svg,.png,.jpg,.jpeg,.gif">
                <div class="admin-form-help">
                    Dozwolone formaty: SVG, PNG, JPG, GIF. Maksymalny rozmiar: 2MB.
                </div>

                <?php if (!empty($category['icon'])): ?>
                    <div style="margin-top: 15px;">
                        <strong>Aktualna ikona:</strong>
                        <div style="margin-top: 10px;">
                            <img src="../images/icons/<?php echo htmlspecialchars($category['icon']); ?>" alt="Current Icon" style="max-width: 100px; max-height: 100px;">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="admin-form-actions">
        <a href="categories.php" class="btn btn-secondary">Anuluj</a>
        <button type="submit" class="btn btn-primary">
            <?php echo isset($category['id']) ? 'Zapisz zmiany' : 'Dodaj kategorię'; ?>
        </button>
    </div>

    <!-- JavaScript validation -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const nameField = document.getElementById('name');
            const slugField = document.getElementById('slug');

            form.addEventListener('submit', function(e) {
                let hasErrors = false;
                const errorMessages = [];

                // Check name
                if (!nameField.value.trim()) {
                    errorMessages.push('Nazwa kategorii jest wymagana.');
                    hasErrors = true;
                }

                // Check if slug will be generated from name
                if (!slugField.value.trim() && !nameField.value.trim()) {
                    errorMessages.push('Slug kategorii nie może być pusty. Wprowadź nazwę kategorii lub podaj slug ręcznie.');
                    hasErrors = true;
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
                    alertDiv.scrollIntoView({
                        behavior: 'smooth'
                    });

                    // Initialize close button
                    closeBtn.addEventListener('click', function() {
                        alertDiv.remove();
                    });
                }
            });
        });
    </script>
</form>

<?php
// Include footer
include_once 'includes/footer.php';
?>
