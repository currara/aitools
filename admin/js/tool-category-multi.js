/**
 * Tool Category Multi-select
 * Skrypt do obsługi wyboru wielu kategorii dla narzędzi
 */

document.addEventListener('DOMContentLoaded', function() {
    // Znajdź kontener kategorii
    const categoriesContainer = document.getElementById('categories-container');
    if (!categoriesContainer) return;

    // Znajdź input z wybranymi kategoriami
    const selectedCategoriesInput = document.getElementById('selected_categories');
    if (!selectedCategoriesInput) return;

    // Znajdź input do wyszukiwania
    const categorySearchInput = document.getElementById('category-search');

    // Lista wybranych kategorii
    let selectedCategories = [];

    // Jeśli są już wybrane kategorie, załaduj je
    if (selectedCategoriesInput.value) {
        try {
            selectedCategories = JSON.parse(selectedCategoriesInput.value);
        } catch (e) {
            console.error('Błąd parsowania wybranych kategorii:', e);
            selectedCategories = [];
        }
    }

    // Kontener dla wyświetlania wybranych kategorii
    const selectedCategoriesContainer = document.createElement('div');
    selectedCategoriesContainer.className = 'selected-categories';
    categoriesContainer.parentNode.insertBefore(selectedCategoriesContainer, categoriesContainer.nextSibling);

    // Funkcja do aktualizacji widoku wybranych kategorii
    function updateSelectedCategories() {
        // Wyczyść kontener
        selectedCategoriesContainer.innerHTML = '';

        // Jeśli nie ma wybranych kategorii, ukryj kontener
        if (selectedCategories.length === 0) {
            selectedCategoriesContainer.style.display = 'none';
            return;
        }

        // Pokaż kontener
        selectedCategoriesContainer.style.display = 'flex';

        // Dodaj tytuł
        const title = document.createElement('div');
        title.className = 'selected-categories-title';
        title.textContent = 'Wybrane kategorie:';
        selectedCategoriesContainer.appendChild(title);

        // Dodaj listę wybranych kategorii
        const list = document.createElement('div');
        list.className = 'selected-categories-list';

        selectedCategories.forEach(category => {
            const checkbox = document.querySelector(`input[type="checkbox"][data-category-id="${category.id}"]`);
            if (checkbox) {
                checkbox.checked = true;

                const item = document.createElement('div');
                item.className = 'selected-category-item';

                const name = document.createElement('span');
                name.textContent = category.name;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'remove-category';
                removeButton.innerHTML = '&times;';
                removeButton.setAttribute('data-category-id', category.id);
                removeButton.addEventListener('click', function() {
                    removeCategory(category.id);
                });

                item.appendChild(name);
                item.appendChild(removeButton);
                list.appendChild(item);
            }
        });

        selectedCategoriesContainer.appendChild(list);

        // Aktualizuj ukryty input
        selectedCategoriesInput.value = JSON.stringify(selectedCategories);
    }

    // Funkcja usuwająca kategorię
    function removeCategory(categoryId) {
        const index = selectedCategories.findIndex(c => c.id === categoryId);
        if (index !== -1) {
            selectedCategories.splice(index, 1);
            updateSelectedCategories();

            // Odznacz checkbox
            const checkbox = document.querySelector(`input[type="checkbox"][data-category-id="${categoryId}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
        }
    }

    // Dodaj checkboxy dla wszystkich kategorii
    const categoryItems = document.querySelectorAll('#categories-container .category-item');
    categoryItems.forEach(item => {
        const categoryId = item.getAttribute('data-category-id');
        const categoryName = item.querySelector('.category-name').textContent;

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'category-checkbox';
        checkbox.setAttribute('data-category-id', categoryId);

        // Sprawdź czy kategoria jest już wybrana
        if (selectedCategories.some(c => c.id === categoryId)) {
            checkbox.checked = true;
        }

        // Obsługuj zmianę stanu checkboxa
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // Dodaj kategorię do wybranych
                selectedCategories.push({
                    id: categoryId,
                    name: categoryName.trim()
                });
            } else {
                // Usuń kategorię z wybranych
                removeCategory(categoryId);
            }

            updateSelectedCategories();
        });

        // Dodaj checkbox do elementu kategorii
        item.insertBefore(checkbox, item.firstChild);
    });

    // Obsługa wyszukiwania kategorii
    if (categorySearchInput) {
        categorySearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();

            // Jeśli pusty term, pokaż wszystko
            if (searchTerm === '') {
                categoryItems.forEach(item => {
                    item.style.display = '';
                });
                return;
            }

            // Filtruj kategorie
            categoryItems.forEach(item => {
                const categoryName = item.querySelector('.category-name').textContent.toLowerCase();
                if (categoryName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Inicjalizacja widoku
    updateSelectedCategories();
});
