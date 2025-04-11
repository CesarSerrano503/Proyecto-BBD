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
    <title>Detalle del Platillo</title>
    <?php include_once 'templates/header.php' ?>
</head>
<body class="bg-gray-100">
    <div class="flex flex-col md:flex-row min-h-screen">

        <main class="flex-1 p-6">

            <?php include_once 'templates/nav.php' ?>

            <h1 class="text-2xl font-bold text-center my-6">Detalle del platillo</h1>
            <div class="flex justify-center">
                <div class="bg-white p-6 shadow-md rounded-lg max-w-lg text-center">
                    <img src="imgs/carne.jpeg" alt="Carne asada" class="w-full h-60 object-cover mx-auto rounded">
                    <h2 class="mt-4 text-xl font-bold">Carne asada con arroz y ensalada</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Disfruta de una deliciosa y jugosa carne asada, sazonada con especias seleccionadas y cocinada a la perfección en la parrilla...
                    </p>
                    <p class="mt-4 text-lg font-semibold">Precio: 3.99 USD</p>
                    
                    <button onclick="history.back()" class="mt-4 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">
                        Regresar
                    </button>
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
