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

// Validar parámetros
if (!isset($_GET['id'], $_GET['estado'])) {
    die('Parámetros inválidos.');
}

$id = intval($_GET['id']);
$estado = intval($_GET['estado']) === 1 ? 1 : 0;

// Actualizar estado del plato
$stmt = $conn->prepare('UPDATE platos SET activo = ? WHERE id_plato = ?');
if (!$stmt) {
    die('Error en la preparación de la consulta.');
}
$stmt->bind_param('ii', $estado, $id);

if ($stmt->execute()) {
    // Registrar en historial
    $usuario = $_SESSION['Usuario']['Nombre'] ?? 'admin';
    $accion = $estado ? 'habilitar' : 'deshabilitar';
    // Obtener nombre del plato para mayor detalle
    $res = $conn->prepare('SELECT nombre FROM platos WHERE id_plato = ?');
    $res->bind_param('i', $id);
    $res->execute();
    $res->bind_result($nombrePlato);
    $res->fetch();
    $res->close();

    $detalle = ucfirst($accion) . " plato: " . ($nombrePlato ?? "ID $id");
    $hist = $conn->prepare("INSERT INTO historial (fecha, usuario, accion, detalle) VALUES (NOW(), ?, ?, ?)");
    $hist->bind_param('sss', $usuario, $accion, $detalle);
    $hist->execute();

    header('Location: dashboard.php?seccion=platos');
    exit();
} else {
    echo 'Error al cambiar el estado del plato.';
}
?>
