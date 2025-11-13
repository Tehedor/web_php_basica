<?php
// Carga configuración y conexión
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_config.php';

$error = null;
$rows = [];

try {
    $stmt = $conn->query("SELECT * FROM `usuarios` LIMIT 1000");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($PAGE_TITLE) ?></title>
    <style>
        body { font-family: sans-serif; margin: 2em; }
        table { border-collapse: collapse; width: 100%; margin-top: 1em; }
        th, td { border: 1px solid #ccc; padding: 0.5em; text-align: left; }
        th { background: #f2f2f2; }
        .error { color: #c00; font-weight: bold; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($PAGE_TITLE) ?></h1>

    <h2>Usuarios desde MySQL</h2>
    <?php if ($error): ?>
        <p class="error">Error al consultar la base de datos: <?= htmlspecialchars($error) ?></p>
    <?php elseif (empty($rows)): ?>
        <p>No hay registros en la tabla <code>usuarios</code>.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <?php foreach (array_keys($rows[0]) as $col): ?>
                        <th><?= htmlspecialchars($col) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <?php foreach ($r as $v): ?>
                            <td><?= htmlspecialchars((string)$v) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>


    <h2>Imagen desde almacenamiento</h2>
    <?php
        // Obtener IP/host desde variable de entorno
        $ip = getenv('IP_OBJECT_STORAGE') ?: '';
        if ($ip !== '') {
            // Si el valor ya contiene esquema, úsalo tal cual; si no, construye http://IP:9000
            if (preg_match('#^https?://#i', $ip)) {
                $base = rtrim($ip, '/');
            } else {
                $base = 'http://' . $ip . ':9000';
            }
            $storageUrl = $base . '/images/image.jpg';

            // Comprobar accesibilidad remota (HEAD)
            $isAccessible = false;
            if (preg_match('#^https?://#i', $storageUrl)) {
                $headers = @get_headers($storageUrl);
                if ($headers && preg_match('#^HTTP/\d+\.\d+\s+2\d\d#', $headers[0])) {
                    $isAccessible = true;
                }
            } else {
                // URL no-http (raro), tratar como ruta local
                $localPath = __DIR__ . '/' . ltrim($storageUrl, '/');
                if (file_exists($localPath) && is_readable($localPath)) {
                    $isAccessible = true;
                    // ajustar ruta para el navegador si es necesario
                    $storageUrl = 'storage/image.jpg';
                }
            }
        } else {
            // fallback a imagen local si no hay variable de entorno
            $storageUrl = 'storage/image.jpg';
            $localPath = __DIR__ . '/' . $storageUrl;
            $isAccessible = file_exists($localPath) && is_readable($localPath);
        }
    ?>
    <?php if (!empty($isAccessible)): ?>
        <img src="<?= htmlspecialchars($storageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Storage Image" width="300">
    <?php else: ?>
        <p class="error">No se ha podido acceder a la imagen de storage.</p>
    <?php endif; ?>

</body>
</html>
