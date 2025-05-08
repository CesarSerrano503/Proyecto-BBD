<?php
session_start();
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// Conexión a la base de datos
$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// Inyectar en MySQL el nombre del admin para los triggers
$adminName = $conn->real_escape_string($_SESSION['Usuario']['Nombre']);
$conn->query("SET @currentAdmin = '{$adminName}';");

// Obtener parámetros
$id     = isset($_GET['id'])     ? (int)$_GET['id']     : null;
$estado = isset($_GET['estado']) ? (int)$_GET['estado'] : null;

// Validar y ejecutar actualización
if ($id !== null && ($estado === 0 || $estado === 1)) {
    $stmt = $conn->prepare(
        "UPDATE complementos 
           SET activo = ? 
         WHERE id_complemento = ?"
    );
    $stmt->bind_param('ii', $estado, $id);
    $stmt->execute();
}

// Redirigir de vuelta al listado de complementos
header('Location: ../dashboard.php?seccion=complementos');
exit;
