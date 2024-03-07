<?php

$folder = 'audio';
$files = glob($folder . '/*');
foreach ($files as $file) {
    if (is_file($file) && basename($file) !== '.' && basename($file) !== '..') {
        echo "Deleting file: " . $file . "<br>";
        unlink($file);
    }
}

echo "All audio files have been deleted.";