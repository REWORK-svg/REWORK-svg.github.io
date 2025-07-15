<?php
session_start();
include("conexion.php");

if (!isset($_SESSION["usuario_id"])) exit("Acceso denegado.");

$usuario_id = $_SESSION["usuario_id"];
$tipo = $_SESSION["tipo"];
$ingreso = $_POST["ingreso"];

if ($ingreso > 0) {
    $sql = "INSERT INTO gastos (usuario_id, descripcion, categoria, monto, fecha, tipo) 
            VALUES (?, 'Ingreso Mensual', 'Ingreso', ?, CURDATE(), ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ids", $usuario_id, $ingreso, $tipo);
    $stmt->execute();
    header("Location: dashboard_$tipo.php");
}
?>