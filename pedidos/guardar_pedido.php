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
    // si alguien entra por GET, lo mandamos de vuelta al formulario
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
$id_plato    = filter_input(INPUT_POST,'id_plato',    FILTER_VALIDATE_INT);
$precio_base = filter_input(INPUT_POST,'precio_base', FILTER_VALIDATE_FLOAT);
$total       = filter_input(INPUT_POST,'total',       FILTER_VALIDATE_FLOAT);

if (!$id_plato || $precio_base===false || $total===false || $total<=0) {
    echo "<p class='text-red-500 text-center mt-10 font-bold'>❌ Error: datos inválidos.</p>";
    echo "<p class='text-center'><a href='index.php' class='underline text-blue-600'>Volver al menú</a></p>";
    exit;
}

$carnet = $_SESSION['Usuario']['Carnet'];

// ───────── OBTENER NOMBRE Y LÍMITE DEL PLATO ─────────
$stmt = $conn->prepare("SELECT nombre, limite_disponible FROM platos WHERE id_plato=?");
$stmt->bind_param('i',$id_plato);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc() ?: null;
$stmt->close();

if (!$row) {
    die("<p class='text-red-500 text-center mt-10 font-bold'>❌ Plato no encontrado.</p>");
}
$nombre_plato = $row['nombre'];
$limite       = (int)$row['limite_disponible'];

// ───────── CONTAR PEDIDOS HOY ─────────
$stmt = $conn->prepare(
  "SELECT COUNT(*) AS c 
     FROM pedidos 
    WHERE id_plato=? AND fecha_reserva=CURDATE()"
);
$stmt->bind_param('i',$id_plato);
$stmt->execute();
$pedHoy = (int)$stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

if ($limite>0 && $pedHoy >= $limite) {
    echo "<p class='text-red-500 text-center mt-10 font-bold'>
            ❌ Límite alcanzado para «{$nombre_plato}» hoy.
          </p>";
    echo "<p class='text-center'><a href='index.php' class='underline text-blue-600'>
            Volver al menú
          </a></p>";
    exit;
}

// ───────── ARMAR DESCRIPCIÓN ─────────
$items = ["Plato: {$nombre_plato}"];
// complementos tipo select (extra_N)
foreach ($_POST as $k=>$v) {
    if (preg_match('/^extra_(\d+)$/',$k,$m) && ($qty=intval($v))>0) {
        $idc = $m[1];
        $q = $conn->prepare("SELECT nombre, precio FROM complementos WHERE id_complemento=?");
        $q->bind_param('i',$idc);
        $q->execute();
        $r = $q->get_result()->fetch_assoc() ?: [];
        $q->close();
        if (!empty($r)) {
            $items[] = "{$qty}×{$r['nombre']} (\${$r['precio']})";
        }
    }
}
// complementos tipo radio (comp_tipo)
foreach ($_POST as $k=>$v) {
    if (strpos($k,'comp_')===0 && ($idc=intval($v))>0) {
        $q = $conn->prepare("SELECT tipo,nombre,precio FROM complementos WHERE id_complemento=?");
        $q->bind_param('i',$idc);
        $q->execute();
        $r = $q->get_result()->fetch_assoc() ?: [];
        $q->close();
        if (!empty($r)) {
            $label = ucfirst($r['tipo']);
            $items[] = "{$label}: {$r['nombre']} (\${$r['precio']})";
        }
    }
}
$descripcion = implode(' | ',$items);

// ───────── INSERTAR EN pedidos ─────────
$stmt = $conn->prepare(
  "INSERT INTO pedidos
     (carnet_alumno, descripcion_pedido, fecha_reserva, id_plato)
   VALUES (?,?,CURDATE(),?)"
);
$stmt->bind_param('ssi',$carnet,$descripcion,$id_plato);
$stmt->execute();
$id_pedido = $conn->insert_id;
$stmt->close();

// ───────── INSERTAR LINEAS EN pedido_plato ─────────
// plato principal
$stmt = $conn->prepare(
  "INSERT INTO pedido_plato
     (id_pedido,id_plato,id_complemento,monto)
   VALUES (?,?,NULL,?)"
);
$stmt->bind_param('iid',$id_pedido,$id_plato,$precio_base);
$stmt->execute();
$stmt->close();

// extras
foreach ($_POST as $k=>$v) {
    if (preg_match('/^extra_(\d+)$/',$k,$m) && ($qty=intval($v))>0) {
        $idc = $m[1];
        // obtener precio
        $q = $conn->prepare("SELECT precio FROM complementos WHERE id_complemento=?");
        $q->bind_param('i',$idc);
        $q->execute();
        $price = floatval($q->get_result()->fetch_assoc()['precio'] ?? 0);
        $q->close();
        // insertar cada unidad
        for($i=0;$i<$qty;$i++){
            $stmt = $conn->prepare(
              "INSERT INTO pedido_plato
                 (id_pedido,id_plato,id_complemento,monto)
               VALUES (?,?,?,?)"
            );
            $stmt->bind_param('iiid',$id_pedido,$id_plato,$idc,$price);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// radio
foreach ($_POST as $k=>$v) {
    if (strpos($k,'comp_')===0 && ($idc=intval($v))>0) {
        $q = $conn->prepare("SELECT precio FROM complementos WHERE id_complemento=?");
        $q->bind_param('i',$idc);
        $q->execute();
        $price = floatval($q->get_result()->fetch_assoc()['precio'] ?? 0);
        $q->close();
        $stmt = $conn->prepare(
          "INSERT INTO pedido_plato
             (id_pedido,id_plato,id_complemento,monto)
           VALUES (?,?,?,?)"
        );
        $stmt->bind_param('iiid',$id_pedido,$id_plato,$idc,$price);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();

// ───────── ÉXITO ─────────
echo "<p class='text-green-600 text-center mt-10 font-bold'>
        ✅ Pedido registrado.
      </p>";
echo "<p class='text-center'>
        <a href='index.php' class='underline text-blue-600'>Volver al menú</a>
      </p>";
