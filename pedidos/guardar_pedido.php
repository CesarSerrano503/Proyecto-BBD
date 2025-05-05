<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar sesión de alumno
if (!isset($_SESSION['Usuario']['Carnet']) || $_SESSION['Usuario']['Rol'] !== 'alumno') {
    header('Location: ../login/login.php?cerrado=1');
    exit();
}

// Conexión a DB
$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Recoger y validar POST
$carnet    = $_SESSION['Usuario']['Carnet'];
$id_plato  = filter_input(INPUT_POST, 'id_plato', FILTER_VALIDATE_INT);
$total     = filter_input(INPUT_POST, 'total', FILTER_VALIDATE_FLOAT);
$tortillas = filter_input(INPUT_POST, 'tortillas', FILTER_VALIDATE_INT);

if (!$id_plato || !$total || $total <= 0) {
    echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Error: datos de pedido inválidos.</p>";
    echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
    exit();
}

// Obtener datos del plato y límite
$stmt = $conn->prepare("SELECT nombre, limite_disponible FROM platos WHERE id_plato = ?");
$stmt->bind_param('i', $id_plato);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("<p class='text-center text-red-500 mt-10 font-bold'>❌ Plato no encontrado.</p>");
}
$plato = $res->fetch_assoc();
$stmt->close();
$nombre_plato = $plato['nombre'];
$limite       = (int)$plato['limite_disponible'];

// Contar pedidos hoy para disponibilidad
$stmtCnt = $conn->prepare("SELECT COUNT(*) AS total FROM pedidos WHERE id_plato = ? AND fecha_reserva = CURDATE()");
$stmtCnt->bind_param('i', $id_plato);
$stmtCnt->execute();
$resCnt = $stmtCnt->get_result();
$pedHoy = $resCnt->fetch_assoc()['total'];
$stmtCnt->close();
if ($limite > 0 && $pedHoy >= $limite) {
    echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Límite de pedidos alcanzado para $nombre_plato hoy.</p>";
    echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
    exit();
}

// Construir descripción con complementos dinámicos
$items = ["Plato: $nombre_plato", "Tortillas: $tortillas"];
foreach ($_POST as $key => $val) {
    // complementos tipo radio: comp_<tipo>
    if (strpos($key, 'comp_') === 0 && intval($val) > 0) {
        $compId = intval($val);
        $q = $conn->prepare("SELECT tipo, nombre FROM complementos WHERE id_complemento = ?");
        $q->bind_param('i', $compId);
        $q->execute();
        $r = $q->get_result();
        if ($row = $r->fetch_assoc()) {
            $tipo = ucfirst($row['tipo']);
            $items[] = "$tipo: {$row['nombre']}";
        }
        $q->close();
    }
    // extras tipo select: extra_<id>
    if (preg_match('/^extra_(\d+)$/', $key, $m) && intval($val) > 0) {
        $compId = intval($m[1]);
        $cantidad = intval($val);
        $q = $conn->prepare("SELECT nombre FROM complementos WHERE id_complemento = ?");
        $q->bind_param('i', $compId);
        $q->execute();
        $r = $q->get_result();
        if ($row = $r->fetch_assoc()) {
            $items[] = "$cantidad x {$row['nombre']}";
        }
        $q->close();
    }
}
$descripcion = implode(' | ', $items);

// Insertar pedido
$stmtIns = $conn->prepare(
    "INSERT INTO pedidos (carnet_alumno, id_plato, descripcion_pedido, monto, fecha_reserva)
     VALUES (?, ?, ?, ?, CURDATE())"
);
$stmtIns->bind_param('sisd', $carnet, $id_plato, $descripcion, $total);
if ($stmtIns->execute()) {
    echo "<p class='text-center mt-10 font-bold text-green-600'>✅ Pedido registrado con éxito.</p>";
    echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
} else {
    echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Error al guardar: {$stmtIns->error}</p>";
}
$stmtIns->close();
?>
