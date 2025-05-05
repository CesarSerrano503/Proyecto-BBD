<?php
// ───────── SESSION & CACHE SETTINGS ─────────
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ───────── SESSION VERIFICATION ─────────
if (!isset($_SESSION['Usuario']['Carnet']) || $_SESSION['Usuario']['Rol'] !== 'alumno') {
    header('Location: ../login/login.php?cerrado=1');
    exit();
}

// ───────── DATABASE CONNECTION ─────────
$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// ───────── FETCH ACTIVE DISHES ─────────
$stmt = $conn->prepare(
    'SELECT id_plato, nombre, descripcion, precio, imagen
     FROM platos
     WHERE activo = 1
     ORDER BY nombre'
);
$stmt->execute();
$platos = $stmt->get_result();
$stmt->close();

// ───────── USER ORDER SUMMARY FOR TODAY ─────────
$carnet = $_SESSION['Usuario']['Carnet'];
$stmt2 = $conn->prepare(
    'SELECT COUNT(*) AS item_count, COALESCE(SUM(monto),0) AS total
     FROM pedidos
     WHERE carnet_alumno = ?
       AND fecha_reserva = CURDATE()'
);
$stmt2->bind_param('s', $carnet);
$stmt2->execute();
$res2 = $stmt2->get_result();
$summary = $res2->fetch_assoc();
$item_count   = $summary['item_count'];
$total_to_pay = $summary['total'];
$stmt2->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú del Día</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- HEADER -->
    <header class="bg-green-100 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <!-- Badge: number of items -->
            <span class="bg-yellow-400 text-black font-bold px-3 py-1 rounded"><?= htmlspecialchars($item_count) ?></span>
            <a href="index.php" class="text-gray-800 hover:underline font-medium">Menú del día</a>
            <a href="su_pedido.php" class="text-gray-800 hover:underline font-medium">Su pedido</a>
        </div>
        <div class="flex items-center gap-4">
            <!-- Total to pay -->
            <span class="text-gray-700 font-semibold">
                Total a pagar: $<?= number_format($total_to_pay, 2) ?>
            </span>
            <a href="../login/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md">Cerrar sesión</a>
        </div>
    </header>

    <!-- MAIN CONTENT: DISHES -->
    <main class="p-6">
        <h1 class="text-2xl font-bold text-center my-4">¡Hola, <?= htmlspecialchars($_SESSION['Usuario']['Nombre']) ?>!</h1>
        <h2 class="text-xl font-semibold text-center mb-6">Menú del día:</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 place-items-center">
            <?php while ($plato = $platos->fetch_assoc()): ?>
                <div class="bg-white p-4 shadow-md rounded-lg w-72 text-center">
                    <!-- Image -->
                    <?php if (!empty($plato['imagen'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($plato['imagen']) ?>"
                             alt="<?= htmlspecialchars($plato['nombre']) ?>"
                             class="w-64 h-40 object-cover mx-auto rounded" />
                    <?php else: ?>
                        <div class="w-64 h-40 bg-gray-200 rounded mb-2"></div>
                    <?php endif; ?>
                    <!-- Name and Price -->
                    <p class="mt-2 font-semibold"><?= htmlspecialchars($plato['nombre']) ?></p>
                    <p class="mt-1 text-gray-600">$<?= number_format($plato['precio'], 2) ?> USD</p>
                    <!-- Actions -->
                    <div class="flex justify-center mt-3 space-x-2">
                        <a href="pedido.php?id=<?= $plato['id_plato'] ?>">
                            <button class="bg-green-400 text-white px-4 py-1 rounded hover:bg-green-500">Reservar</button>
                        </a>
                        <a href="info.php?id=<?= $plato['id_plato'] ?>">
                            <button class="bg-yellow-300 px-4 py-1 rounded hover:bg-yellow-400">Información</button>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

    <!-- PREVENT CACHE ON BACK BUTTON -->
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) window.location.reload();
        });
    </script>
</body>
</html>