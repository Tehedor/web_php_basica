<!-- storage_proxy.php -->
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

$url = "http://{$storage_ip}:9000/images/" . basename($filename);

// Usa cURL para obtener la imagen internamente
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

curl_close($ch);

if ($httpCode !== 200 || !$data) {
    http_response_code(404);
    echo "Image not found.";
    exit;
}

header("Content-Type: $mime");
echo $data;

