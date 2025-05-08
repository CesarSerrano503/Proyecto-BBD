
<?php
session_start();

// 🔐 Evitar caché del navegador\ nheader("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar sesión de administrador
if (!isset($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit();
}

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// ───────────── Sección y filtros ─────────────
$seccion = $_GET['seccion'] ?? 'dashboard';
// valores de filtro: rango por defecto últimos 7 días
$fdesde = $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days'));
$fhasta = $_GET['hasta'] ?? date('Y-m-d');

// ───────────── Pedidos ─────────────
$pedidos = null;
if ($seccion === 'pedidos') {
    // Consulta directa con LEFT JOIN y COALESCE
    $sqlPedidos = sprintf(
        "SELECT
             p.fecha_reserva AS fecha,
             a.nombre AS alumno,
             p.carnet_alumno AS carnet,
             p.descripcion_pedido AS descripcion,
             COALESCE(SUM(pp.monto),0) AS monto
           FROM pedidos p
           JOIN alumnos a ON p.carnet_alumno = a.carnet
           LEFT JOIN pedido_plato pp ON pp.id_pedido = p.id_pedido
          WHERE p.fecha_reserva BETWEEN '%s' AND '%s'
          GROUP BY p.id_pedido
          ORDER BY p.fecha_reserva DESC",
        $conn->real_escape_string($fdesde),
        $conn->real_escape_string($fhasta)
    );
    $pedidos = $conn->query($sqlPedidos);
    if (!$pedidos) {
        die('Error al obtener pedidos: ' . $conn->error);
    }
}

// ───────────── Platos ─────────────
$platos = $conn->query('SELECT * FROM platos ORDER BY activo DESC, nombre');
if (!$platos) {
    die('Error al obtener platos: ' . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Administración</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
<div class="flex h-screen">

  <!-- SIDEBAR -->
  <aside class="w-64 bg-white shadow-lg border-r flex flex-col justify-between">
    <div>
      <div class="bg-blue-600 text-white text-center p-4 text-xl font-bold">Admin Panel</div>
      <nav class="p-4 space-y-1">
        <a href="dashboard.php?seccion=dashboard" class="block px-4 py-2 rounded hover:bg-blue-50 <?= $seccion==='dashboard'?'bg-blue-100':'' ?>">📊 Dashboard</a>
        <a href="dashboard.php?seccion=pedidos" class="block px-4 py-2 rounded hover:bg-blue-50 <?= $seccion==='pedidos'?'bg-blue-100':'' ?>">📝 Pedidos</a>
        <a href="dashboard.php?seccion=platos" class="block px-4 py-2 rounded hover:bg-blue-50 <?= $seccion==='platos'?'bg-blue-100':'' ?>">🍽️ Platos</a>
        <a href="historial.php" class="block px-4 py-2 rounded hover:bg-blue-50">🕒 Historial</a>
        <a href="../login/logout.php" class="block px-4 py-2 rounded text-red-600 hover:bg-red-50">🚪 Cerrar sesión</a>
      </nav>
    </div>
    <footer class="text-center text-gray-400 text-xs mb-4">© <?= date('Y') ?> Sistema de Reservas</footer>
  </aside>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="flex-1 overflow-y-auto p-8">

    <!-- DASHBOARD -->
    <section class="<?= $seccion==='dashboard'?'':'hidden' ?>">
      <h1 class="text-3xl font-bold mb-4">Panel de Administración</h1>
      <p class="text-gray-600">Bienvenido/a, utiliza el menú para navegar entre secciones.</p>
    </section>

    <!-- PEDIDOS -->
    <section class="<?= $seccion==='pedidos'?'':'hidden' ?>">
      <h2 class="text-2xl font-semibold mb-4">📝 Pedidos realizados</h2>
      <!-- Formulario de filtro -->
      <form method="GET" class="flex gap-4 mb-6">
        <input type="hidden" name="seccion" value="pedidos">
        <label>Desde: <input type="date" name="desde" value="<?= htmlspecialchars($fdesde) ?>" class="border rounded px-2 py-1"></label>
        <label>Hasta: <input type="date" name="hasta" value="<?= htmlspecialchars($fhasta) ?>" class="border rounded px-2 py-1"></label>
        <button class="bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">Filtrar</button>
      </form>
      <div class="overflow-x-auto border rounded">
        <table class="w-full text-sm text-left">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="p-2 border">Fecha</th>
              <th class="p-2 border">Alumno</th>
              <th class="p-2 border">Carnet</th>
              <th class="p-2 border">Descripción</th>
              <th class="p-2 border text-right">Monto ($)</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($pedidos->num_rows > 0): ?>
              <?php while ($p = $pedidos->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="p-2"><?= htmlspecialchars($p['fecha']) ?></td>
                  <td class="p-2"><?= htmlspecialchars($p['alumno']) ?></td>
                  <td class="p-2"><?= htmlspecialchars($p['carnet']) ?></td>
                  <td class="p-2"><?= htmlspecialchars($p['descripcion']) ?></td>
                  <td class="p-2 text-right">$<?= number_format($p['monto'],2) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5" class="p-4 text-center text-gray-500">No hay pedidos en ese rango.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- PLATOS -->
    <section class="<?= $seccion==='platos'?'':'hidden' ?> mt-8">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-semibold">🍽️ Gestión de Platos</h2>
        <a href="platos/agregar_plato.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">+ Agregar plato</a>
      </div>
      <div class="overflow-x-auto border rounded">
        <table class="w-full text-sm text-left">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="p-2 border">Imagen</th>
              <th class="p-2 border">Nombre</th>
              <th class="p-2 border">Descripción</th>
              <th class="p-2 border">Precio</th>
              <th class="p-2 border">Estado</th>
              <th class="p-2 border text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($platos->num_rows > 0): while ($pl = $platos->fetch_assoc()): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="p-2"><?php if (!empty($pl['imagen'])): ?><img src="data:image/jpeg;base64,<?= base64_encode($pl['imagen']) ?>" class="w-20 h-16 object-cover rounded"><?php else: ?><span class="italic text-gray-400">Sin imagen</span><?php endif; ?></td>
                <td class="p-2"><?= htmlspecialchars($pl['nombre']) ?></td>
                <td class="p-2"><?= htmlspecialchars($pl['descripcion']) ?></td>
                <td class="p-2">$<?= number_format($pl['precio'],2) ?></td>
                <td class="p-2"><?= $pl['activo'] ? '🟢 Activo' : '🔴 Inactivo' ?></td>
                <td class="p-2 text-center space-x-2">
                  <a href="platos/editar_plato.php?id=<?= $pl['id_plato'] ?>" class="text-blue-600 hover:underline">Editar</a>
                  <?php if ($pl['activo']): ?><a href="platos/deshabilitar_plato.php?id=<?= $pl['id_plato'] ?>&estado=0" class="text-yellow-600 hover:underline">Deshabilitar</a><?php else: ?><a href="platos/deshabilitar_plato.php?id=<?= $pl['id_plato'] ?>&estado=1" class="text-green-600 hover:underline">Habilitar</a><?php endif; ?>
                  <a href="platos/eliminar_plato.php?id=<?= $pl['id_plato'] ?>" onclick="return confirm('¿Eliminar este plato?')" class="text-red-600 hover:underline">Eliminar</a>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="6" class="p-4 text-center text-gray-500">No hay platos registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>
</body>
</html>

