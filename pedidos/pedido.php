<?php
session_start();
if (!isset($_SESSION["Usuario"]["Carnet"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "reservas_db");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

//  Validar y preparar ID del plato
$platoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$platoId) {
    die("<p class='text-center text-red-500 mt-10 font-bold'>Error: ID de plato inválido.</p>");
}

//  Consulta preparada para evitar inyección
$stmt = $conn->prepare("SELECT * FROM platos WHERE id_plato = ?");
$stmt->bind_param("i", $platoId);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("<p class='text-center text-red-500 mt-10 font-bold'>Error: Plato no encontrado.</p>");
}

$plato = $resultado->fetch_assoc();
$precioBase = 1.50;
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
        
        <!-- Información del plato -->
        <div class="w-full md:w-1/2">
            <?php if (!empty($plato['imagen'])): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($plato['imagen']) ?>" alt="<?= htmlspecialchars($plato['nombre']) ?>" class="rounded-lg w-full h-48 object-cover">
            <?php else: ?>
                <div class="bg-gray-200 w-full h-48 flex items-center justify-center rounded-lg text-gray-600">Sin imagen</div>
            <?php endif; ?>
            <h2 class="text-xl font-semibold mt-4"><?= htmlspecialchars($plato['nombre']) ?></h2>
            <p class="text-gray-700 mt-2">$<?= number_format($precioBase, 2) ?> USD</p>
        </div>

        <!-- Formulario de pedido -->
        <form action="guardar_pedido.php" method="POST" class="w-full md:w-1/2">
            <input type="hidden" name="id_plato" value="<?= $platoId ?>">
            <input type="hidden" name="precio_base" value="<?= $precioBase ?>">

            <h3 class="text-lg font-bold mb-2">Seleccione complementos para su pedido:</h3>

            <div class="space-y-4">
                <?php
                $complementos = $conn->query("SELECT * FROM complementos ORDER BY tipo, nombre");
                $currentTipo = '';
                while ($comp = $complementos->fetch_assoc()):
                    if ($comp['tipo'] === 'extra') {
                        echo '<div class="mt-4">';
                        echo '<label class="font-semibold block mb-1">Tortillas (0.10 c/u, máximo 5):</label>';
                        echo '<select name="tortillas" id="tortillas" class="p-2 border rounded w-28">';
                        for ($i = 0; $i <= 5; $i++) {
                            echo '<option value="'.$i.'">'.$i.' tortilla'.($i == 1 ? '' : 's').'</option>';
                        }
                        echo '</select>';
                        echo '</div>';
                    } else {
                        if ($currentTipo !== $comp['tipo']) {
                            echo "<p class='font-semibold mt-4 capitalize'>" . htmlspecialchars($comp['tipo']) . "</p>";
                            $currentTipo = $comp['tipo'];
                        }
                        echo '<label class="block ml-2">';
                        echo '<input type="radio" name="' . htmlspecialchars($comp['tipo']) . '" value="' . htmlspecialchars($comp['nombre']) . '" class="mr-2 complemento">';
                        echo htmlspecialchars($comp['nombre']) . ' $' . number_format($comp['precio'], 2);
                        echo '</label>';
                    }
                endwhile;
                ?>
            </div>

            <!-- Total y botón de confirmación -->
            <div class="mt-6">
                <p class="text-lg font-bold">Total: <span id="total">$<?= number_format($precioBase, 2) ?></span></p>
                <input type="hidden" name="total" id="total-hidden" value="<?= $precioBase ?>">
                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded mt-4 hover:bg-green-600">Confirmar pedido</button>
            </div>
        </form>
    </div>

    <script>
        const base = <?= $precioBase ?>;
        const totalSpan = document.getElementById("total");
        const totalHidden = document.getElementById("total-hidden");
        const inputs = document.querySelectorAll(".complemento");
        const tortillaSelect = document.getElementById("tortillas");

        function calcularTotal() {
            let extras = document.querySelectorAll(".complemento:checked").length * 0.25;
            let tortillas = parseInt(tortillaSelect.value) || 0;
            let total = (base + extras + tortillas * 0.10).toFixed(2);
            totalSpan.textContent = `$${total}`;
            totalHidden.value = total;
        }

        inputs.forEach(input => input.addEventListener("change", calcularTotal));
        tortillaSelect.addEventListener("change", calcularTotal);
    </script>

</body>
</html>
