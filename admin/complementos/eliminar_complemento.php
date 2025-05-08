<?php
session_start();
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol']!=='admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// Conexión a la base de datos y set de usuario para triggers
$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
$adminName = $conn->real_escape_string($_SESSION['Usuario']['Nombre']);
$conn->query("SET @currentAdmin = '{$adminName}';");

// Obtener id
$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $conn->prepare("DELETE FROM complementos WHERE id_complemento = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
}

// Volver al dashboard de complementos
header('Location: ../dashboard.php?seccion=complementos');
exit;
