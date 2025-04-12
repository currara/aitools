<?php
/**
 * Szablon wyboru wielu kategorii dla narzędzia
 */

// Sprawdź, która kategoria jest główną, aby jej nie wyświetlać w dodatkowych kategoriach
$main_category_id = $tool['category_id'] ?? 0;
?>

<div class="admin-form-group">
    <label class="admin-form-label">Kategorie</label>

    <!-- Ukryty input przechowujący wybrane kategorie w formacie JSON -->
    <input type="hidden" id="selected_categories" name="selected_categories" value="<?php echo htmlspecialchars(json_encode($selected_categories ?? [])); ?>">

    <div id="categories-container">
        <?php foreach ($categories as $category): ?>
            <?php if ($category['id'] != $main_category_id): ?>
                <div class="category-item" data-category-id="<?php echo $category['id']; ?>">
                    <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($category['subcategories'])): ?>
                <?php foreach ($category['subcategories'] as $subcategory): ?>
                    <?php if ($subcategory['id'] != $main_category_id): ?>
                        <div class="category-item subcategory" data-category-id="<?php echo $subcategory['id']; ?>" data-parent-id="<?php echo $category['id']; ?>">
                            <span class="category-name"><?php echo htmlspecialchars($subcategory['name']); ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="admin-form-help">
        Wybierz jedną lub więcej kategorii, do których należy to narzędzie.
        Jednocześnie można przypisać narzędzie zarówno do kategorii głównej, jak i podkategorii.
    </div>
</div>

<!-- Podłączamy skrypty i style -->
<link rel="stylesheet" href="tool-category-multi.css">
<script src="tool-category-multi.js"></script>

<script>
// Skrypt do aktualizacji listy dostępnych kategorii po zmianie głównej kategorii
document.addEventListener('DOMContentLoaded', function() {
    const mainCategorySelect = document.getElementById('category_id');
    if (mainCategorySelect) {
        mainCategorySelect.addEventListener('change', function() {
            const mainCategoryId = this.value;

            // Odśwież widok kategorii w kontenerze
            const categoryItems = document.querySelectorAll('#categories-container .category-item');
            categoryItems.forEach(item => {
                const categoryId = item.getAttribute('data-category-id');

                if (categoryId === mainCategoryId) {
                    item.style.display = 'none';
                } else {
                    item.style.display = '';
                }
            });
        });

        // Wywołaj zmianę, aby ukryć aktualnie wybraną kategorię
        mainCategorySelect.dispatchEvent(new Event('change'));
    }
});
</script>
