<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reservas_db");
if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

$mensaje = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $carnet = trim($_POST['usuario']);
    $contrasena = trim($_POST['contrasena']);

    if (!$carnet || !$contrasena) {
        $mensaje = "Por favor completa todos los campos.";
    } else {
        // Buscar en alumnos
        $stmtAlum = $conn->prepare("SELECT carnet, nombre FROM alumnos WHERE carnet = ? AND contrasena = ?");
        $stmtAlum->bind_param("ss", $carnet, $contrasena);
        $stmtAlum->execute();
        $resAlum = $stmtAlum->get_result();

        if ($alumno = $resAlum->fetch_assoc()) {
            $_SESSION['Usuario'] = [
                'Carnet' => $alumno['carnet'],
                'Nombre' => $alumno['nombre'],
                'Rol'    => 'alumno'
            ];
            header("Location: ../pedidos/index.php");
            exit();
        }

        // Buscar en administradores
        $stmtAdmin = $conn->prepare("SELECT carnet, nombre_completo FROM administradores WHERE carnet = ? AND contrasena = ?");
        $stmtAdmin->bind_param("ss", $carnet, $contrasena);
        $stmtAdmin->execute();
        $resAdmin = $stmtAdmin->get_result();

        if ($admin = $resAdmin->fetch_assoc()) {
            $_SESSION['Usuario'] = [
                'Carnet' => $admin['carnet'],
                'Nombre' => $admin['nombre_completo'],
                'Rol'    => 'admin'
            ];
            header("Location: ../admin/dashboard.php");
            exit();
        }

        $mensaje = "⚠️ Carnet o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex justify-center items-center h-screen">
<div class="bg-white p-8 rounded shadow-md w-full max-w-sm">
    <h2 class="text-2xl font-bold mb-6 text-center">Iniciar sesión</h2>

    <?php if (isset($_GET['cerrado']) && $_GET['cerrado'] == 1): ?>
        <div class="bg-yellow-100 text-yellow-800 px-4 py-2 mb-4 rounded text-center">
            ⚠️ Debes iniciar sesión para continuar.
        </div>
    <?php endif; ?>

    <?php if ($mensaje): ?>
        <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded text-center"><?= $mensaje ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Carnet (usuario admin)</label>
            <input type="text" name="usuario" required class="mt-1 block w-full border border-gray-300 rounded p-2" />
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Contraseña</label>
            <input type="password" name="contrasena" required class="mt-1 block w-full border border-gray-300 rounded p-2" />
        </div>

        <button type="submit" class="bg-blue-600 text-white w-full py-2 rounded hover:bg-blue-700">Ingresar</button>
    </form>
</div>
</body>
</html>
