<?php
// ───────── SESIÓN Y CACHE ─────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// ───────── VERIFICAR SESIÓN ALUMNO ─────────
if (!isset($_SESSION['Usuario']['Carnet']) || $_SESSION['Usuario']['Rol'] !== 'alumno') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// ───────── CONEXIÓN A BBDD ─────────
$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) {
    die("<p class='text-center text-red-500'>Error de conexión: {$conn->connect_error}</p>");
}

// ───────── INYECTAR USUARIO PARA TRIGGERS ─────────
$usuario = $_SESSION['Usuario']['Carnet'];
$conn->query("SET @usuario = '" . $conn->real_escape_string($usuario) . "';");

// ───────── LEER Y VALIDAR POST ─────────
$platoId = filter_input(INPUT_POST, 'id_plato', FILTER_VALIDATE_INT);
$total   = filter_input(INPUT_POST, 'total',    FILTER_VALIDATE_FLOAT);

if (!$platoId || $total === false || $total <= 0) {
    die("<p class='text-center text-red-500'>❌ Datos de pedido inválidos.</p>");
}

// ───────── ARMAR DESCRIPCIÓN ─────────
$items = [];

// Plato principal
$q = $conn->prepare("SELECT nombre FROM platos WHERE id_plato = ?");
$q->bind_param('i', $platoId);
$q->execute();
$row = $q->get_result()->fetch_assoc() ?: [];
$q->close();

$items[] = "Plato: " . ($row['nombre'] ?? "ID {$platoId}");

// Complementos radio
foreach ($_POST as $k => $v) {
    if (strpos($k, 'comp_') === 0 && intval($v) > 0) {
        $idc = intval($v);
        $q = $conn->prepare("SELECT tipo, nombre, precio FROM complementos WHERE id_complemento = ?");
        $q->bind_param('i', $idc);
        $q->execute();
        $c = $q->get_result()->fetch_assoc() ?: [];
        $q->close();
        if ($c) {
            $items[] = ucfirst($c['tipo']) . ": {$c['nombre']} (\${$c['precio']})";
        }
    }
}

// Complementos select múltiple
foreach ($_POST as $k => $v) {
    if (preg_match('/^extra_(\d+)$/', $k, $m) && intval($v) > 0) {
        $idc  = intval($m[1]);
        $qty  = intval($v);
        $q = $conn->prepare("SELECT nombre, precio FROM complementos WHERE id_complemento = ?");
        $q->bind_param('i', $idc);
        $q->execute();
        $c = $q->get_result()->fetch_assoc() ?: [];
        $q->close();
        if ($c) {
            $items[] = "{$qty}×{$c['nombre']} (\${$c['precio']})";
        }
    }
}

$descripcion = implode(' | ', $items);

// ───────── LLAMAR AL SP sp_reservar_plato ─────────
// Asegúrate de que tu procedimiento sp_reservar_plato acepte estos 4 parámetros:
//   IN p_id_plato INT,
//   IN p_carnet_alumno VARCHAR(20),
//   IN p_descripcion TEXT,
//   IN p_monto DECIMAL(10,2)
$stmt = $conn->prepare("CALL sp_reservar_plato(?, ?, ?, ?)");
if (!$stmt) {
    die("<p class='text-center text-red-500'>Error al preparar SP: {$conn->error}</p>");
}
$stmt->bind_param(
    'issd',
    $platoId,
    $usuario,
    $descripcion,
    $total
);

if (! $stmt->execute()) {
    $err = htmlspecialchars($stmt->error);
    $stmt->close();
    die("<p class='text-center text-red-500'>❌ {$err}</p>
         <p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>");
}

$stmt->close();
$conn->close();

// ───────── ÉXITO ─────────
echo "<p class='text-center text-green-600 font-bold mt-10'>✅ Pedido registrado con éxito.</p>";
echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
