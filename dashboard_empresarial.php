<?php
session_start();
// Redirige si el usuario no ha iniciado sesión o no es de tipo 'empresarial'
if (!isset($_SESSION["usuario_id"]) || $_SESSION["tipo"] != "empresarial") {
    header("Location: login2.php");
    exit();
}
// Incluye el archivo de conexión a la base de datos
include("conexion.php"); //

$usuario_id = $_SESSION["usuario_id"]; //

// --- Obtener datos para el resumen financiero ---
// Consulta SQL para sumar ingresos y gastos del usuario actual
$sql_summary = "SELECT SUM(CASE WHEN categoria = 'Ingreso' THEN monto ELSE 0 END) as total_income,
                SUM(CASE WHEN categoria != 'Ingreso' THEN monto ELSE 0 END) as total_expenses
                FROM gastos
                WHERE usuario_id = ?"; //
$stmt_summary = $conn->prepare($sql_summary); // Prepara la consulta
$stmt_summary->bind_param("i", $usuario_id); // Vincula el ID del usuario
$stmt_summary->execute(); // Ejecuta la consulta
$result_summary = $stmt_summary->get_result(); // Obtiene los resultados
$summary_data = $result_summary->fetch_assoc(); // Obtiene la fila asociada como un array

// Calcula los totales, asegurando que sean 0 si no hay datos
$total_income = $summary_data['total_income'] ?? 0; //
$total_expenses = $summary_data['total_expenses'] ?? 0; //
$net_income = $total_income - $total_expenses; // Calcula la utilidad neta
$savings_rate = ($total_income > 0) ? (($net_income / $total_income) * 100) : 0; // Calcula el margen de ahorro

// --- Obtener historial de gastos ---
// Consulta SQL para obtener todos los gastos (no ingresos) del usuario, ordenados por fecha descendente
$sql_history = "SELECT descripcion, categoria, monto, fecha, fecha_limite FROM gastos WHERE usuario_id = ? AND categoria != 'Ingreso' ORDER BY fecha DESC"; //
$stmt_history = $conn->prepare($sql_history); // Prepara la consulta
$stmt_history->bind_param("i", $usuario_id); // Vincula el ID del usuario
$stmt_history->execute(); // Ejecuta la consulta
$result_history = $stmt_history->get_result(); // Obtiene los resultados
$expenses_history = [];
while ($row = $result_history->fetch_assoc()) {
    $expenses_history[] = $row; // Almacena cada fila en un array
}

// --- Obtener datos para el gráfico por categoría ---
// Consulta SQL para sumar montos por categoría (solo gastos, no ingresos)
$sql_chart = "SELECT categoria, SUM(monto) as total_monto FROM gastos WHERE usuario_id = ? AND categoria != 'Ingreso' GROUP BY categoria"; //
$stmt_chart = $conn->prepare($sql_chart); // Prepara la consulta
$stmt_chart->bind_param("i", $usuario_id); // Vincula el ID del usuario
$stmt_chart->execute(); // Ejecuta la consulta
$result_chart = $stmt_chart->get_result(); // Obtiene los resultados
$chart_data = [];
while ($row = $result_chart->fetch_assoc()) {
    $chart_data[] = $row; // Almacena cada fila en un array
}

$conn->close(); // Cierra la conexión a la base de datos
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestor Empresarial de Gastos</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🏢 Gestor de Gastos - Empresarial</h1>
    </div>

    <div class="main-content">
        <div class="card">
            <h2>📊 Registrar Ingresos</h2>
            <form action="guardar_ingreso.php" method="POST">
                <div class="form-group">
                    <label>Ingresos Mensuales:</label>
                    <input type="number" name="ingreso" placeholder="0.00" step="0.01" required>
                </div>
                <button type="submit" class="btn">Guardar Ingresos</button>
            </form>
        </div>

        <div class="card">
            <h2>💸 Agregar Gasto</h2>
            <form action="guardar_gasto.php" method="POST">
                <div class="form-group">
                    <label>Descripción:</label>
                    <input type="text" name="descripcion" required>
                </div>
                <div class="form-group">
                    <label>Categoría:</label>
                    <select name="categoria" required>
                        <option value="">Seleccionar</option>
                        <option>Materiales</option>
                        <option>Servicios</option>
                        <option>Marketing</option>
                        <option>Oficina</option>
                        <option>Tecnología</option>
                        <option>Personal</option>
                        <option>Transporte</option>
                        <option>Impuestos</option>
                        <option>Otros</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Monto:</label>
                    <input type="number" name="monto" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Fecha del Gasto:</label>
                    <input type="date" name="fecha" required>
                </div>
                <div class="form-group">
                    <label>Fecha Límite de Pago (opcional):</label>
                    <input type="date" name="fecha_limite">
                </div>
                <button type="submit" class="btn">Agregar Gasto</button>
            </form>
        </div>

        <div class="card summary-card">
            <h2>📈 Resumen Financiero</h2>
            <div class="summary-grid">
                <div class="summary-item"><h3 id="totalIncome">$<?php echo number_format($total_income, 2); ?></h3><p>Ingresos Totales</p></div>
                <div class="summary-item"><h3 id="totalExpenses">$<?php echo number_format($total_expenses, 2); ?></h3><p>Gastos Totales</p></div>
                <div class="summary-item"><h3 id="netIncome" class="<?php echo ($net_income < 0) ? 'negative' : 'positive'; ?>">$<?php echo number_format($net_income, 2); ?></h3><p>Utilidad Neta</p></div>
                <div class="summary-item"><h3 id="savingsRate"><?php echo number_format($savings_rate, 2); ?>%</h3><p>Margen de Ahorro</p></div>
            </div>
        </div>

        <div class="card">
            <h2>🗑️ Gestión de Gastos Mensuales</h2>
            <p>Puedes borrar todos los gastos del mes anterior para empezar un nuevo ciclo.</p>
            <form action="borrar_gastos_mes_anterior.php" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres borrar TODOS los gastos del mes anterior? Esta acción no se puede deshacer.');">
                <button type="submit" class="btn delete-btn">Borrar Gastos del Mes Anterior</button>
            </form>
            <?php
            // Mostrar mensaje de confirmación/error si existe en la sesión
            if (isset($_SESSION['mensaje_borrado'])) {
                echo '<p style="color: green; font-weight: bold; margin-top: 10px;">' . $_SESSION['mensaje_borrado'] . '</p>';
                unset($_SESSION['mensaje_borrado']); // Limpiar el mensaje después de mostrarlo
            }
            ?>
        </div>
    </div>

    <div class="card">
        <h2>📋 Historial de Gastos</h2>
        <div class="expenses-list" id="expensesList">
            <?php if (empty($expenses_history)): ?>
                <p style="text-align: center; color: #718096; padding: 20px;">No hay gastos registrados</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Descripción</th>
                            <th>Categoría</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Fecha Límite</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses_history as $expense): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($expense['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($expense['categoria']); ?></td>
                                <td>$<?php echo number_format($expense['monto'], 2); ?></td>
                                <td><?php echo htmlspecialchars($expense['fecha']); ?></td>
                                <td><?php echo htmlspecialchars($expense['fecha_limite'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="charts-section">
        <div class="chart-container">
            <h2>📊 Gastos por Categoría</h2>
            <canvas id="categoryChart" width="400" height="500"></canvas>
            <?php if (empty($chart_data)): ?>
                 <p style="text-align: center; color: #888;">No hay datos suficientes para generar el gráfico.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Alerta para ingresos netos negativos
    const netIncomeElement = document.getElementById('netIncome'); //
    if (netIncomeElement && parseFloat(netIncomeElement.textContent.replace(/[^0-9.-]+/g,"")) < 0) { //
        setTimeout(() => {
            alert("⚠️ Atención: Tus gastos superan los ingresos disponibles."); //
        }, 1000); // Se ejecuta después de cargar los datos
    }

    // Gráfico para Gastos por Categoría
    const chartData = <?php echo json_encode($chart_data); ?>; //
    const categories = chartData.map(item => item.categoria); //
    const amounts = chartData.map(item => parseFloat(item.total_monto)); //

    const ctx = document.getElementById('categoryChart').getContext('2d'); //
    new Chart(ctx, {
        type: 'pie', // Puedes cambiar esto a 'bar' o 'doughnut'
        data: {
            labels: categories, //
            datasets: [{
                data: amounts, //
                backgroundColor: [ // Colores para las categorías
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#CD5C5C', '#7B68EE', '#ADFF2F'
                ],
                hoverOffset: 4 //
            }]
        },
        options: {
            responsive: true, // Hace el gráfico responsivo
            plugins: {
                title: {
                    display: true, // Muestra el título
                    text: 'Distribución de Gastos por Categoría Empresarial' // Texto del título
                }
            }
        }
    });
});
</script>
</body>
</html>