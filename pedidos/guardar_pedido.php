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

// Validar y sanitizar entradas
$carnet = $_SESSION["Usuario"]["Carnet"];
$id_plato = filter_input(INPUT_POST, 'id_plato', FILTER_VALIDATE_INT);
$total = filter_input(INPUT_POST, 'total', FILTER_VALIDATE_FLOAT);
$tortillas = filter_input(INPUT_POST, 'tortillas', FILTER_VALIDATE_INT);

$bebida = htmlspecialchars(trim($_POST['bebida'] ?? 'Sin bebida'));
$guarnicion = htmlspecialchars(trim($_POST['guarnicion'] ?? 'Sin guarnición'));
$ensalada = htmlspecialchars(trim($_POST['ensalada'] ?? 'Sin ensalada'));

if (!$id_plato || !$total || $total <= 0) {
    echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Error: datos de pedido inválidos.</p>";
    echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
    exit();
}

// ✅ Obtener límite y verificar disponibilidad
$stmtPlato = $conn->prepare("SELECT nombre, limite FROM platos WHERE id_plato = ?");
$stmtPlato->bind_param("i", $id_plato);
$stmtPlato->execute();
$resultPlato = $stmtPlato->get_result();
$plato = $resultPlato->fetch_assoc();

if (!$plato) {
    echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Error: plato no encontrado.</p>";
    exit();
}

$nombre_plato = $plato['nombre'];
$limite = $plato['limite'];

// Contar pedidos del día
$stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM pedidos WHERE id_plato = ? AND fecha_reserva = CURDATE()");
$stmtCount->bind_param("i", $id_plato);
$stmtCount->execute();
$countResult = $stmtCount->get_result();
$pedidosHoy = $countResult->fetch_assoc()['total'];

if ($limite > 0 && $pedidosHoy >= $limite) {
    echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Límite de pedidos alcanzado para el plato <strong>$nombre_plato</strong> hoy.</p>";
    echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
    exit();
}

// Obtener nombre del alumno
$stmtAlumno = $conn->prepare("SELECT nombre FROM alumnos WHERE carnet = ?");
$stmtAlumno->bind_param("s", $carnet);
$stmtAlumno->execute();
$resultAlumno = $stmtAlumno->get_result();
$alumno = $resultAlumno->fetch_assoc();
$nombre_alumno = $alumno['nombre'] ?? 'Alumno desconocido';

// Descripción del pedido
$descripcion = "Plato: $nombre_plato | Bebida: $bebida | Guarnición: $guarnicion | Ensalada: $ensalada | Tortillas: $tortillas";

// ID del administrador (puede venir por sesión más adelante)
$id_admin = 1;

// Insertar pedido (ahora incluye id_plato en la tabla pedidos)
$stmt = $conn->prepare("INSERT INTO pedidos (carnet_alumno, descripcion_pedido, monto, id_admin, fecha_reserva, id_plato) VALUES (?, ?, ?, ?, CURRENT_DATE, ?)");
$stmt->bind_param("ssdii", $carnet, $descripcion, $total, $id_admin, $id_plato);

if ($stmt->execute()) {
    echo "<p class='text-center mt-10 font-bold text-green-600'>✅ Pedido registrado con éxito.</p>";
    echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
} else {
    echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Error al guardar el pedido: " . $stmt->error . "</p>";
}
?>
