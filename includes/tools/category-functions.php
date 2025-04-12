<?php
/**
 * Funkcje pomocnicze do obsługi kategorii
 */

/**
 * Funkcja zwracająca całkowitą liczbę narzędzi w kategorii (wraz z podkategoriami)
 *
 * @param int $category_id ID kategorii
 * @return int Liczba narzędzi w kategorii i jej podkategoriach
 */
function get_category_total_count($category_id) {
    // Wykorzystaj istniejącą funkcję count_tools_in_category
    return count_tools_in_category($category_id, []);
}
