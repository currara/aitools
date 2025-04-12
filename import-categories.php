<?php
/**
 * Import Categories and Subcategories from Toolify.ai
 * This script will import the exact category structure from Toolify.ai website
 */

// Włącz raportowanie wszystkich błędów
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Załaduj konfigurację
require_once 'includes/config.php';

// Zacznij zbierać output do bufora
ob_start();

echo '<h1>Import Kategorii i Podkategorii z Toolify.ai</h1>';
echo '<p>Ten skrypt zaimportuje kategorie i podkategorie ze strony Toolify.ai</p>';

// Sprawdź czy kolumna parent_id istnieje
echo '<h2>Sprawdzanie struktury kategorii...</h2>';
$sql = "SHOW COLUMNS FROM categories LIKE 'parent_id'";
$result = $conn->query($sql);

if ($result && $result->num_rows == 0) {
    echo '<p style="color: orange;">Dodawanie kolumny parent_id do tabeli categories...</p>';

    $sql = "ALTER TABLE categories ADD COLUMN parent_id INT DEFAULT NULL";
    if ($conn->query($sql)) {
        echo '<p style="color: green;">Kolumna parent_id została dodana.</p>';

        $sql = "ALTER TABLE categories ADD FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL";
        if ($conn->query($sql)) {
            echo '<p style="color: green;">Dodano klucz obcy dla parent_id.</p>';
        } else {
            echo '<p style="color: red;">Błąd podczas dodawania klucza obcego: ' . $conn->error . '</p>';
        }
    } else {
        echo '<p style="color: red;">Błąd podczas dodawania kolumny: ' . $conn->error . '</p>';
    }
}

// Usuwanie istniejących kategorii (opcjonalne)
echo '<h2>Przygotowanie do importu...</h2>';
$delete_existing = isset($_GET['delete_existing']) && $_GET['delete_existing'] == 1;

if ($delete_existing) {
    // Najpierw usuwamy wszystkie narzędzia przypisane do kategorii
    $sql = "UPDATE tools SET category_id = NULL";
    if ($conn->query($sql)) {
        echo '<p style="color: green;">Usunięto powiązania narzędzia-kategorie.</p>';
    } else {
        echo '<p style="color: red;">Błąd podczas usuwania powiązań narzędzia-kategorie: ' . $conn->error . '</p>';
    }

    // Najpierw usuwamy powiązania podkategorii
    $sql = "UPDATE categories SET parent_id = NULL";
    if ($conn->query($sql)) {
        echo '<p style="color: green;">Usunięto powiązania podkategorii.</p>';
    } else {
        echo '<p style="color: red;">Błąd podczas usuwania powiązań podkategorii: ' . $conn->error . '</p>';
    }

    // Teraz możemy usunąć wszystkie kategorie
    $sql = "DELETE FROM categories";
    if ($conn->query($sql)) {
        echo '<p style="color: green;">Usunięto wszystkie kategorie.</p>';
    } else {
        echo '<p style="color: red;">Błąd podczas usuwania kategorii: ' . $conn->error . '</p>';
    }
}

// Lista głównych kategorii
$main_categories = [
    'text-writing' => 'Text & Writing',
    'image' => 'Image',
    'video' => 'Video',
    'code-it' => 'Code & IT',
    'voice' => 'Voice',
    'business' => 'Business',
    'marketing' => 'Marketing',
    'ai-detector' => 'AI Detector',
    'chatbot' => 'Chatbot',
    'design-art' => 'Design & Art',
    'life-assistant' => 'Life Assistant',
    '3d' => '3D',
    'education' => 'Education',
    'prompt' => 'Prompt',
    'productivity' => 'Productivity',
    'other' => 'Other'
];

// Lista podkategorii (podkategoria => kategoria główna)
$subcategories = [
    // Text & Writing subcategories
    'ai-blog-writer' => ['name' => 'AI Blog Writer', 'parent' => 'text-writing'],
    'translate' => ['name' => 'Translate', 'parent' => 'text-writing'],
    'papers' => ['name' => 'Papers', 'parent' => 'text-writing'],
    'handwriting' => ['name' => 'Handwriting', 'parent' => 'text-writing'],
    'copywriting' => ['name' => 'Copywriting', 'parent' => 'text-writing'],
    'captions-subtitle' => ['name' => 'Captions or Subtitle', 'parent' => 'text-writing'],
    'essay-writer' => ['name' => 'Essay Writer', 'parent' => 'text-writing'],
    'letter-writer' => ['name' => 'Letter Writer', 'parent' => 'text-writing'],
    'ai-lyrics-generator' => ['name' => 'AI Lyrics Generator', 'parent' => 'text-writing'],
    'report-writing' => ['name' => 'Report Writing', 'parent' => 'text-writing'],
    'ai-rewriter' => ['name' => 'AI Rewriter', 'parent' => 'text-writing'],
    'ai-script-writing' => ['name' => 'AI Script Writing', 'parent' => 'text-writing'],
    'ai-story-writing' => ['name' => 'AI Story Writing', 'parent' => 'text-writing'],
    'ai-bio-generator' => ['name' => 'AI Bio Generator', 'parent' => 'text-writing'],
    'ai-book-writing' => ['name' => 'AI Book Writing', 'parent' => 'text-writing'],
    'paraphraser' => ['name' => 'Paraphraser', 'parent' => 'text-writing'],
    'ai-poem-poetry-generator' => ['name' => 'AI Poem & Poetry Generator', 'parent' => 'text-writing'],
    'summarizer' => ['name' => 'Summarizer', 'parent' => 'text-writing'],
    'pick-up-lines-generator' => ['name' => 'Pick-up Lines Generator', 'parent' => 'text-writing'],
    'transcription' => ['name' => 'Transcription', 'parent' => 'text-writing'],
    'general-writing' => ['name' => 'General Writing', 'parent' => 'text-writing'],
    'writing-assistants' => ['name' => 'Writing Assistants', 'parent' => 'text-writing'],
    'ai-creative-writing' => ['name' => 'AI Creative Writing', 'parent' => 'text-writing'],
    'transcriber' => ['name' => 'Transcriber', 'parent' => 'text-writing'],
    'ai-content-generator' => ['name' => 'AI Content Generator', 'parent' => 'text-writing'],
    'ai-email-writer' => ['name' => 'AI Email Writer', 'parent' => 'text-writing'],
    'ai-novel' => ['name' => 'Novel', 'parent' => 'text-writing'],
    'ai-quotes-generator' => ['name' => 'Quotes Generator', 'parent' => 'text-writing'],
    'ai-product-description-generator' => ['name' => 'AI Product Description Generator', 'parent' => 'text-writing'],

    // Image subcategories
    'text-to-image' => ['name' => 'Text to Image', 'parent' => 'image'],
    'ai-photo-image-generator' => ['name' => 'AI Photo & Image Generator', 'parent' => 'image'],
    'ai-illustration-generator' => ['name' => 'AI Illustration Generator', 'parent' => 'image'],
    'ai-avatar-generator' => ['name' => 'AI Avatar Generator', 'parent' => 'image'],
    'ai-background-generator' => ['name' => 'AI Background Generator', 'parent' => 'image'],
    'ai-banner-generator' => ['name' => 'AI Banner Generator', 'parent' => 'image'],
    'ai-cover-generator' => ['name' => 'AI Cover Generator', 'parent' => 'image'],
    'ai-emoji-generator' => ['name' => 'AI Emoji Generator', 'parent' => 'image'],
    'ai-gif-generator' => ['name' => 'AI GIF Generator', 'parent' => 'image'],
    'ai-icon-generator' => ['name' => 'AI Icon Generator', 'parent' => 'image'],
    'ai-image-enhancer' => ['name' => 'AI Image Enhancer', 'parent' => 'image'],
    'ai-logo-generator' => ['name' => 'AI Logo Generator', 'parent' => 'image'],
    'photo-image-editor' => ['name' => 'Photo & Image Editor', 'parent' => 'image'],
    'ai-photo-enhancer' => ['name' => 'AI Photo Enhancer', 'parent' => 'image'],
    'ai-photo-restoration' => ['name' => 'AI Photo Restoration', 'parent' => 'image'],
    'ai-photography' => ['name' => 'AI Photography', 'parent' => 'image'],
    'ai-profile-picture-generator' => ['name' => 'AI Profile Picture Generator', 'parent' => 'image'],
    'ai-wallpaper-generator' => ['name' => 'AI Wallpaper Generator', 'parent' => 'image'],
    'ai-background-remover' => ['name' => 'AI Background Remover', 'parent' => 'image'],
    'ai-manga-comic' => ['name' => 'AI Manga & Comic', 'parent' => 'image'],
    'ai-pattern-generator' => ['name' => 'AI Pattern Generator', 'parent' => 'image'],
    'ai-selfie-portrait' => ['name' => 'AI Selfie & Portrait', 'parent' => 'image'],
    'ai-tattoo-generator' => ['name' => 'AI Tattoo Generator', 'parent' => 'image'],
    'ai-image-scanning' => ['name' => 'AI Image Scanning', 'parent' => 'image'],
    'image-to-image' => ['name' => 'Image to Image', 'parent' => 'image'],

    // Video subcategories
    'ai-anime-cartoon-generator' => ['name' => 'AI Anime & Cartoon Generator', 'parent' => 'video'],
    'ai-animated-video' => ['name' => 'AI Animated Video', 'parent' => 'video'],
    'image-to-video' => ['name' => 'Image to Video', 'parent' => 'video'],
    'ai-music-video-generator' => ['name' => 'AI Music Video Generator', 'parent' => 'video'],
    'ai-video-editor' => ['name' => 'AI Video Editor', 'parent' => 'video'],
    'ai-video-enhancer' => ['name' => 'AI Video Enhancer', 'parent' => 'video'],
    'text-to-video' => ['name' => 'Text to Video', 'parent' => 'video'],
    'ai-thumbnail-maker' => ['name' => 'AI Thumbnail Maker', 'parent' => 'video'],
    'ai-ugc-video-generator' => ['name' => 'AI UGC Video Generator', 'parent' => 'video'],
    'ai-video-search' => ['name' => 'AI Video Search', 'parent' => 'video'],
    'video-to-video' => ['name' => 'Video to Video', 'parent' => 'video'],
    'ai-personalized-video-generator' => ['name' => 'AI Personalized Video Generator', 'parent' => 'video'],
    'ai-video-generator' => ['name' => 'AI Video Generator', 'parent' => 'video'],
    'ai-short-clips-generator' => ['name' => 'AI Short Clips Generator', 'parent' => 'video'],
    'ai-lip-sync-generator' => ['name' => 'AI Lip Sync Generator', 'parent' => 'video'],

    // Code & IT subcategories
    'ai-maps-generator' => ['name' => 'AI Maps Generator', 'parent' => 'code-it'],
    'ai-devops-assistant' => ['name' => 'AI DevOps Assistant', 'parent' => 'code-it'],
    'ai-landing-page-builder' => ['name' => 'AI Landing Page Builder', 'parent' => 'code-it'],
    'ai-website-builder' => ['name' => 'AI Website Builder', 'parent' => 'code-it'],
    'no-code-low-code' => ['name' => 'No-Code & Low-Code', 'parent' => 'code-it'],
    'ai-code-assistant' => ['name' => 'AI Code Assistant', 'parent' => 'code-it'],
    'ai-code-explanation' => ['name' => 'Code Explanation', 'parent' => 'code-it'],
    'ai-code-generator' => ['name' => 'AI Code Generator', 'parent' => 'code-it'],
    'ai-code-refactoring' => ['name' => 'AI Code Refactoring', 'parent' => 'code-it'],
    'ai-monitor-report-builder' => ['name' => 'AI Monitor & Report Builder', 'parent' => 'code-it'],
    'ai-data-mining' => ['name' => 'AI Data Mining', 'parent' => 'code-it'],

    // Voice subcategories
    'ai-audio-enhancer' => ['name' => 'AI Audio Enhancer', 'parent' => 'voice'],
    'ai-music-generator' => ['name' => 'AI Music Generator', 'parent' => 'voice'],
    'text-to-speech' => ['name' => 'Text-to-Speech', 'parent' => 'voice'],
    'speech-to-text' => ['name' => 'Speech-to-Text', 'parent' => 'voice'],
    'voice-audio-editing' => ['name' => 'Voice & Audio Editing', 'parent' => 'voice'],
    'ai-voice-changer' => ['name' => 'AI Voice Changer', 'parent' => 'voice'],
    'ai-voice-chat-generator' => ['name' => 'AI Voice Chat Generator', 'parent' => 'voice'],
    'ai-voice-cloning' => ['name' => 'AI Voice Cloning', 'parent' => 'voice'],

    // Business subcategories
    'accounting-assistant' => ['name' => 'AI Accounting Assistant', 'parent' => 'business'],
    'research-tool' => ['name' => 'Research Tool', 'parent' => 'business'],
    'ai-business-ideas-generator' => ['name' => 'AI Business Ideas Generator', 'parent' => 'business'],
    'ai-consulting-assistant' => ['name' => 'AI Consulting Assistant', 'parent' => 'business'],
    'ai-trading-bot-assistant' => ['name' => 'AI Trading Bot Assistant', 'parent' => 'business'],
    'tax-assistant' => ['name' => 'Tax Assistant', 'parent' => 'business'],
    'investing-assistant' => ['name' => 'Investing Assistant', 'parent' => 'business'],
    'sales-assistant' => ['name' => 'Sales Assistant', 'parent' => 'business'],
    'e-commerce-assistant' => ['name' => 'E-commerce Assistant', 'parent' => 'business'],

    // Marketing subcategories
    'advertising-assistant' => ['name' => 'AI Advertising Assistant', 'parent' => 'marketing'],
    'ai-instagram-assistant' => ['name' => 'AI Instagram Assistant', 'parent' => 'marketing'],
    'ai-twitter-assistant' => ['name' => 'AI Twitter Assistant', 'parent' => 'marketing'],
    'ai-youtube-assistant' => ['name' => 'AI YouTube Assistant', 'parent' => 'marketing'],
    'ai-facebook-assistant' => ['name' => 'AI Facebook Assistant', 'parent' => 'marketing'],
    'ai-tiktok-assistant' => ['name' => 'AI Tiktok Assistant', 'parent' => 'marketing'],
    'ai-analytics-assistant' => ['name' => 'AI Analytics Assistant', 'parent' => 'marketing'],
    'ai-customer-service-assistant' => ['name' => 'AI Customer Service Assistant', 'parent' => 'marketing'],
    'ai-podcast-assistant' => ['name' => 'AI Podcast Assistant', 'parent' => 'marketing'],

    // AI Detector subcategories
    'ai-detector-1' => ['name' => 'AI Detector', 'parent' => 'ai-detector'],
    'ai-checker-essay' => ['name' => 'AI Checker Essay', 'parent' => 'ai-detector'],
    'ai-plagiarism-checker' => ['name' => 'AI Plagiarism Checker', 'parent' => 'ai-detector'],
    'ai-grammar-checker' => ['name' => 'AI Grammar Checker', 'parent' => 'ai-detector'],
    'ai-content-detector' => ['name' => 'AI Content Detector', 'parent' => 'ai-detector'],

    // Chatbot (tylko główna kategoria, bez podkategorii)

    // Design & Art subcategories
    'ai-3d-generator' => ['name' => 'AI 3D Generator', 'parent' => 'design-art'],
    'ai-graphic-design' => ['name' => 'AI Graphic Design', 'parent' => 'design-art'],
    'ai-interior-design' => ['name' => 'AI Interior Design', 'parent' => 'design-art'],
    'ai-vector-artist-design' => ['name' => 'AI Vector & Artist Design', 'parent' => 'design-art'],
    'ai-font-generator' => ['name' => 'AI Font Generator', 'parent' => 'design-art'],
    'ai-website-designer' => ['name' => 'AI Website Designer', 'parent' => 'design-art'],

    // Life Assistant subcategories
    'ai-cooking-assistant' => ['name' => 'AI Cooking Assistant', 'parent' => 'life-assistant'],
    'ai-dating-assistant' => ['name' => 'AI Dating Assistant', 'parent' => 'life-assistant'],
    'ai-gift-ideas' => ['name' => 'AI Gift Ideas', 'parent' => 'life-assistant'],
    'ai-job-finder' => ['name' => 'AI Job Finder', 'parent' => 'life-assistant'],
    'ai-interview-assistant' => ['name' => 'AI Interview Assistant', 'parent' => 'life-assistant'],
    'ai-nutrition' => ['name' => 'AI Nutrition', 'parent' => 'life-assistant'],
    'ai-parenting' => ['name' => 'AI Parenting', 'parent' => 'life-assistant'],
    'resume-builder' => ['name' => 'Resume Builder', 'parent' => 'life-assistant'],
    'cover-letter-generator' => ['name' => 'Cover Letter Generator', 'parent' => 'life-assistant'],
    'fitness' => ['name' => 'Fitness', 'parent' => 'life-assistant'],
    'healthcare' => ['name' => 'Healthcare', 'parent' => 'life-assistant'],
    'sports' => ['name' => 'Sports', 'parent' => 'life-assistant'],
    'mental-health' => ['name' => 'Mental Health', 'parent' => 'life-assistant'],
    'legal-assistance' => ['name' => 'Legal Assistance', 'parent' => 'life-assistant'],
    'travel' => ['name' => 'Travel', 'parent' => 'life-assistant'],

    // 3D subcategories
    'ai-3d-modeling' => ['name' => 'AI 3D Modeling', 'parent' => '3d'],
    'text-to-3d' => ['name' => 'Text to 3D', 'parent' => '3d'],
    'image-to-3d-model' => ['name' => 'Image to 3D Model', 'parent' => '3d'],

    // Education subcategories
    'homework-helper' => ['name' => 'Homework Helper', 'parent' => 'education'],
    'ai-knowledge-graph' => ['name' => 'AI Knowledge Graph', 'parent' => 'education'],
    'ai-knowledge-management' => ['name' => 'AI Knowledge Management', 'parent' => 'education'],
    'ai-quizzes' => ['name' => 'AI Quizzes', 'parent' => 'education'],
    'ai-tutoring' => ['name' => 'AI Tutoring', 'parent' => 'education'],
    'ai-education-assistant' => ['name' => 'AI Education Assistant', 'parent' => 'education'],

    // Prompt categories
    'ai-prompts' => ['name' => 'AI Prompts', 'parent' => 'prompt'],

    // Productivity subcategories
    'ai-project-management' => ['name' => 'AI Project Management', 'parent' => 'productivity'],
    'ai-spreadsheet' => ['name' => 'AI Spreadsheet', 'parent' => 'productivity'],
    'ai-todo' => ['name' => 'AI TODO', 'parent' => 'productivity'],
    'ai-pdf' => ['name' => 'AI PDF', 'parent' => 'productivity'],
    'ai-docs-collaboration' => ['name' => 'AI Docs Collaboration', 'parent' => 'productivity'],
    'ai-notes-assistant' => ['name' => 'AI Notes Assistant', 'parent' => 'productivity'],
    'ai-schedule-management' => ['name' => 'AI Schedule Management', 'parent' => 'productivity'],
    'ai-ocr' => ['name' => 'AI OCR', 'parent' => 'productivity'],
    'ai-presentation' => ['name' => 'AI Presentation', 'parent' => 'productivity'],
    'ai-diagram-conversion' => ['name' => 'AI Diagram Conversion', 'parent' => 'productivity'],
    'ai-document-extraction' => ['name' => 'AI Document Extraction', 'parent' => 'productivity'],
    'ai-video-recording' => ['name' => 'AI Video Recording', 'parent' => 'productivity'],

    // Other categories
    'ai-text-similarity' => ['name' => 'AI Text Similarity', 'parent' => 'other'],
    'newsletter' => ['name' => 'Newsletter', 'parent' => 'other'],
    'nfts' => ['name' => 'NFTs', 'parent' => 'other'],
    'other' => ['name' => 'Other', 'parent' => 'other']
];

// Importowanie głównych kategorii
echo '<h2>Importowanie głównych kategorii...</h2>';
$main_category_ids = [];

foreach ($main_categories as $slug => $name) {
    // Sprawdź czy kategoria już istnieje
    $sql = "SELECT id FROM categories WHERE slug = '" . $conn->real_escape_string($slug) . "' AND parent_id IS NULL";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Kategoria już istnieje, zapisz jej ID
        $row = $result->fetch_assoc();
        $main_category_ids[$slug] = $row['id'];
        echo "<p>Kategoria główna '$name' już istnieje (ID: " . $row['id'] . ")</p>";
    } else {
        // Utwórz nową kategorię
        $sql = "INSERT INTO categories (name, slug, description) VALUES (
            '" . $conn->real_escape_string($name) . "',
            '" . $conn->real_escape_string($slug) . "',
            'Main category: " . $conn->real_escape_string($name) . "'
        )";

        if ($conn->query($sql)) {
            $main_category_ids[$slug] = $conn->insert_id;
            echo "<p style=\"color: green;\">Utworzono kategorię główną: $name (ID: " . $conn->insert_id . ")</p>";
        } else {
            echo "<p style=\"color: red;\">Błąd podczas tworzenia kategorii głównej $name: " . $conn->error . "</p>";
        }
    }
}

// Importowanie podkategorii
echo '<h2>Importowanie podkategorii...</h2>';

foreach ($subcategories as $slug => $subcategory) {
    $name = $subcategory['name'];
    $parent_slug = $subcategory['parent'];

    // Sprawdź czy mamy ID rodzica
    if (!isset($main_category_ids[$parent_slug])) {
        echo "<p style=\"color: red;\">Brak kategorii głównej dla podkategorii $name (slug: $slug, parent: $parent_slug)</p>";
        continue;
    }

    $parent_id = $main_category_ids[$parent_slug];

    // Sprawdź czy podkategoria już istnieje
    $sql = "SELECT id FROM categories WHERE slug = '" . $conn->real_escape_string($slug) . "'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        // Podkategoria już istnieje, zaktualizuj jej parent_id
        $row = $result->fetch_assoc();
        $sql = "UPDATE categories SET parent_id = " . $parent_id . " WHERE id = " . $row['id'];

        if ($conn->query($sql)) {
            echo "<p>Zaktualizowano podkategorię '$name' z parent_id = $parent_id</p>";
        } else {
            echo "<p style=\"color: red;\">Błąd podczas aktualizacji podkategorii $name: " . $conn->error . "</p>";
        }
    } else {
        // Utwórz nową podkategorię
        $sql = "INSERT INTO categories (name, slug, description, parent_id) VALUES (
            '" . $conn->real_escape_string($name) . "',
            '" . $conn->real_escape_string($slug) . "',
            'Subcategory of " . $conn->real_escape_string($main_categories[$parent_slug]) . "',
            " . $parent_id . "
        )";

        if ($conn->query($sql)) {
            echo "<p style=\"color: green;\">Utworzono podkategorię: $name (rodzic: " . $main_categories[$parent_slug] . ")</p>";
        } else {
            echo "<p style=\"color: red;\">Błąd podczas tworzenia podkategorii $name: " . $conn->error . "</p>";
        }
    }
}

// Aktualizacja liczby narzędzi w kategoriach
echo '<h2>Aktualizacja liczby narzędzi w kategoriach...</h2>';
if (function_exists('update_category_counts')) {
    if (update_category_counts()) {
        echo '<p style="color: green;">Liczba narzędzi w kategoriach została zaktualizowana.</p>';
    } else {
        echo '<p style="color: red;">Wystąpił błąd podczas aktualizacji liczby narzędzi.</p>';
    }
} else {
    echo '<p style="color: orange;">Funkcja update_category_counts() nie istnieje. Liczby narzędzi nie zostały zaktualizowane.</p>';
}

// Zakończ i wypisz output
$output = ob_get_clean();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Kategorii z Toolify.ai</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #333;
        }
        p {
            margin-bottom: 10px;
        }
        .success {
            color: green;
        }
        .warning {
            color: orange;
        }
        .error {
            color: red;
        }
        .actions {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f8f8;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background-color: #7659f2;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
        }
        .btn-danger {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="actions">
        <p>Wybierz opcje importu:</p>
        <a href="?delete_existing=1" class="btn btn-danger" onclick="return confirm('UWAGA! Ta operacja usunie wszystkie istniejące kategorie i powiązania narzędzi. Czy na pewno chcesz kontynuować?')">Usuń istniejące kategorie i zaimportuj nowe</a>
        <a href="?" class="btn">Zachowaj istniejące kategorie i dodaj brakujące</a>
    </div>

    <?php echo $output; ?>

    <hr>
    <p><a href="/">Powrót do strony głównej</a></p>
</body>
</html>
