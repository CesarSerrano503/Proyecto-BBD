<?php
// ───────── SESSION & CACHE ─────────
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ───────── ADMIN CHECK ─────────
if (!isset($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit();
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
$usuario = $_SESSION['Usuario']['Nombre'] ?? 'admin';
$conn->query(
    "SET @usuario = '" . $conn->real_escape_string($usuario) . "'"
);

// ───────── CALL STORED PROCEDURE ─────────
if ($stmt = $conn->prepare('CALL sp_eliminar_plato(?, ?)')) {
    $stmt->bind_param('is', $id, $usuario);
    if ($stmt->execute()) {
        // Redirigir de vuelta al dashboard de administración
        header('Location: ../dashboard.php?seccion=platos');
        exit();
    } else {
        echo 'Error al eliminar el plato: ' . htmlspecialchars($stmt->error);
    }
    $stmt->close();
} else {
    echo 'Error al preparar el procedimiento almacenado: ' . htmlspecialchars($conn->error);
}

$conn->close();
?>
