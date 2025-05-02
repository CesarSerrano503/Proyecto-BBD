<?php
session_start();

// 🔐 Evitar caché navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar sesión de administrador
if (!isset($_SESSION["Usuario"]["Rol"]) || $_SESSION["Usuario"]["Rol"] !== 'admin') {
    header("Location: ../login/login.php?cerrado=1");
    exit();
}

$conn = new mysqli("localhost", "root", "", "reservas_db");
if ($conn->connect_error) die("Error: " . $conn->connect_error);

// Filtro de fechas
$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');

$stmt = $conn->prepare("SELECT p.*, a.nombre AS alumno_nombre FROM pedidos p JOIN alumnos a ON p.carnet_alumno = a.carnet WHERE p.fecha_reserva BETWEEN ? AND ? ORDER BY p.fecha_reserva DESC");
$stmt->bind_param("ss", $desde, $hasta);
$stmt->execute();
$pedidos = $stmt->get_result();

$platos = $conn->query("SELECT * FROM platos ORDER BY activo DESC, nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Panel de Administración</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    html { scroll-behavior: smooth; }
  </style>
</head>
<body class="bg-gray-100 font-sans">
<div class="flex h-screen">

  <!-- SIDEBAR -->
  <aside class="w-64 bg-white shadow-lg border-r flex flex-col justify-between">
    <div>
      <div class="bg-blue-600 text-white text-center p-4 text-xl font-bold">Admin Panel</div>
      <div class="p-4">
        <div class="text-center mb-6">
          <img src="https://i.pravatar.cc/100" class="rounded-full mx-auto mb-2" width="60">
          <p class="text-sm font-semibold">Admin</p>
        </div>
        <nav class="space-y-2">
          <button onclick="mostrarSeccion('dashboard')" class="block w-full text-left px-4 py-2 hover:bg-blue-100">📊 Dashboard</button>
          <button onclick="mostrarSeccion('pedidos')" class="block w-full text-left px-4 py-2 hover:bg-blue-100">📝 Pedidos</button>
          <button onclick="mostrarSeccion('platos')" class="block w-full text-left px-4 py-2 hover:bg-blue-100">🍽️ Platos</button>
          <a href="../login/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-100">🚪 Cerrar sesión</a>
        </nav>
      </div>
    </div>
    <footer class="text-center text-gray-400 text-xs mb-4">© <?= date("Y") ?> Sistema de Reservas</footer>
  </aside>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="flex-1 overflow-y-auto p-8 space-y-10">

    <!-- DASHBOARD -->
    <section id="seccion-dashboard" class="seccion bg-white p-6 rounded-xl shadow border border-gray-300">
      <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">📋 Panel de Administración</h1>
      <p class="text-sm text-gray-500">Bienvenido/a, puedes gestionar los pedidos y platos desde aquí.</p>
    </section>

    <!-- PEDIDOS -->
    <section id="seccion-pedidos" class="seccion hidden bg-white p-6 rounded-xl shadow border border-gray-300">
      <h2 class="text-xl font-semibold flex items-center gap-2 mb-4">📝 Pedidos realizados</h2>

      <form method="GET" class="flex gap-4 items-center mb-4">
        <input type="hidden" name="seccion" value="pedidos">
        <label class="flex items-center gap-1">
          Desde:
          <input type="date" name="desde" value="<?= $desde ?>" class="border rounded p-1">
        </label>
        <label class="flex items-center gap-1">
          Hasta:
          <input type="date" name="hasta" value="<?= $hasta ?>" class="border rounded p-1">
        </label>
        <button class="bg-blue-500 text-white px-4 py-1 rounded hover:bg-blue-600">Filtrar</button>
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
              <?php while($p = $pedidos->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="p-2"><?= $p['fecha_reserva'] ?></td>
                  <td class="p-2"><?= $p['alumno_nombre'] ?></td>
                  <td class="p-2"><?= $p['carnet_alumno'] ?></td>
                  <td class="p-2"><?= $p['descripcion_pedido'] ?></td>
                  <td class="p-2 text-right">$<?= number_format($p['monto'], 2) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5" class="p-4 text-center text-gray-500">No hay pedidos en este rango.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <!-- PLATOS -->
    <section id="seccion-platos" class="seccion hidden bg-white p-6 rounded-xl shadow border border-gray-300">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold flex items-center gap-2">🍽️ Gestión de Platos</h2>
        <a href="agregar_plato.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">+ Agregar plato</a>
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
            <?php if ($platos->num_rows > 0): ?>
              <?php while ($plato = $platos->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="p-2">
                    <?php if (!empty($plato['imagen'])): ?>
                      <img src="data:image/jpeg;base64,<?= base64_encode($plato['imagen']) ?>" class="w-20 h-16 object-cover rounded" alt="Imagen">
                    <?php else: ?>
                      <span class="text-gray-400 italic">Sin imagen</span>
                    <?php endif; ?>
                  </td>
                  <td class="p-2"><?= htmlspecialchars($plato['nombre']) ?></td>
                  <td class="p-2"><?= htmlspecialchars($plato['descripcion']) ?></td>
                  <td class="p-2">$<?= number_format($plato['precio'], 2) ?></td>
                  <td class="p-2"><?= $plato['activo'] ? '🟢 Activo' : '🔴 Inactivo' ?></td>
                  <td class="p-2 text-center space-x-2">
                    <a href="editar_plato.php?id=<?= $plato['id_plato'] ?>" class="text-blue-600 hover:underline">Editar</a>
                    <?php if ($plato['activo']): ?>
                      <a href="deshabilitar_plato.php?id=<?= $plato['id_plato'] ?>&estado=0" class="text-yellow-600 hover:underline">Deshabilitar</a>
                    <?php else: ?>
                      <a href="deshabilitar_plato.php?id=<?= $plato['id_plato'] ?>&estado=1" class="text-green-600 hover:underline">Habilitar</a>
                    <?php endif; ?>
                    <a href="eliminar_plato.php?id=<?= $plato['id_plato'] ?>" onclick="return confirm('¿Eliminar este plato?')" class="text-red-600 hover:underline">Eliminar</a>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="p-4 text-center text-gray-500">No hay platos registrados.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</div>

<script>
function mostrarSeccion(id) {
  const secciones = document.querySelectorAll('.seccion');
  secciones.forEach(sec => sec.classList.add('hidden'));
  document.getElementById('seccion-' + id).classList.remove('hidden');
}
const urlParams = new URLSearchParams(window.location.search);
const seccionActiva = urlParams.get('seccion');
if (seccionActiva) {
  mostrarSeccion(seccionActiva);
} else {
  mostrarSeccion('dashboard');
}
</script>

</body>
</html>
