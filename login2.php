<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php
session_start(); // Inicia la sesión para acceder a $_SESSION
if (isset($_SESSION['alert_message'])) {
    echo '<script type="text/javascript">';
    // Asegúrate de escapar correctamente el mensaje para que no rompa el JavaScript
    echo 'alert("' . htmlspecialchars($_SESSION['alert_message'], ENT_QUOTES, 'UTF-8') . '");';
    echo '</script>';
    unset($_SESSION['alert_message']); // Elimina el mensaje después de mostrarlo
}
?>
<div class="card card-login-register">
    <h2>🔐 Iniciar Sesión</h2>
    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="correo">Correo:</label>
            <input type="email" id="correo" name="correo" placeholder="tu@ejemplo.com" required>
        </div>
        <div class="form-group">
            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" placeholder="********" required>
        </div>
        <button type="submit" class="btn">Entrar</button>
    </form>
    <a href="index.html" class="btn">← Regresar</a>
</div>
</body>
</html>