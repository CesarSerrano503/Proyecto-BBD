<?php
// ───────── SESIÓN Y CACHE ─────────
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ───────── VERIFICAR SESIÓN COMO ALUMNO ─────────
if (!isset($_SESSION['Usuario']['Carnet']) || $_SESSION['Usuario']['Rol'] !== 'alumno') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// ───────── CONEXIÓN A LA BASE DE DATOS ─────────
$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// ───────── OBTENER PLATOS ACTIVOS ─────────
$stmt = $conn->prepare(
    'SELECT id_plato, nombre, descripcion, precio, imagen
       FROM platos
      WHERE activo = 1
      ORDER BY nombre'
);
$stmt->execute();
$platos = $stmt->get_result();
$stmt->close();

// ───────── RESUMEN DEL PEDIDO PARA HOY ─────────
$carnet = $_SESSION['Usuario']['Carnet'];
$stmt = $conn->prepare(
    'SELECT COUNT(*) AS item_count, COALESCE(SUM(pp.monto),0) AS total
       FROM pedidos p
       JOIN pedido_plato pp ON p.id_pedido = pp.id_pedido
      WHERE p.carnet_alumno = ?
        AND p.fecha_reserva = CURDATE()'
);
$stmt->bind_param('s',$carnet);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$item_count   = $summary['item_count'];
$total_to_pay = $summary['total'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Menú del Día</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">

  <!-- ENCABEZADO -->
  <header class="bg-green-100 px-6 py-4 flex justify-between items-center">
    <div class="flex items-center gap-4">
      <span class="bg-yellow-400 text-black font-bold px-3 py-1 rounded">
        <?= htmlspecialchars($item_count) ?>
      </span>
      <a href="index.php" class="text-gray-800 hover:underline font-medium">Menú del día</a>
      <a href="ver_pedido.php" class="text-gray-800 hover:underline font-medium">Su pedido</a>
    </div>
    <div class="flex items-center gap-4">
      <span class="text-gray-700 font-semibold">
        Total a pagar: $<?= number_format($total_to_pay,2) ?>
      </span>
      <a href="../login/logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md">
        Cerrar sesión
      </a>
    </div>
  </header>

  <!-- LISTADO DE PLATOS -->
  <main class="p-6">
    <h1 class="text-2xl font-bold text-center my-4">
      ¡Hola, <?= htmlspecialchars($_SESSION['Usuario']['Nombre']) ?>!
    </h1>
    <h2 class="text-xl font-semibold mb-6 text-center">Menú del día</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 place-items-center">
      <?php while($plato = $platos->fetch_assoc()): ?>
        <div class="bg-white p-4 shadow-md rounded-lg w-72 text-center">
          <?php if(!empty($plato['imagen'])): ?>
            <img src="data:image/jpeg;base64,<?= base64_encode($plato['imagen']) ?>"
                 alt="<?= htmlspecialchars($plato['nombre']) ?>"
                 class="w-64 h-40 object-cover mx-auto rounded" />
          <?php else: ?>
            <div class="w-64 h-40 bg-gray-200 rounded mb-2"></div>
          <?php endif; ?>
          <p class="mt-2 font-semibold"><?= htmlspecialchars($plato['nombre']) ?></p>
          <p class="mt-1 text-gray-600">$<?= number_format($plato['precio'],2) ?></p>
          <div class="flex justify-center mt-3 space-x-2">
            <a href="pedido.php?id=<?= $plato['id_plato'] ?>">
              <button class="bg-green-400 text-white px-4 py-1 rounded hover:bg-green-500">
                Reservar
              </button>
            </a>
            <a href="detalle_plato.php?id=<?= $plato['id_plato'] ?>">
              <button class="bg-yellow-300 px-4 py-1 rounded hover:bg-yellow-400">
                Información
              </button>
            </a>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </main>

  <!-- EVITAR CACHE EN ATRÁS -->
  <script>
    window.addEventListener('pageshow', e => {
      if (e.persisted) window.location.reload();
    });
  </script>
</body>
</html>
