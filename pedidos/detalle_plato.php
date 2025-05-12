<?php
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: menu.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

$id_plato = intval($_GET['id']);

$sql = "SELECT nombre, descripcion, precio, imagen FROM platos WHERE id_plato = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_plato);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p class='text-center mt-10 text-red-600'>Platillo no encontrado.</p>";
    exit();
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($row['nombre']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex justify-center items-center min-h-screen p-4">
        <div class="bg-white p-6 shadow-md rounded-lg max-w-lg text-center">
            <?php if (!empty($row['imagen'])): ?>
                <img src="data:image/jpeg;base64,<?= base64_encode($row['imagen']) ?>" alt="<?= htmlspecialchars($row['nombre']) ?>" class="w-full h-60 object-cover mx-auto rounded">
            <?php else: ?>
                <img src="../../imgs/placeholder.jpg" alt="Imagen del platillo" class="w-full h-60 object-cover mx-auto rounded">
            <?php endif; ?>

            <h2 class="mt-4 text-xl font-bold"><?= htmlspecialchars($row['nombre']) ?></h2>
            <p class="mt-2 text-sm text-gray-600"><?= htmlspecialchars($row['descripcion']) ?></p>
            <p class="mt-4 text-lg font-semibold text-green-700">Precio: $<?= number_format($row['precio'], 2) ?></p>

            <button onclick="history.back()" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                Regresar
            </button>
        </div>
    </div>
</body>
</html>
