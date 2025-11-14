<!-- index.php -->
<?php
// Carga configuración y conexión
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_config.php'; // Esto define $conn y $db_connection_error

$error = null;
$rows = [];

// Comprobamos si hubo error al conectar
if (isset($db_connection_error) && $db_connection_error) {
    $error = $db_connection_error;
} else {
    try {
        $stmt = $conn->query("SELECT * FROM `usuarios` LIMIT 1000");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
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
    <title><?= htmlspecialchars($PAGE_TITLE ?? 'Mi Página') ?></title>
    <style>
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
    // Archivo que queremos mostrar
    $filename = 'image.jpg';
    // URL del proxy PHP
    $proxyUrl = '/storage_proxy.php?file=' . urlencode($filename);
    ?>
    <p>Ruta: <?= htmlspecialchars($proxyUrl) ?></p>
    <img src="<?= htmlspecialchars($proxyUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Storage Image" width="300">

<script>
    // Reintentos si hubo error de BD
    const dbErrorOccurred = <?= json_encode(boolval($error)) ?>;

    if (dbErrorOccurred) {
        console.log('Error de BD detectado. Iniciando reintentos cada 10 segundos...');
        const pollingInterval = setInterval(checkDatabase, 10000);

        async function checkDatabase() {
            try {
                const response = await fetch('ajax_get_users.php');
                if (response.ok) {
                    const tableHtml = await response.text();
                    document.getElementById('usuarios-data').innerHTML = tableHtml;
                    clearInterval(pollingInterval);
                    console.log('¡Éxito! La BD ha respondido.');
                } else {
                    console.log('Reintento fallido. La BD sigue sin responder.');
                }
            } catch (error) {
                console.log('Error de red durante el reintento.', error);
            }
        }
    }
</script>

</body>
</html>
