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
        // $sqlFile = __DIR__ . '/init-data.sql';
        // if (file_exists($sqlFile)) {
        //     $sqlContent = file_get_contents($sqlFile);
        //     if ($sqlContent) {
        //         // Separa por ";" para ejecutar cada comando SQL
        //         $queries = array_filter(array_map('trim', explode(';', $sqlContent)));
        //         foreach ($queries as $query) {
        //             if ($query) {
        //                 $conn->exec($query); // Ejecuta cada query
        //             }
        //         }
        //     }
        // }
        // --- FIN inicialización ---

        // 2b. Ahora leemos la tabla de usuarios
        $stmt = $conn->query("SELECT * FROM `usuarios` LIMIT 1000");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $error = $e->getMeassage();
    }
}

// Nombre de la imagen
$filename = 'image.jpg';
// IP del object storage desde variable de entorno (puede estar vacía)
$ip_object_storage = getenv('IP_OBJECT_STORAGE') ?: '';
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
    const filename = <?= json_encode($filename) ?>;
    const storageIP = <?= json_encode($ip_object_storage) ?>;

    function showError(msg) {
        container.innerHTML = `<p class="error">${msg}</p>`;
    }

    function showImgFromSrc(src) {
        const img = document.createElement('img');
        img.width = 300;
        img.alt = 'Storage Image';
        img.src = src;
        img.onload = () => {
            container.innerHTML = '';
            container.appendChild(img);
        };
        img.onerror = () => {
            showError('No se ha podido acceder a la imagen de storage.');
        };
    }

    // 1) Intentar proxy (fetch_image.php)
    try {
        const proxyResp = await fetch(`fetch_image.php?file=${encodeURIComponent(filename)}`);
        if (proxyResp.ok) {
            const blob = await proxyResp.blob();
            const objectUrl = URL.createObjectURL(blob);
            showImgFromSrc(objectUrl);
            return;
        }
    } catch (e) {
        // continuar a intento directo
    }

    // 2) Intento directo construyendo URL a partir de IP_OBJECT_STORAGE
    if (storageIP && storageIP.length > 0) {
        let base = storageIP;
        if (!/^https?:\/\//i.test(base)) {
            if (base.indexOf(':') === -1) {
                base = 'http://' + base + ':9000';
            } else {
                base = 'http://' + base;
            }
        }
        base = base.replace(/\/+$/, '');
        const directUrl = `${base}/images/${encodeURIComponent(filename)}`;

        showImgFromSrc(directUrl);
        return;
    }

    // 3) Fallback: intentar imagen local relativa 'storage/image.jpg'
    const localPath = `storage/${filename}`;
    const testImg = document.createElement('img');
    testImg.onload = () => { showImgFromSrc(localPath); };
    testImg.onerror = () => { showError('No se ha podido acceder a la imagen de storage.'); };
    testImg.src = localPath;
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
