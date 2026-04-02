<?php
// Cek path
echo "<h3>Informasi Path Backup</h3>";

// Path relatif (dari admin)
$relatif = '../backups/';
echo "Path Relatif: " . realpath($relatif) . "<br>";
echo "Ada Folder? " . (file_exists($relatif) ? '✅ Ya' : '❌ Tidak') . "<br>";
echo "Bisa Tulis? " . (is_writable($relatif) ? '✅ Ya' : '❌ Tidak') . "<br><br>";

// Path absolut
$absolut = dirname(__DIR__) . '/backups/';
echo "Path Absolut: " . realpath($absolut) . "<br>";
echo "Ada Folder? " . (file_exists($absolut) ? '✅ Ya' : '❌ Tidak') . "<br>";
echo "Bisa Tulis? " . (is_writable($absolut) ? '✅ Ya' : '❌ Tidak') . "<br><br>";

// Cek isi folder
echo "<h3>Isi Folder Backups:</h3>";
$files = scandir($relatif);
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        $size = filesize($relatif . $file) / 1024 / 1024;
        echo "📄 $file - " . number_format($size, 2) . " MB<br>";
    }
}
?>