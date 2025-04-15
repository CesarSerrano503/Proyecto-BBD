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

$platos = $conn->query("SELECT * FROM platos");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú del Día</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Encabezado -->
    <header class="bg-green-100 px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <span class="bg-yellow-400 text-black font-bold px-3 py-1 rounded">2</span>
            <a href="index.php" class="text-gray-800 hover:underline font-medium">Menú del día</a>
            <a href="su_pedido.php" class="text-gray-800 hover:underline font-medium">Su pedido</a>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-gray-700 font-semibold">Total a pagar: $3.99</span>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md">Cerrar sesión</a>
        </div>
    </header>

    <!-- Contenido principal -->
    <main class="p-6">
        <h1 class="text-2xl font-bold text-center my-4">¡Saludos, <?= htmlspecialchars($_SESSION["Usuario"]["Nombre"]) ?>!</h1>
        <h2 class="text-xl font-semibold text-center mb-6">Menú del día:</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 place-items-center">
            <?php while($plato = $platos->fetch_assoc()): ?>
                <div class="bg-white p-4 shadow-md rounded-lg w-72 text-center">
                    <img src="imgs/<?= htmlspecialchars($plato['imagen']) ?>" alt="<?= htmlspecialchars($plato['nombre']) ?>" class="w-64 h-40 object-cover mx-auto rounded">
                    <p class="mt-2 font-semibold"><?= htmlspecialchars($plato['nombre']) ?></p>
                    <div class="flex justify-center mt-3 space-x-2">
                        <!-- usamos id en el enlace -->
                        <a href="pedido.php?id=<?= $plato['id_plato'] ?>">
                            <button class="bg-green-400 text-white px-4 py-1 rounded">Reservar</button>
                        </a>
                        <a href="info.php?id=<?= $plato['id_plato'] ?>">
                            <button class="bg-yellow-300 px-4 py-1 rounded">Información</button>
                        </a>
                    </div>
                    <p class="mt-2 font-semibold">$<?= number_format($plato['precio'], 2) ?> USD</p>
                </div>
            <?php endwhile; ?>
        </div>
    </main>
</body>
</html>
