<?php
session_start();

// Si ya hay sesión activa, redirigir directamente
if (isset($_SESSION["Usuario"]["Carnet"])) {
    header("Location: ../pedidos/index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded shadow-lg w-full max-w-md">

        <h2 class="text-2xl font-bold text-center mb-6">Iniciar Sesión</h2>

        <!--  Mensaje de sesión cerrada -->
        <?php if (isset($_GET['cerrado']) && $_GET['cerrado'] == 1): ?>
            <div class="bg-yellow-100 text-yellow-800 px-4 py-2 mb-4 rounded text-center font-semibold">
                 Debes iniciar sesión para continuar.
            </div>
        <?php endif; ?>

        <form method="POST" action="verificar_login.php" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Carnet</label>
                <input type="text" name="carnet" required class="w-full border border-gray-300 p-2 rounded" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" name="contrasena" required class="w-full border border-gray-300 p-2 rounded" />
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Entrar</button>
        </form>
    </div>
</body>
</html>
