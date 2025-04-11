<?php
session_start();
if (isset($_SESSION["Usuario"]["Carnet"])) { 
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    <?php include_once 'templates/header.php' ?>
</head>
<body class="flex items-center justify-center min-h-screen bg-green-100">
    <div class="flex flex-col md:flex-row bg-white rounded-lg shadow-lg overflow-hidden max-w-4xl w-full m-10">
        <div class="p-8 md:w-1/2 flex flex-col justify-center">
            <h2 class="text-gray-700 text-lg font-semibold">Bienvenido a Cafetería Domingo Savio</h2>
            <h1 class="text-2xl font-bold mt-2">Iniciar Sesión</h1>
            <form class="m-6" method="POST">
                <label class="block text-gray-600">Carnet</label>
                <input type="text" name="carnet" class="w-full mt-1 p-2 border rounded bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                
                <label class="block text-gray-600 mt-4">Contraseña</label>
                <input type="password" name ="contra" class="w-full mt-1 p-2 border rounded bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-400" required>
                
                <button class="w-full mt-6 bg-green-500 text-white py-2 rounded hover:bg-green-600 transition" name="login" >Iniciar Sesión</button>
            </form>
        </div>
        <div class="hidden md:block md:w-1/2 bg-green-300 flex items-center justify-center ">
            <img src="imgs/Domingo.jpeg" alt="Imagen de bienvenida" class="shadow-lg w-screen p-8 rounded-lg">
        </div>
    </div>
    <?php include_once 'php/loginConsultas.php' ?>
</body>
</html>