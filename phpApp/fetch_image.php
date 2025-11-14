<?php
$filename = $_GET['file'] ?? null;
if (!$filename) {
    http_response_code(400);
    echo "Missing file parameter.";
    exit;
}

$storage_ip = getenv('IP_OBJECT_STORAGE');
if (!$storage_ip) {
    http_response_code(500);
    echo "Storage IP not configured.";
    exit;
}

$localFile = __DIR__ . '/images/' . basename($filename);

// Si la imagen ya existe localmente, la usamos directamente
if (!file_exists($localFile)) {
    // Descargar desde el storage
    $url = "http://{$storage_ip}:9000/images/" . basename($filename);
    $data = @file_get_contents($url);

    if (!$data) {
        http_response_code(404);
        echo "Image not found.";
        exit;
    }

    // Guardar localmente
    if (!is_dir(__DIR__ . '/images')) {
        mkdir(__DIR__ . '/images', 0755, true);
    }
    file_put_contents($localFile, $data);
}

// Servir la imagen
$info = getimagesize($localFile);
header("Content-Type: " . $info['mime']);
readfile($localFile);
