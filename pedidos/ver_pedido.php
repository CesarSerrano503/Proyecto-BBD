<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['Usuario']['Carnet']) || $_SESSION['Usuario']['Rol'] !== 'alumno') {
    header('Location: ../login/login.php?cerrado=1');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

$carnet = $_SESSION['Usuario']['Carnet'];

// ✅ Consulta con cálculo del total (precio * cantidad)
$sql = "SELECT p.id_pedido, p.fecha_reserva, pl.nombre AS plato, p.descripcion_pedido AS Descripción, p.monto AS total
        FROM pedidos p
        INNER JOIN platos pl ON p.id_plato = pl.id_plato
        WHERE p.carnet_alumno = ?
        ORDER BY p.fecha_reserva ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $carnet);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6 min-h-screen">
    <div class="max-w-4xl mx-auto bg-white shadow-md rounded-xl p-6">
        <h1 class="text-2xl font-bold mb-4 text-gray-700">📋 Mis Pedidos</h1>

        <?php if ($result->num_rows > 0): ?>
            <table class="w-full table-auto border-collapse">
                <thead>
                    <tr class="bg-gray-200 text-left text-sm">
                        <th class="px-4 py-2">Fecha</th>
                        <th class="px-4 py-2">Plato</th>
                        <th class="px-4 py-2">Descripción</th>
                        <th class="px-4 py-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2"><?= htmlspecialchars($row['fecha_reserva']) ?></td>
                            <td class="px-4 py-2"><?= htmlspecialchars($row['plato']) ?></td>
                            <td class="px-4 py-2">$<?= htmlspecialchars($row['Descripción'], 2) ?></td>
                            <td class="px-4 py-2">$<?= number_format($row['total'], 2) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500 text-center mt-6">No has realizado ningún pedido aún.</p>
        <?php endif; ?>

        <button onclick="history.back()" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                Regresar
            </button>
    </div>
</body>
</html>
