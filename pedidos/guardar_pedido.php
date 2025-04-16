<?php
session_start();

if (!isset($_SESSION["Usuario"]["Carnet"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "reservas_db");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Recoger datos del formulario
$carnet = $_SESSION["Usuario"]["Carnet"];
$id_plato = $_POST['id_plato'];
$total = floatval($_POST['total']);
$tortillas = intval($_POST['tortillas']);

// Complementos
$bebida = $_POST['bebida'] ?? 'Sin bebida';
$guarnicion = $_POST['guarnicion'] ?? 'Sin guarnición';
$ensalada = $_POST['ensalada'] ?? 'Sin ensalada';

// Obtener nombre del alumno
$stmtAlumno = $conn->prepare("SELECT nombre FROM alumnos WHERE carnet = ?");
$stmtAlumno->bind_param("s", $carnet);
$stmtAlumno->execute();
$resultAlumno = $stmtAlumno->get_result();
$alumno = $resultAlumno->fetch_assoc();
$nombre_alumno = $alumno['nombre'] ?? 'Alumno desconocido';

// Obtener nombre del plato
$stmtPlato = $conn->prepare("SELECT nombre FROM platos WHERE id_plato = ?");
$stmtPlato->bind_param("i", $id_plato);
$stmtPlato->execute();
$resultPlato = $stmtPlato->get_result();
$plato = $resultPlato->fetch_assoc();
$nombre_plato = $plato['nombre'] ?? 'Plato desconocido';

// Descripción del pedido
$descripcion = "Plato: $nombre_plato | Bebida: $bebida | Guarnición: $guarnicion | Ensalada: $ensalada | Tortillas: $tortillas";

// ID fijo de administrador (puede venir luego de sesión si tenés login de admins)
$id_admin = 1;

// Insertar pedido
$stmt = $conn->prepare("INSERT INTO pedidos (carnet_alumno, descripcion_pedido, monto, id_admin, fecha_reserva) VALUES (?, ?, ?, ?, CURRENT_DATE)");
$stmt->bind_param("ssdi", $carnet, $descripcion, $total, $id_admin);

if ($stmt->execute()) {
    echo "<p class='text-center mt-10 font-bold text-green-600'>✅ Pedido registrado con éxito.</p>";
    echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
} else {
    echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Error al guardar el pedido: " . $stmt->error . "</p>";
}
?>
