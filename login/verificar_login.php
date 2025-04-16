<?php
session_start();
$conn = new mysqli("localhost", "root", "", "reservas_db");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$carnet = $_POST['carnet'];
$contrasena = $_POST['contrasena'];

// Consulta
$stmt = $conn->prepare("SELECT * FROM alumnos WHERE carnet = ? AND contrasena = ?");
$stmt->bind_param("ss", $carnet, $contrasena);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $usuario = $result->fetch_assoc();
    $_SESSION["Usuario"] = [
        "Carnet" => $usuario["carnet"],
        "Nombre" => $usuario["nombre"],
        "Grado" => $usuario["grado"],
        "Seccion" => $usuario["seccion"]
    ];
    header("Location: ../pedidos/index.php");
} else {
    echo "<p class='text-center text-red-500 mt-10'>❌ Carnet o contraseña incorrectos</p>";
    echo "<p class='text-center'><a href='login.php' class='text-blue-600 underline'>Volver</a></p>";
}
