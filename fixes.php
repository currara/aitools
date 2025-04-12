<?php
// Ten skrypt naprawia plik .htaccess
$htaccess_content = "RewriteEngine On

# IMPORTANT: No automatic language detection for root path
# domain.com/ will always show English content

# Static resources and admin paths
RewriteCond %{REQUEST_URI} ^/(admin|includes|css|js|images|uploads|languages|robots\.txt)
RewriteRule .* - [L]

# For language prefixed URLs - remove prefix and add language parameter
RewriteRule ^(pl|es|pt|ru)/(.*)$ $2?lang=$1 [QSA]

# Process PHP pages
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([^/]+)/?$ $1.php [QSA,L]
RewriteRule ^([^/]+)/([^/]+)/?$ $1.php?slug=$2 [QSA,L]";

// Zapisz naprawioną zawartość do .htaccess
file_put_contents(__DIR__ . '/.htaccess', $htaccess_content);

echo "Plik .htaccess został naprawiony";
?>
