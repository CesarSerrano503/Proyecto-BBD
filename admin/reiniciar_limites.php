<?php
session_start();
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol']!=='admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: '.$conn->connect_error);
}

// Inyectar usuario para triggers (si tu trigger los registra)
$admin = $conn->real_escape_string($_SESSION['Usuario']['Nombre']);
$conn->query("SET @usuario = '{$admin}';");

// Reiniciar uno o todos
if (isset($_GET['all']) && $_GET['all']==1) {
    $conn->query("CALL sp_reiniciar_todos_limites()");
} elseif (!empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("CALL sp_reiniciar_limite_plato(?)");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header('Location: ../admin/dashboard.php?seccion=platos');
exit;
