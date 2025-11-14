<?php
// Configuración de conexión MySQL
// Actualiza estos valores según tu servidor
$db_host = getenv('DB_HOST') ?: 'localhost:3306';
// $db_host = getenv('DB_HOST') ?: 'mysql_db';
$db_user = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASS') ?: 'xxxx';
$db_name = getenv('DB_NAME') ?: 'ususarios_db';
$db_charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Inicializamos las variables
$conn = null;
$db_connection_error = null;

try {
    // Intentamos conectar
    $conn = new PDO($dsn, $db_user, $db_password, $options);
} catch (PDOException $e) {
    // ¡No usamos die()! Solo guardamos el mensaje de error.
    $db_connection_error = 'Error conexión BD: ' . $e->getMessage();
}