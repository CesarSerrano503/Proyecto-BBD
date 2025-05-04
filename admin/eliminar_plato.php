<?php
session_start();

// 🔐 Evitar caché navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar sesión de administrador
if (!isset($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit();
}

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// Validar ID de plato
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die('ID no válido.');
}
$id = intval($_GET['id']);

// Obtener nombre del plato antes de eliminar
$stmt = $conn->prepare('SELECT nombre FROM platos WHERE id_plato = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($nombrePlato);
if (!$stmt->fetch()) {
    die('Plato no encontrado.');
}
$stmt->close();

// Eliminar plato
delete_stmt:
$stmt = $conn->prepare('DELETE FROM platos WHERE id_plato = ?');
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    // Registrar en historial
    $usuario = $_SESSION['Usuario']['Nombre'] ?? 'admin';
    $detalle = "Eliminó plato: $nombrePlato";
    $hist = $conn->prepare("INSERT INTO historial (fecha, usuario, accion, detalle) VALUES (NOW(), ?, 'eliminar', ?)");
    $hist->bind_param('ss', $usuario, $detalle);
    $hist->execute();

    header('Location: dashboard.php?seccion=platos');
    exit();
} else {
    echo 'Error al eliminar el plato.';
}
?>
