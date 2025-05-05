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

// ───────── VALIDATE & FETCH DISH ─────────
$platoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$platoId) {
    die("<p class='text-center text-red-500 mt-10 font-bold'>Error: ID de plato inválido.</p>");
}
$stmt = $conn->prepare("SELECT id_plato, nombre, descripcion, precio, imagen, limite_disponible FROM platos WHERE id_plato = ?");
$stmt->bind_param("i", $platoId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("<p class='text-center text-red-500 mt-10 font-bold'>Error: Plato no encontrado.</p>");
}
$plato = $result->fetch_assoc();
$stmt->close();

// ───────── COUNT ORDERS TODAY ─────────
$stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM pedidos WHERE id_plato = ? AND fecha_reserva = CURDATE()");
$stmtCount->bind_param("i", $platoId);
$stmtCount->execute();
$countResult = $stmtCount->get_result();
$pedidosHoy = (int)$countResult->fetch_assoc()['total'];
$stmtCount->close();

// ───────── CALCULATE AVAILABILITY ─────────
$limite = (int)$plato['limite_disponible'];
$disponibles = $limite > 0 ? max(0, $limite - $pedidosHoy) : 'Sin límite';
$bloqueado = $limite > 0 && $pedidosHoy >= $limite;

// ───────── FETCH COMPLEMENTS ─────────
$complementos = $conn->query("SELECT id_complemento, nombre, tipo, precio FROM complementos ORDER BY tipo, nombre");
$extras = [];
$otros = [];
while ($comp = $complementos->fetch_assoc()) {
    if ($comp['tipo'] === 'extra') {
        $extras[] = $comp;
    } else {
        $otros[$comp['tipo']][] = $comp;
    }
}

// Base price from database
$precioBase = (float)$plato['precio'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservar Pedido</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6 min-h-screen">

    <div class="max-w-4xl mx-auto bg-white shadow-md rounded-xl p-6 flex flex-col md:flex-row justify-between gap-6">

        <!-- Dish Info -->
        <div class="w-full md:w-1/2">
            <?php if (!empty($plato['imagen'])): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($plato['imagen']) ?>" alt="<?= htmlspecialchars($plato['nombre']) ?>" class="rounded-lg w-full h-48 object-cover" />
            <?php else: ?>
                <div class="bg-gray-200 w-full h-48 flex items-center justify-center rounded-lg text-gray-600">Sin imagen</div>
            <?php endif; ?>
            <h2 class="text-xl font-semibold mt-4"><?= htmlspecialchars($plato['nombre']) ?></h2>
            <p class="text-gray-700 mt-2">Precio base: $<?= number_format($precioBase, 2) ?> USD</p>
            <p class="text-gray-500 mt-1 text-sm">Disponibles hoy: <?= htmlspecialchars($disponibles) ?></p>
        </div>

        <!-- Order Form -->
        <?php if ($bloqueado): ?>
            <div class="w-full md:w-1/2 flex items-center justify-center text-center">
                <p class="text-red-500 font-bold text-lg">❌ Límite de pedidos alcanzado para este plato hoy.</p>
            </div>
        <?php else: ?>
            <form action="guardar_pedido.php" method="POST" class="w-full md:w-1/2 space-y-4">
                <input type="hidden" name="id_plato" value="<?= $platoId ?>" />
                <input type="hidden" name="precio_base" id="precio_base" value="<?= $precioBase ?>" />

                <!-- Extras (e.g., tortillas) -->
                <?php if (!empty($extras)): ?>
                    <?php foreach ($extras as $extra): ?>
                        <div>
                            <label class="font-semibold block mb-1"><?= htmlspecialchars($extra['nombre']) ?> ($<?= number_format($extra['precio'],2) ?> c/u)</label>
                            <select name="extra_<?= $extra['id_complemento'] ?>" id="extra_<?= $extra['id_complemento'] ?>" data-price="<?= $extra['precio'] ?>" class="p-2 border rounded w-28 cantidad">
                                <?php for ($i=0; $i<=5; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Other complements: one selection per type -->
                <?php foreach ($otros as $tipo => $comps): ?>
                    <p class="font-semibold mt-4 capitalize"><?= htmlspecialchars($tipo) ?>:</p>
                    <?php foreach ($comps as $comp): ?>
                        <label class="block ml-2">
                            <input type="radio" name="comp_<?= htmlspecialchars($tipo) ?>" class="complemento" data-price="<?= $comp['precio'] ?>" value="<?= $comp['id_complemento'] ?>" />
                            <?= htmlspecialchars($comp['nombre']) ?> ($<?= number_format($comp['precio'],2) ?>)
                        </label>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <!-- Total and submit -->
                <div class="mt-6">
                    <p class="text-lg font-bold">Total: <span id="total">$<?= number_format($precioBase,2) ?></span></p>
                    <input type="hidden" name="total" id="total-hidden" value="<?= number_format($precioBase,2) ?>" />
                    <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">Confirmar pedido</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
        const base = parseFloat(document.getElementById('precio_base').value);
        const totalSpan = document.getElementById('total');
        const totalHidden = document.getElementById('total-hidden');
        const complementoInputs = document.querySelectorAll('.complemento');
        const cantidadSelects = document.querySelectorAll('.cantidad');

        function calcularTotal() {
            let total = base;
            // extras quantity
            cantidadSelects.forEach(sel => {
                const cantidad = parseInt(sel.value) || 0;
                const precio = parseFloat(sel.dataset.price) || 0;
                total += cantidad * precio;
            });
            // selected complements
            complementoInputs.forEach(inp => {
                if (inp.checked) {
                    total += parseFloat(inp.dataset.price) || 0;
                }
            });
            total = total.toFixed(2);
            totalSpan.textContent = '$' + total;
            totalHidden.value = total;
        }
        complementoInputs.forEach(i => i.addEventListener('change', calcularTotal));
        cantidadSelects.forEach(s => s.addEventListener('change', calcularTotal));
    </script>

</body>
</html>
