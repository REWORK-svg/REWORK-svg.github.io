<?php
session_start();
include("conexion.php");

if (!isset($_SESSION["usuario_id"])) exit("Acceso denegado.");

$usuario_id = $_SESSION["usuario_id"];
$tipo = $_SESSION["tipo"];
$descripcion = $_POST["descripcion"];
$categoria = $_POST["categoria"];
$monto = $_POST["monto"];
$fecha = $_POST["fecha"];
$fecha_limite = !empty($_POST["fecha_limite"]) ? $_POST["fecha_limite"] : null;

$sql = "INSERT INTO gastos (usuario_id, descripcion, categoria, monto, fecha, tipo, fecha_limite) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("issdsss", $usuario_id, $descripcion, $categoria, $monto, $fecha, $tipo, $fecha_limite);
$stmt->execute();

header("Location: dashboard_$tipo.php");
?>