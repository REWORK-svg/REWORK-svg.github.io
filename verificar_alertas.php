<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
require 'conexion.php';

$hoy = date("Y-m-d");

$sql = "SELECT u.correo, g.descripcion, g.fecha_limite 
        FROM gastos g
        JOIN usuarios u ON g.usuario_id = u.id
        WHERE g.fecha_limite = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hoy);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $correo = $row['correo'];
    $descripcion = $row['descripcion'];

    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'proyectogestor3@gmail.com';        // <-- Cambia esto
        $mail->Password   = 'eewaetdtqfjihqdm';         // <-- Usa una contraseña de aplicación
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Destinatario
        $mail->setFrom('tucorreo@gmail.com', 'Gestor de Gastos');
        $mail->addAddress($correo);

        // Contenido del mensaje
        $mail->isHTML(true);
        $mail->Subject = '🔔 Recordatorio de pago: ' . $descripcion;
        $mail->Body    = "Hola, este es un recordatorio de que <b>hoy vence el pago</b> del gasto: <strong>$descripcion</strong>.<br><br>Por favor, asegúrate de realizar el pago a tiempo.";

        $mail->send();
        echo "Correo enviado a $correo<br>";
    } catch (Exception $e) {
        echo "No se pudo enviar el correo a $correo. Error: {$mail->ErrorInfo}<br>";
    }
}
?>
