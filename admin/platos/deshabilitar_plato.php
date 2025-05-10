<?php
session_start();

// 🔐 Evitar caché navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar sesión de administrador
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// Inyectar el nombre del admin en MySQL para que el trigger lo use
$admin = $conn->real_escape_string($_SESSION['Usuario']['Nombre']);
$conn->query("SET @usuario = '{$admin}';");

// Validar parámetros
if (!isset($_GET['id'], $_GET['estado'])) {
    die('Parámetros inválidos.');
}

$id     = (int) $_GET['id'];
$estado = ((int) $_GET['estado'] === 1) ? 1 : 0;

// Preparar y ejecutar la actualización
if ($stmt = $conn->prepare('UPDATE platos SET activo = ? WHERE id_plato = ?')) {
    $stmt->bind_param('ii', $estado, $id);
    if (!$stmt->execute()) {
        die('Error al actualizar estado: ' . htmlspecialchars($stmt->error));
    }
    $stmt->close();
} else {
    die('Error en la preparación de la consulta: ' . htmlspecialchars($conn->error));
}

$conn->close();

// Redirigir de vuelta al dashboard de administración
header('Location: ../dashboard.php?seccion=platos');
exit;
?>
