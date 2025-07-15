<?php
session_start();
// Incluye el archivo de conexión a la base de datos
include("conexion.php"); //

// Si el usuario no ha iniciado sesión, deniega el acceso
if (!isset($_SESSION["usuario_id"])) {
    header("Location: login.html");
    exit();
}

$usuario_id = $_SESSION["usuario_id"]; // Obtiene el ID del usuario de la sesión

// Calcular el primer y último día del mes anterior
$primer_dia_mes_anterior = date("Y-m-01", strtotime("first day of last month")); //
$ultimo_dia_mes_anterior = date("Y-m-t", strtotime("last day of last month")); //

// Prepara la consulta para borrar gastos del mes anterior, excluyendo registros con categoría 'Ingreso'
$sql_delete = "DELETE FROM gastos WHERE usuario_id = ? AND fecha >= ? AND fecha <= ? AND categoria != 'Ingreso'"; //
$stmt_delete = $conn->prepare($sql_delete); // Prepara la sentencia SQL
$stmt_delete->bind_param("iss", $usuario_id, $primer_dia_mes_anterior, $ultimo_dia_mes_anterior); // Vincula los parámetros

// Ejecuta la consulta y verifica si fue exitosa
if ($stmt_delete->execute()) { //
    $_SESSION['mensaje_borrado'] = "Se han borrado los gastos del mes anterior (" . date("F Y", strtotime("last month")) . ") correctamente."; // Mensaje de éxito
} else {
    $_SESSION['mensaje_borrado'] = "Error al borrar los gastos del mes anterior: " . $stmt_delete->error; // Mensaje de error
}

$stmt_delete->close(); // Cierra la sentencia preparada
$conn->close(); // Cierra la conexión a la base de datos

// Redirige de vuelta al dashboard correspondiente (personal o empresarial)
if ($_SESSION["tipo"] == "personal") { //
    header("Location: dashboard_personal.php"); //
} else {
    header("Location: dashboard_empresarial.php"); //
}
exit(); // Finaliza el script
?>