<?php
if (session_status() === PHP_SESSION_NONE) {
    require_once '../../config/session_config.php';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Sistema de Gestión'; ?></title>
    <link rel="stylesheet" href="../../../public/css/index.css">
    <?php if (isset($additionalCSS)): ?>
        <?php foreach($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>