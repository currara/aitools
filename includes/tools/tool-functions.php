<?php

/**
 * Funkcje pomocnicze do obsługi kart narzędzi
 */

/**
 * Sprawdza czy obraz narzędzia jest zrzutem ekranu czy ikoną/favicon
 *
 * @param array $tool Dane narzędzia
 * @return string 'screenshot' lub 'favicon'
 */
function get_image_type($tool)
{
    // Sprawdź czy mamy bezpośrednio podany typ obrazu
    if (!empty($tool['image_type'])) {
        return $tool['image_type'];
    }

    // Sprawdź na podstawie ścieżki pliku
    if (!empty($tool['logo']) && strpos($tool['logo'], 'screenshot') !== false) {
        return 'screenshot';
    } elseif (!empty($tool['logo']) && strpos($tool['logo'], 'favicon') !== false) {
        return 'favicon';
    }

    // Domyślnie traktujemy jako favicon/logo
    return 'favicon';
}

/**
 * Zwraca ścieżkę do obrazu narzędzia z uwzględnieniem typu i preferowanego rozmiaru
 *
 * @param array $tool Dane narzędzia
 * @param bool $use_thumbnail Czy używać miniatury
 * @param int $size Preferowany rozmiar miniatury (150, 300, 600)
 * @return string Ścieżka do obrazu
 */
function get_image_path($tool, $use_thumbnail = false, $size = 150) {
    $image_type = get_image_type($tool);

    // Domyślna ścieżka do obrazu
    if ($image_type == 'screenshot' && !empty($tool['screenshot'])) {
        $image_path = $tool['screenshot'];
    } elseif (!empty($tool['logo'])) {
        $image_path = $tool['logo'];
    } else {
        return 'default-tool-logo.png';
    }

    // Jeśli nie mamy używać miniatury lub to nie jest screenshot, zwróć standardową ścieżkę
    if (!$use_thumbnail || $image_type != 'screenshot') {
        return $image_path;
    }

    // Sprawdź, czy istnieje miniatura
    $base_name = pathinfo($image_path, PATHINFO_FILENAME);
    $extension = pathinfo($image_path, PATHINFO_EXTENSION);

    // Jeśli nie ma określonego rozszerzenia, używamy jpg dla miniatur
    if (empty($extension)) {
        $extension = 'jpg';
    }

    // Ustal właściwą wielkość miniatury (150, 300 lub 600)
    $valid_sizes = [150, 300, 600];
    $selected_size = in_array($size, $valid_sizes) ? $size : 150;

    // Ścieżka do miniatury
    $thumbnail_path = 'screenshots/thumbnails/' . $base_name . '-' . $selected_size . '.' . $extension;

    // Sprawdź, czy miniatura istnieje (w systemie plików)
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/' . $thumbnail_path)) {
        return $thumbnail_path;
    }

    // Jeśli miniatura nie istnieje, spróbuj inną wielkość
    foreach ($valid_sizes as $alternative_size) {
        if ($alternative_size == $selected_size) continue;

        $alternative_thumbnail = 'screenshots/thumbnails/' . $base_name . '-' . $alternative_size . '.' . $extension;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/' . $alternative_thumbnail)) {
            return $alternative_thumbnail;
        }
    }

    // Jeśli żadna miniatura nie istnieje, użyj oryginalnego obrazu
    return $image_path;
}

/**
 * Renderuje obraz narzędzia (logo lub zrzut) w zależności od jego typu
 *
 * @param array $tool Dane narzędzia
 * @param bool $list_view Czy wyświetlać w widoku listy
 * @return string HTML z obrazem w odpowiednim formacie
 */
function render_tool_image($tool, $list_view = false)
{
    $image_alt = htmlspecialchars($tool['name']) . ' ' . __('logo');
    $image_type = get_image_type($tool);

    $html = '';

    if ($image_type == 'screenshot') {
        if (!empty($tool['screenshot'])) {
            // Użyj funkcji get_image_path z odpowiednimi parametrami
            $image_path = get_image_path($tool, $list_view, 150);

            $html .= '<div class="tool-' . ($list_view ? 'logo' : 'screenshot') . '">';
            $html .= '<img src="/images/' . $image_path . '" alt="' . $image_alt . '" loading="lazy">';
            $html .= '</div>';
        } else {
            // Brak obrazu, użyj domyślnego
            $html .= '<div class="tool-logo">';
            $html .= '<img src="/images/default-tool-logo.png" alt="' . $image_alt . '" loading="lazy">';
            $html .= '</div>';
        }
    } else {
        // Dla favicon/logo
        $image_path = !empty($tool['logo']) ? $tool['logo'] : 'default-tool-logo.png';
        $html .= '<div class="tool-logo">';
        $html .= '<img src="/images/' . $image_path . '" alt="' . $image_alt . '" loading="lazy">';
        $html .= '</div>';
    }

    return $html;
}

/**
 * Renderuje kartę narzędzia w siatce lub widoku listy
 *
 * @param array $tool Dane narzędzia
 * @param bool $grid_view Czy wyświetlać w siatce (true) czy liście (false)
 * @return string HTML karty narzędzia
 */
function render_tool_card($tool, $grid_view = true)
{
    global $current_language, $default_language;

    $image_type = get_image_type($tool);
    $card_class = 'tool-card';
    $card_class .= $image_type == 'screenshot' ? ' has-screenshot' : ' has-favicon';
    $card_class .= $grid_view ? '' : ' list-view';
    $list_view = !$grid_view;

    $html = '<div class="' . $card_class . '" data-category="' . ($tool['category_slug'] ?? '') . '">';

    // Plakietki wyróżnienia/nowości
    if (isset($tool['is_featured']) && $tool['is_featured'] || isset($tool['featured']) && $tool['featured']) {
        $html .= '<div class="featured-badge"><i class="fas fa-star"></i> ' . __('featured') . '</div>';
    } elseif (isset($tool['is_new']) && $tool['is_new'] || isset($tool['new_launch']) && $tool['new_launch']) {
        $html .= '<div class="new-badge"><i class="fas fa-bolt"></i> ' . __('new') . '</div>';
    }

    // Dla zrzutów ekranu w widoku siatki umieszczamy je na górze karty, poza inner
    if ($image_type == 'screenshot' && $grid_view) {
        $html .= render_tool_image($tool, false);
    }

    // W widoku listy, jeśli to screenshot, dodajemy logo z miniaturą przed inner
    if ($image_type == 'screenshot' && $list_view) {
        $html .= '<div class="tool-logo">';
        // Używamy funkcji get_image_path z parametrami dla miniatury
        $image_path = get_image_path($tool, true, 150);
        $image_alt = htmlspecialchars($tool['name']) . ' ' . __('logo');
        $html .= '<img src="/images/' . $image_path . '" alt="' . $image_alt . '" loading="lazy">';
        $html .= '</div>';
    }

    $html .= '<div class="tool-card-inner">';

    // Dla favicon w widoku siatki lub listy umieszczamy je wewnątrz inner
    if ($image_type == 'favicon') {
        $html .= render_tool_image($tool, $list_view);
    }

    $html .= '<div class="tool-info">';
    $html .= '<h3>' . htmlspecialchars($tool['name']) . '</h3>';
    $html .= '<div class="tool-meta">';

    // Kategoria
    $html .= '<div class="tool-category">';
    $html .= '<a href="' . (($current_language === $default_language) ? '/category/' : '/' . $current_language . '/category/') . ($tool['category_slug'] ?? '') . '" class="category-tag">';
    $html .= $tool['category_name'] ?? '';
    $html .= '</a>';
    $html .= '</div>';

    // Ocena
    $html .= '<div class="tool-rating">';
    $html .= '<div class="stars" data-rating="' . $tool['rating'] . '"></div>';
    $html .= '<span>' . number_format($tool['rating'], 1) . '</span>';
    $html .= '</div>';
    $html .= '</div>';

    // Wyświetlamy opis z zachowaniem HTML
    $description = !empty($tool['description']) ? $tool['description'] : '';
    // Skróć opis do określonej długości
    $description = substr_preserve_html($description, 150);
    $html .= '<div class="tool-description">' . $description . '</div>';
    $html .= '</div>';

    // Przyciski akcji
    $html .= '<div class="tool-actions">';
    $html .= '<a href="' . (($current_language === $default_language) ? '/tool/' : '/' . $current_language . '/tool/') . $tool['slug'] . '" class="btn-view">' . __('view_details') . '</a>';
    $html .= '<a href="' . $tool['website_url'] . '" target="_blank" rel="noopener noreferrer" class="btn-visit" title="' . __('visit_website') . '">';
    $html .= '<i class="fas fa-external-link-alt"></i>';
    $html .= '</a>';
    $html .= '</div>';

    $html .= '</div>'; // Zamknięcie .tool-card-inner
    $html .= '</div>'; // Zamknięcie .tool-card

    return $html;
}

/**
 * Funkcja skracająca tekst HTML z zachowaniem struktury tagów
 *
 * @param string $html Tekst HTML do skrócenia
 * @param int $length Docelowa długość tekstu
 * @param string $suffix Suffix dodawany po skróceniu
 * @return string Skrócony tekst HTML
 */
function substr_preserve_html($html, $length, $suffix = '...')
{
    // Jeśli tekst jest krótszy niż żądana długość, zwróć go bez zmian
    if (mb_strlen(strip_tags($html)) <= $length) {
        return $html;
    }

    // Usuń zbędne białe znaki
    $html = preg_replace('/\s+/', ' ', $html);

    $html_no_tags = strip_tags($html);
    if (mb_strlen($html_no_tags) <= $length) {
        return $html;
    }

    // Znajdź wszystkie tagi
    preg_match_all('/<[^>]+>([^<]*)/', $html, $matches, PREG_OFFSET_CAPTURE);

    $result = '';
    $total_length = 0;
    $open_tags = [];

    foreach ($matches[0] as $index => $match) {
        $tag = $match[0];
        $tag_start = $match[1];

        // Znajdź nazwę tagu
        preg_match('/<\/?([a-z]+)/', $tag, $tag_name_match);
        $tag_name = isset($tag_name_match[1]) ? strtolower($tag_name_match[1]) : '';

        // Sprawdź czy to jest tag otwierający czy zamykający
        $is_closing = preg_match('/<\//', $tag);

        if (!$is_closing && $tag_name) {
            // Dodaj tag do listy otwartych
            array_push($open_tags, $tag_name);
        } elseif ($is_closing && $tag_name) {
            // Usuń ostatni pasujący tag z listy
            $open_tags = array_filter($open_tags, function ($val) use ($tag_name) {
                return $val != $tag_name;
            });
        }

        // Dodaj tag do wyniku
        $result .= $tag;

        // Sprawdź tekst pomiędzy tagami
        if (isset($matches[1][$index][0])) {
            $text = $matches[1][$index][0];
            $text_length = mb_strlen($text);

            if ($total_length + $text_length > $length) {
                // Dodaj tylko część tekstu, aby nie przekroczyć docelowej długości
                $result .= mb_substr($text, 0, $length - $total_length);
                break;
            } else {
                // Dodaj cały tekst
                $result .= $text;
                $total_length += $text_length;
            }
        }
    }

    // Dodaj suffix
    $result .= $suffix;

    // Zamknij wszystkie otwarte tagi
    foreach (array_reverse($open_tags) as $tag) {
        $result .= '</' . $tag . '>';
    }

    return $result;
}
