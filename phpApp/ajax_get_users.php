<!-- // ajax_get_users.php -->
<?php
// Cargamos las configuraciones (¡importante!)
// Asumimos que config.php y db_config.php están en el mismo directorio
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_config.php';

// 1. Comprobar si la conexión (de db_config.php) falló
if ($db_connection_error) {
    // Si falló, respondemos con un error de servidor.
    // JavaScript interpretará esto como un fallo y volverá a intentarlo.
    http_response_code(503); // 503 Service Unavailable
    echo "BD no disponible";
    exit;
}

// 2. Si la conexión tuvo éxito, intentamos la consulta
try {
    $stmt = $conn->query("SELECT * FROM `usuarios` LIMIT 1000");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Comprobar si hay resultados y construir el HTML de la tabla
    if (empty($rows)) {
        echo "<p>No hay registros en la tabla <code>usuarios</code>.</p>";
        exit;
    }

    // Si hay datos, construimos la tabla (este código es copiado de tu index.php)
    ?>
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
    <?php

} catch (Exception $e) {
    // Si la CONSULTA falla (ej. tabla no existe)
    http_response_code(500); // 500 Internal Server Error
    echo "Error en la consulta: " . htmlspecialchars($e->getMessage());
    exit;
}