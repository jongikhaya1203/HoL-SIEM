<?php
$password = 'admin123';
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Testing password: $password\n";
echo "Against hash: $hash\n\n";

if (password_verify($password, $hash)) {
    echo "✓ Password MATCHES hash\n";
} else {
    echo "✗ Password DOES NOT match hash\n";
    echo "\nGenerating new hash for 'admin123'...\n";
    $newHash = password_hash('admin123', PASSWORD_DEFAULT);
    echo "New hash: $newHash\n";
}
?>
