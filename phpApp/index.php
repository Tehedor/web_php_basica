<?php
// Carga configuración y conexión
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_config.php'; // Esto define $conn y $db_connection_error

$error = null;
$rows = [];

// PRIMERO comprobamos si hubo un error AL CONECTAR
if (isset($db_connection_error) && $db_connection_error) {
    $error = $db_connection_error;
} else {
    // Si no hubo error de conexión, INTENTAMOS LA CONSULTA
    try {
        $stmt = $conn->query("SELECT * FROM `usuarios` LIMIT 1000");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Esto captura errores en la CONSULTA (ej. tabla no existe)
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($PAGE_TITLE ?? 'Mi Página') ?></title>     <style>
        body { font-family: sans-serif; margin: 2em; }
        table { border-collapse: collapse; width: 100%; margin-top: 1em; }
        th, td { border: 1px solid #ccc; padding: 0.5em; text-align: left; }
        th { background: #f2f2f2; }
        .error { color: #c00; font-weight: bold; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($PAGE_TITLE ?? 'Mi Página') ?></h1>

    <h2>Usuarios desde MySQL</h2>
    
    <div id="usuarios-data">
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
    </div>


    <h2>Imagen desde almacenamiento</h2>
    <?php
        // ... (Todo tu bloque PHP para la imagen va aquí sin cambios) ...
        $ip = getenv('IP_OBJECT_STORAGE') ?: '';
        if ($ip !== '') {
            if (preg_match('#^https?://#i', $ip)) {
                $base = rtrim($ip, '/');
            } else {
                $base = 'http://' . $ip . ':9000';
            }
            $storageUrl = $base . '/images/image.jpg';
            
            $isAccessible = false;
            if (preg_match('#^https?://#i', $storageUrl)) {
                $headers = @get_headers($storageUrl);
                if ($headers && preg_match('#^HTTP/\d+\.\d+\s+2\d\d#', $headers[0])) {
                    $isAccessible = true;
                }
            } else {
                $localPath = __DIR__ . '/' . ltrim($storageUrl, '/');
                if (file_exists($localPath) && is_readable($localPath)) {
                    $isAccessible = true;
                    $storageUrl = 'storage/image.jpg';
                }
            }
        } else {
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


<script>
    // Pasamos la variable de error de PHP a JavaScript
    // json_encode(boolval($error)) se asegura de que sea 'true' o 'false'
    const dbErrorOccurred = <?= json_encode(boolval($error)) ?>;

    if (dbErrorOccurred) {
        console.log('Error de BD detectado. Iniciando reintentos cada 10 segundos...');
        
        // Guardamos el ID del intervalo para poder detenerlo
        const pollingInterval = setInterval(checkDatabase, 10000); // 10000 ms = 10 seg

        async function checkDatabase() {
            try {
                // Hacemos la llamada al nuevo archivo
                const response = await fetch('ajax_get_users.php');

                // response.ok es true si el código HTTP es 200-299
                if (response.ok) {
                    console.log('¡Éxito! La BD ha respondido.');
                    
                    // Obtenemos el HTML de la tabla
                    const tableHtml = await response.text();
                    
                    // Reemplazamos el mensaje de error por la tabla
                    document.getElementById('usuarios-data').innerHTML = tableHtml;
                    
                    // ¡Muy importante! Detenemos los reintentos
                    clearInterval(pollingInterval);
                    
                } else {
                    // El servidor respondió con 503 o 500
                    console.log('Reintento fallido. La BD sigue sin responder.');
                }
            } catch (error) {
                // Esto captura errores de red (ej. servidor caído)
                console.log('Error de red durante el reintento.', error);
            }
        }
    }
</script>

</body>
</html>