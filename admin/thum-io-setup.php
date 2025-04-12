<?php
// Strona informacyjna do konfiguracji thum.io
include_once 'includes/header.php';
?>

<div class="admin-content">
    <h1>Konfiguracja Thum.io</h1>

    <div class="admin-card">
        <h2>Informacje o aktualizacji</h2>
        <p>
            Ta strona zawiera informacje dotyczące nowych funkcji związanych z konfiguracją usługi Thum.io
            do pobierania zrzutów ekranu i obrazów stron internetowych.
        </p>
        <p>
            Przed rozpoczęciem korzystania z nowych funkcji, należy zaktualizować bazę danych i funkcje systemu.
            Użyj poniższych linków do przeprowadzenia aktualizacji:
        </p>

        <div style="display: flex; gap: 15px; margin-top: 20px; margin-bottom: 30px;">
            <a href="update-schema.php" class="btn btn-primary">Aktualizuj schemat bazy danych</a>
            <a href="update-function.php" class="btn btn-primary">Aktualizuj funkcje systemu</a>
        </div>

        <h2>Nowe funkcje</h2>
        <div class="admin-info-list">
            <div class="admin-info-item">
                <h3>Zaawansowana konfiguracja Thum.io</h3>
                <p>
                    Możliwość dostosowania parametrów usługi Thum.io do generowania zrzutów ekranu:
                </p>
                <ul>
                    <li>Szerokość zrzutu (640px, 800px, 1024px, 1280px)</li>
                    <li>Format obrazu (PNG, JPG, WebP)</li>
                    <li>Tryb widoku (desktop, mobile, tablet)</li>
                </ul>
            </div>

            <div class="admin-info-item">
                <h3>Wybór typu obrazu</h3>
                <p>
                    Możliwość wyboru typu pobieranego obrazu:
                </p>
                <ul>
                    <li><strong>Zrzut ekranu</strong> - pełny zrzut strony internetowej</li>
                    <li><strong>Logo/Favicon</strong> - pobieranie ikony/logo strony</li>
                </ul>
            </div>

            <div class="admin-info-item">
                <h3>Automatyczne generowanie miniatur</h3>
                <p>
                    System automatycznie generuje miniatury w różnych rozmiarach:
                </p>
                <ul>
                    <li>150px - miniatura do listy narzędzi</li>
                    <li>300px - miniatura średniej wielkości</li>
                    <li>600px - duża miniatura</li>
                </ul>
                <p>
                    Wszystkie miniatury są generowane z zachowaniem proporcji obrazu.
                </p>
            </div>

            <div class="admin-info-item">
                <h3>Podgląd narzędzia</h3>
                <p>
                    Nowy podgląd narzędzia w panelu edycji, który pokazuje, jak będzie wyglądać narzędzie
                    przed zapisaniem zmian. Podgląd aktualizuje się na bieżąco podczas edycji pól.
                </p>
            </div>

            <div class="admin-info-item">
                <h3>Wstawianie obrazów do edytora</h3>
                <p>
                    Możliwość wstawiania obrazów do opisu narzędzia za pomocą edytora tekstowego.
                    Wystarczy kliknąć przycisk z ikoną obrazu i podać adres URL obrazu.
                </p>
            </div>
        </div>

        <h2>Bezpieczeństwo</h2>
        <p>
            Wszystkie dane wprowadzane przez użytkownika są odpowiednio walidowane i czyszczone przed zapisaniem
            do bazy danych. System sprawdza również typy plików przesyłanych na serwer, aby zapobiec atakom.
        </p>
    </div>
</div>

<style>
    .admin-card {
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        padding: 25px;
        margin-bottom: 25px;
    }

    .admin-info-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .admin-info-item {
        background: #f9f9f9;
        border-radius: 6px;
        padding: 20px;
        border-left: 4px solid #007bff;
    }

    .admin-info-item h3 {
        margin-top: 0;
        color: #007bff;
    }

    ul {
        padding-left: 20px;
    }

    li {
        margin-bottom: 8px;
    }
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
