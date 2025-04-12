<?php
// Include header
include_once 'includes/header.php';
?>

<section class="section section-localization">
    <div class="container">
        <div class="localization-content">
            <h1>Multilanguage Support Documentation</h1>

            <div class="documentation-section">
                <h2>Available Languages</h2>
                <div class="language-grid">
                    <?php foreach ($available_languages as $code => $language): ?>
                        <div class="language-item">
                            <div class="language-badge <?php echo ($current_language === $code) ? 'active' : ''; ?>">
                                <?php echo $language['native_name']; ?>
                                <?php if ($current_language === $code): ?>
                                    <span class="current-badge">Current</span>
                                <?php endif; ?>
                            </div>
                            <div class="language-info">
                                <p><strong>Code:</strong> <?php echo $code; ?></p>
                                <p><strong>Direction:</strong> <?php echo $language['direction']; ?></p>
                                <p><strong>Locale:</strong> <?php echo $language['locale']; ?></p>
                                <a href="<?php echo get_language_url($code); ?>" class="btn btn-sm btn-primary">Switch to <?php echo $language['name']; ?></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="documentation-section">
                <h2>How the Language System Works</h2>
                <p>Our multilanguage system is a comprehensive solution that enables easy handling of different language versions. Here are the main features:</p>

                <h3>1. Language Detection</h3>
                <p>The system detects the preferred language based on:</p>
                <ul>
                    <li>URL parameter (<code>?lang=pl</code>)</li>
                    <li>Session storage</li>
                    <li>Cookie</li>
                    <li>Browser settings (HTTP_ACCEPT_LANGUAGE)</li>
                </ul>

                <h3>2. Translation Files</h3>
                <p>Each language has its translation file in the <code>languages/</code> directory containing all user interface texts.</p>
                <pre><code>languages/
├── en.php   # English (default)
├── pl.php   # Polish
├── es.php   # Spanish
├── pt.php   # Portuguese
└── ru.php   # Russian</code></pre>

                <h3>3. Translation Function</h3>
                <p>The <code>__()</code> function allows for easy text translation with variable support:</p>
                <pre><code>// Basic translation
echo __('home');  // Output: "Home" in English, "Strona główna" in Polish

// Translation with variables
echo __('hero_description', $total_tools);
// Output: "Find the perfect AI tools for your needs from our extensive directory of 100+ AI tools and GPTs"</code></pre>

                <h3>4. SEO for Multiple Languages</h3>
                <p>The system implements:</p>
                <ul>
                    <li>hreflang tags for alternative language versions</li>
                    <li>Language-specific metadata</li>
                    <li>Canonical URLs</li>
                </ul>

                <h3>5. RTL Support</h3>
                <p>The system supports both LTR (Left-to-Right) and RTL (Right-to-Left) text directions with conditional loading of RTL styles.</p>

                <h3>6. Easy Extensibility</h3>
                <p>To add a new language, simply:</p>
                <ol>
                    <li>Create a new translation file in the <code>languages/</code> directory</li>
                    <li>Add the language to the <code>$available_languages</code> array in <code>includes/language.php</code></li>
                </ol>
            </div>

            <div class="documentation-section">
                <h2>Using the Translation Function</h2>
                <p>The <code>__()</code> function is designed to be simple yet powerful:</p>
                <pre><code>// Basic translation
echo __('key');

// Translation with a single variable
echo __('key_with_var', $variable);

// Translation with multiple variables
echo __('key_with_multiple_vars', $var1, $var2, $var3);  </code></pre>

                <p>Inside your language files, you can define translations like this:</p>
                <pre><code>// Basic string
'key' => 'Translated text',

// String with a placeholder
'key_with_var' => 'Translated text with %s',

// String with multiple placeholders
'key_with_multiple_vars' => 'First: %s, Second: %s, Third: %s',</code></pre>
            </div>

            <div class="documentation-section">
                <h2>Language Switching</h2>
                <p>Users can switch languages using:</p>
                <ul>
                    <li>The language dropdown in the header</li>
                    <li>The language options in the mobile menu</li>
                    <li>The language links in the footer</li>
                    <li>Directly via URL parameter: <code>?lang=pl</code></li>
                </ul>

                <p>When a user selects a language, it's stored in:</p>
                <ul>
                    <li>Session: for the current browsing session</li>
                    <li>Cookie: for persistent language preference (30 days)</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<style>
    .section-localization {
        padding: 60px 0;
        background-color: var(--white);
    }

    .localization-content {
        max-width: 900px;
        margin: 0 auto;
    }

    .localization-content h1 {
        font-size: 2.2rem;
        margin-bottom: 30px;
        color: var(--gray-900);
        text-align: center;
    }

    .documentation-section {
        margin-bottom: 40px;
        padding: 30px;
        background-color: var(--gray-100);
        border-radius: var(--border-radius);
    }

    .documentation-section h2 {
        font-size: 1.8rem;
        margin-bottom: 20px;
        color: var(--gray-900);
        padding-bottom: 10px;
        border-bottom: 1px solid var(--gray-300);
    }

    .documentation-section h3 {
        font-size: 1.4rem;
        margin: 25px 0 15px;
        color: var(--gray-800);
    }

    .documentation-section p {
        margin-bottom: 15px;
        line-height: 1.6;
    }

    .documentation-section ul,
    .documentation-section ol {
        margin: 15px 0;
        padding-left: 20px;
    }

    .documentation-section li {
        margin-bottom: 8px;
    }

    .documentation-section code {
        background-color: var(--gray-200);
        padding: 2px 5px;
        border-radius: 4px;
        font-family: monospace;
        font-size: 0.9em;
    }

    .documentation-section pre {
        background-color: var(--gray-800);
        color: var(--white);
        padding: 15px;
        border-radius: var(--border-radius);
        overflow-x: auto;
        margin: 15px 0;
    }

    .documentation-section pre code {
        background-color: transparent;
        color: var(--white);
        padding: 0;
    }

    .language-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .language-item {
        background-color: var(--white);
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
    }

    .language-badge {
        background-color: var(--gray-200);
        padding: 15px;
        text-align: center;
        font-weight: 600;
        font-size: 1.2rem;
        position: relative;
    }

    .language-badge.active {
        background-color: var(--primary-color);
        color: var(--white);
    }

    .current-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        background-color: var(--white);
        color: var(--primary-color);
        font-size: 0.7rem;
        padding: 2px 5px;
        border-radius: 10px;
        font-weight: 700;
    }

    .language-info {
        padding: 15px;
    }

    .language-info p {
        margin-bottom: 10px;
        font-size: 0.9rem;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 0.9rem;
    }

    @media screen and (max-width: 768px) {
        .localization-content h1 {
            font-size: 1.8rem;
        }

        .documentation-section {
            padding: 20px;
        }

        .documentation-section h2 {
            font-size: 1.5rem;
        }

        .documentation-section h3 {
            font-size: 1.2rem;
        }

        .language-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
