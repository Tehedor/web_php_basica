<?php
// Carga configuración y conexión
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_config.php';

try {
    $stmt = $conn->query("SELECT * FROM `usuarios` LIMIT 1000");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $rows = [];
    $error = $e->getMessage();
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($PAGE_TITLE); ?></title>
</head>
<body>
    <h1><?php echo htmlspecialchars($PAGE_TITLE); ?></h1>
    
    <h2>Usuarios from DB mysql</h2>
    <?php if (!empty($error)): ?>
        <p>Error al consultar la BD: <?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <p>No hay registros en la tabla usuarios.</p>
    <?php else: ?>
        <table border="1">
            <thead>
                <tr>
                    <?php foreach (array_keys($rows[0]) as $col): ?>
                        <th><?php echo htmlspecialchars($col); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <?php foreach ($r as $v): ?>
                            <td><?php echo htmlspecialchars((string)$v); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Images from Storage</h2>
    <img src="storage/image.jpg" alt="Storage Image">
</body>
</html>