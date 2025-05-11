<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

// ───────── SESIÓN Y CACHE ─────────
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ───────── SOLO POST ─────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $id = intval($_GET['id'] ?? 0);
    header("Location: pedido.php?id={$id}");
    exit;
}

// ───────── VERIFICAR SESIÓN ─────────
if (!isset($_SESSION['Usuario']['Carnet']) || $_SESSION['Usuario']['Rol']!=='alumno') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// ───────── CONEXIÓN ─────────
$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) {
    die("Error de conexión: ".$conn->connect_error);
}

// ───────── INYECTAR USUARIO PARA TRIGGERS ─────────
$usuario = $conn->real_escape_string($_SESSION['Usuario']['Carnet']);
$conn->query("SET @usuario = '{$usuario}';");

// ───────── LEER Y VALIDAR POST ─────────
$id_plato = filter_input(INPUT_POST,'id_plato',FILTER_VALIDATE_INT);
$total     = filter_input(INPUT_POST,'total',FILTER_VALIDATE_FLOAT);

if (!$id_plato || $total===false || $total<=0) {
    echo "<p class='text-red-500 text-center mt-10 font-bold'>❌ Error: datos inválidos.</p>";
    echo "<p class='text-center'><a href='index.php' class='underline text-blue-600'>Volver al menú</a></p>";
    exit;
}

// ───────── LLAMADA AL PROCEDIMIENTO sp_reservar_plato ─────────
$stmt = $conn->prepare("CALL sp_reservar_plato(?, ?)");
if (!$stmt) {
    die("Error al preparar sp_reservar_plato: " . htmlspecialchars($conn->error));
}
$stmt->bind_param('is', $id_plato, $usuario);

try {
    $stmt->execute();
    echo "<p class='text-green-600 text-center mt-10 font-bold'>✅ Reserva registrada con éxito.</p>";
    echo "<p class='text-center'><a href='index.php' class='underline text-blue-600'>Volver al menú</a></p>";
} catch (mysqli_sql_exception $e) {
    echo "<p class='text-red-500 text-center mt-10 font-bold'>❌ " 
         . htmlspecialchars($e->getMessage()) 
         . "</p>";
    echo "<p class='text-center'><a href='pedido.php?id={$id_plato}' class='underline text-blue-600'>Volver</a></p>";
}

$stmt->close();
$conn->close();
?>
