<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Sistema de Almuerzos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <form action="../login/verificar_login.php" method="POST" class="bg-white shadow-lg p-8 rounded-xl w-80">
        <h2 class="text-2xl font-bold text-center mb-6">Iniciar Sesión</h2>
        <label class="block mb-2">Carnet:</label>
        <input type="text" name="carnet" required class="w-full p-2 mb-4 border rounded">

        <label class="block mb-2">Contraseña:</label>
        <input type="password" name="contrasena" required class="w-full p-2 mb-6 border rounded">

        <button type="submit" class="w-full bg-green-500 text-white p-2 rounded hover:bg-green-600">Entrar</button>
    </form>
</body>
</html>
