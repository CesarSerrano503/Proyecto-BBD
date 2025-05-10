<?php
// ───────── SESSION & CACHE ─────────
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ───────── ADMIN CHECK ─────────
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// ───────── DB CONNECTION ─────────
$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// ───────── VALIDATE ID ─────────
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die('ID no válido.');
}

// ───────── SET MYSQL @usuario FOR TRIGGERS ─────────
$adminName = $conn->real_escape_string($_SESSION['Usuario']['Nombre'] ?? 'admin');
$conn->query("SET @usuario = '{$adminName}';");

// ───────── DIRECT DELETE ─────────
$stmt = $conn->prepare('DELETE FROM platos WHERE id_plato = ?');
if (!$stmt) {
    die('Error en la preparación de la consulta: ' . htmlspecialchars($conn->error));
}
$stmt->bind_param('i', $id);
if (!$stmt->execute()) {
    die('Error al eliminar el plato: ' . htmlspecialchars($stmt->error));
}
$stmt->close();
$conn->close();

// ───────── REDIRECT ─────────
header('Location: ../dashboard.php?seccion=platos');
exit;
?>
