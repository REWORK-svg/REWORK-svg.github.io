<?php
include("conexion.php"); //
session_start(); // Asegúrate de que session_start() esté al principio

if ($_SERVER["REQUEST_METHOD"] == "POST") { //
    $correo = $_POST["correo"]; //
    $contrasena = $_POST["contrasena"]; //

    $sql = "SELECT * FROM usuarios WHERE correo = ?"; //
    $stmt = $conn->prepare($sql); //
    $stmt->bind_param("s", $correo); //
    $stmt->execute(); //
    $res = $stmt->get_result(); //

    if ($res->num_rows == 1) { //
        $user = $res->fetch_assoc(); //
        if (password_verify($contrasena, $user["contrasena"])) { //
            $_SESSION["usuario_id"] = $user["id"]; //
            $_SESSION["tipo"] = $user["tipo"]; //

            header("Location: dashboard_" . $user["tipo"] . ".php"); //
            exit(); //
        } else {
            // Contraseña incorrecta
            $_SESSION['alert_message'] = "Contraseña incorrecta. Inténtalo de nuevo."; // Usamos 'alert_message' para el JS alert
            header("Location: login2.php"); // Redirige a login.php
            exit();
        }
    } else {
        // Usuario no encontrado
        $_SESSION['alert_message'] = "Usuario no encontrado. Por favor, verifica tu correo."; // Usamos 'alert_message' para el JS alert
        header("Location: login2.php"); // Redirige a login.php
        exit();
    }
}
?>