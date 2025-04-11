<?php  
session_start();
if (!isset($_SESSION["Usuario"]["Carnet"])) { 
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú del Día</title>
    <?php include_once 'templates/header.php' ?>
</head>
<body class="bg-gray-100">
    <div class="flex flex-col md:flex-row min-h-screen">
        <main class="flex-1 p-6">
            <?php include_once 'templates/nav.php' ?>
            <h1 class="text-2xl font-bold text-center my-6">¡Saludos, <?= $_SESSION["Usuario"]["Nombre"] ?>!</h1>
            <h2 class="text-2xl font-bold text-center my-6">Menú del día:</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 place-items-center">
                <div class="bg-white p-4 shadow-md rounded-lg w-72 text-center">
                    <img src="imgs/huevo.jpg" alt="Huevo estrellado" class="w-64 h-40 object-cover mx-auto rounded">
                    <p class="mt-2 font-semibold">Huevo estrellado con plátano</p>
                    <div class="flex justify-center mt-3 space-x-2">
                        <a href="pedido.php"><button class="bg-green-400 text-white px-4 py-1 rounded">Reservar</button></a>
                        <a href="info.php"><button class="bg-yellow-300 px-4 py-1 rounded">Información</button></a>
                    </div>
                    <p class="mt-2 font-semibold">3.99 USD</p>
                </div>
                <div class="bg-white p-4 shadow-md rounded-lg w-72 text-center">
                    <img src="imgs/carne.jpeg" alt="Carne asada" class="w-64 h-40 object-cover mx-auto rounded">
                    <p class="mt-2 font-semibold">Carne asada con arroz y ensalada</p>
                    <div class="flex justify-center mt-3 space-x-2">
                        <a href="pedido.php"><button class="bg-green-400 text-white px-4 py-1 rounded">Reservar</button></a>
                        <a href="info.php"><button class="bg-yellow-300 px-4 py-1 rounded">Información</button></a>
                    </div>
                    <p class="mt-2 font-semibold">3.99 USD</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById("menu-toggle").addEventListener("click", function() {
            document.getElementById("mobile-menu").classList.toggle("hidden");
        });
    </script>
</body>
</html>
