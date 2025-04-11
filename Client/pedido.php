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
    <title>Pedido</title>
    <?php include_once 'templates/header.php' ?>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex flex-col p-6">
        
        <?php include_once 'templates/nav.php' ?>

        <h2 class="text-xl font-bold text-center m-8">Su código de pedido es:</h2>

        <main class="flex flex-col lg:flex-row p-6 items-center lg:items-start lg:justify-center lg:space-x-6 space-y-6 lg:space-y-0">

            <div class="bg-white p-4 rounded-lg shadow-md w-full max-w-sm text-center">
                <img src="imgs/huevo.jpg" alt="Huevos con plátano" class="w-full h-60 object-cover rounded">
                <h2 class="mt-2 text-lg font-bold">Huevo estrellado con plátano</h2>
                <div class="mt-4 flex justify-center space-x-4">
                    <button class="bg-green-500 text-white px-4 py-2 rounded">Reservar</button>
                    <button class="bg-yellow-500 text-white px-4 py-2 rounded">Información</button>
                </div>
                <p class="mt-2 text-lg font-semibold">3.99 USD</p>
            </div>
            

            <div class="bg-white p-6 rounded-lg shadow-md w-full max-w-md">
                <p class="m-4 font-semibold text-center">Seleccione complementos para su pedido</p>
                <div class="mt-2 space-y-2">
                    <p class="mt-4 font-semibold ">Bebida</p>
                    <label class="flex items-center">
                        <input type="radio" name="bebida" class="mr-2"> Bebida 0.25$
                    </label>
                    <p class="mt-4 font-semibold">Complemento</p>
                    <label class="flex items-center">
                        <input type="radio" name="complemento" class="mr-2"> Arroz 0.25$
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="complemento" class="mr-2"> Casamiento 0.25$
                    </label>
                    <p class="mt-4 font-semibold">Ensalada</p>
                    <label class="flex items-center">
                        <input type="radio" name="ensalada" class="mr-2"> Chirmol 0.25$
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="ensalada" class="mr-2"> Coditos 0.25$
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="ensalada" class="mr-2"> Ensalada fresca 0.25$
                    </label>
                </div>
            </div>
        </main>
        
        <div class="flex justify-center mt-4">
            <button onclick="history.back()" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition mb-8">
                Regresar
            </button>
        </div>
    </div>

    <script>
        document.getElementById("menu-toggle").addEventListener("click", function() {
            document.getElementById("mobile-menu").classList.toggle("hidden");
        });
    </script>
</body>
</html>
