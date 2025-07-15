<?php
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST["correo"];
    $contrasena = password_hash($_POST["contrasena"], PASSWORD_DEFAULT);
    $tipo = preg_match('/@(gmail|hotmail|yahoo|outlook)\\./i', $correo) ? 'personal' : 'empresarial';

    $sql = "INSERT INTO usuarios (correo, contrasena, tipo) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $correo, $contrasena, $tipo);

    if ($stmt->execute()) {
        header("Location: login2.php");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>