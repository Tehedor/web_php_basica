<?php
// 1. Carga configuración y conexión
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_config.php';

$error = null;
$rows = [];

// 2. Solo intentar inicializar la base si la conexión es exitosa
if (isset($db_connection_error) && $db_connection_error) {
    $error = $db_connection_error;
} else {
    try {
        // --- 2a. Inicializar base con init-data.sql ---
        $sqlFile = __DIR__ . '/init-data.sql';
        if (file_exists($sqlFile)) {
            $sqlContent = file_get_contents($sqlFile);
            if ($sqlContent) {
                // Separa por ";" para ejecutar cada comando SQL
                $queries = array_filter(array_map('trim', explode(';', $sqlContent)));
                foreach ($queries as $query) {
                    if ($query) {
                        $conn->exec($query); // Ejecuta cada query
                    }
                }
            }
        }
        // --- FIN inicialización ---

        // 2b. Ahora leemos la tabla de usuarios
        $stmt = $conn->query("SELECT * FROM `usuarios` LIMIT 1000");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Nombre de la imagen
$filename = 'image.jpg';
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
    <div id="image-container">
        <p>Cargando imagen...</p>
    </div>

<script>
async function loadImage() {
    const container = document.getElementById('image-container');
    const filename = '<?= $filename ?>';

    try {
        const response = await fetch(`fetch_image.php?file=${encodeURIComponent(filename)}`);
        if (!response.ok) throw new Error('No se pudo obtener la imagen');

        const blob = await response.blob();
        const img = document.createElement('img');
        img.width = 300;
        img.alt = 'Storage Image';
        img.src = URL.createObjectURL(blob);

        container.innerHTML = ''; // Limpiar mensaje de carga
        container.appendChild(img);
    } catch (err) {
        container.innerHTML = `<p class="error">Error al cargar la imagen: ${err.message}</p>`;
    }
}

loadImage();

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
