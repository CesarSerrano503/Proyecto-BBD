<?php
// ───────── SESIÓN Y CACHE ─────────
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ───────── ADMIN CHECK ─────────
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// ───────── CONEXIÓN A LA BBDD ─────────
$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// ───────── VALIDAR ID ─────────
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die('ID de plato inválido.');
}

// ───────── INYECTAR USUARIO PARA TRIGGERS ─────────
$adminName = $conn->real_escape_string($_SESSION['Usuario']['Nombre'] ?? 'admin');
$conn->query("SET @usuario = '{$adminName}';");

// ───────── LLAMAR AL SP sp_eliminar_plato ─────────
$stmt = $conn->prepare("CALL sp_eliminar_plato(?, ?)");
if (!$stmt) {
    die('Error al preparar el SP: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('is', $id, $adminName);
if (!$stmt->execute()) {
    die('Error al eliminar el plato: ' . htmlspecialchars($stmt->error));
}
$stmt->close();
$conn->close();

// ───────── REDIRECCIÓN ─────────
header('Location: ../dashboard.php?seccion=platos');
exit;
?>
