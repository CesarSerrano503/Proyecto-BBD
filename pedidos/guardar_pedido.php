<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$stmtCnt = $conn->prepare("SELECT COUNT(*) AS total FROM pedidos WHERE fecha_reserva = CURDATE()");
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
    "INSERT INTO pedidos (carnet_alumno, descripcion_pedido, fecha_reserva)
     VALUES (?, ?, CURDATE())"
);
$stmtIns->bind_param('ss', $carnet, $descripcion);
if (!$stmtIns->execute()) {
    echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Error al guardar el pedido: {$stmtIns->error}</p>";
    echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
    exit();
}
$id_pedido = $conn->insert_id;
$stmtIns->close();

// Insertar plato principal sin complemento
$stmtPP = $conn->prepare(
    "INSERT INTO pedido_plato (id_pedido, id_plato, id_complemento, monto)
     VALUES (?, ?, NULL, ?)"
);
$stmtPP->bind_param('iid', $id_pedido, $id_plato, $total);
if (!$stmtPP->execute()) {
    echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Error al guardar el plato principal: {$stmtPP->error}</p>";
    echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
    exit();
}
$stmtPP->close();

// Insertar complementos tipo radio
foreach ($_POST as $key => $val) {
    if (strpos($key, 'comp_') === 0 && intval($val) > 0) {
        $id_complemento = intval($val);

        // Obtener precio del complemento
        $stmtPrecio = $conn->prepare("SELECT precio FROM complementos WHERE id_complemento = ?");
        $stmtPrecio->bind_param('i', $id_complemento);
        $stmtPrecio->execute();
        $resPrecio = $stmtPrecio->get_result();
        $precio = 0;
        if ($row = $resPrecio->fetch_assoc()) {
            $precio = floatval($row['precio']);
        }
        $stmtPrecio->close();

        // Insertar relación
        $stmtPP = $conn->prepare(
            "INSERT INTO pedido_plato (id_pedido, id_plato, id_complemento, monto)
             VALUES (?, ?, ?, ?)"
        );
        $stmtPP->bind_param('iiid', $id_pedido, $id_plato, $id_complemento, $precio);
        if (!$stmtPP->execute()) {
            echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Error al guardar el complemento: {$stmtPP->error}</p>";
            echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
            exit();
        }
        $stmtPP->close();
    }
}

// Insertar extras tipo select múltiple
foreach ($_POST as $key => $val) {
    if (preg_match('/^extra_(\d+)$/', $key, $m) && intval($val) > 0) {
        $id_complemento = intval($m[1]);
        $cantidad = intval($val);

        // Obtener precio unitario
        $stmtPrecio = $conn->prepare("SELECT precio FROM complementos WHERE id_complemento = ?");
        $stmtPrecio->bind_param('i', $id_complemento);
        $stmtPrecio->execute();
        $resPrecio = $stmtPrecio->get_result();
        $precioUnit = 0;
        if ($row = $resPrecio->fetch_assoc()) {
            $precioUnit = floatval($row['precio']);
        }
        $stmtPrecio->close();

        // Insertar uno por cada cantidad
        for ($i = 0; $i < $cantidad; $i++) {
            $stmtPP = $conn->prepare(
                "INSERT INTO pedido_plato (id_pedido, id_plato, id_complemento, monto)
                 VALUES (?, ?, ?, ?)"
            );
            $stmtPP->bind_param('iiid', $id_pedido, $id_plato, $id_complemento, $precioUnit);
            if (!$stmtPP->execute()) {
                echo "<p class='text-center mt-10 text-red-500 font-bold'>❌ Error al guardar el extra: {$stmtPP->error}</p>";
                echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
                exit();
            }
            $stmtPP->close();
        }
    }
}

// Mensaje de éxito
echo "<p class='text-center mt-10 font-bold text-green-600'>✅ Pedido registrado con éxito.</p>";
echo "<p class='text-center'><a href='index.php' class='text-blue-600 underline'>Volver al menú</a></p>";
?>
