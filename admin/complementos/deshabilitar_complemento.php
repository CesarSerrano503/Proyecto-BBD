<?php
session_start();
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol']!=='admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}
$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) die('Error de conexión');

$id     = $_GET['id']     ?? null;
$estado = $_GET['estado'] ?? null;
if ($id!==null && ($estado=== '0' || $estado==='1')) {
    $stmt = $conn->prepare("UPDATE complementos SET activo=? WHERE id_complemento=?");
    $stmt->bind_param('ii',$estado,$id);
    $stmt->execute();
}

header('Location: ../dashboard.php?seccion=complementos');
