<?php
session_start();

// 🔐 Evitar caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
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

// Consultar historial de cambios, incluyendo nombre_tabla
$query = "
    SELECT fecha, usuario, accion, detalle, nombre_tabla
    FROM historial
    ORDER BY fecha DESC
";
$historial = $conn->query($query);
if ($historial === false) {
    die('Error al consultar historial: ' . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Historial de Cambios</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
<div class="flex h-screen">

  <!-- SIDEBAR -->
  <aside class="w-64 bg-white shadow-lg border-r flex flex-col justify-between">
    <div>
      <div class="bg-blue-600 text-white text-center p-4 text-xl font-bold">Admin Panel</div>
      <div class="p-4">
        <div class="text-center mb-6">
          <img src="https://i.pravatar.cc/100" alt="Avatar" class="rounded-full mx-auto mb-2" width="60"/>
          <p class="text-sm font-semibold">Admin</p>
        </div>
        <nav class="space-y-1">
          <a href="dashboard.php?seccion=dashboard" class="block px-4 py-2 rounded text-gray-700 hover:bg-blue-50">📊 Dashboard</a>
          <a href="dashboard.php?seccion=pedidos" class="block px-4 py-2 rounded text-gray-700 hover:bg-blue-50">📝 Pedidos</a>
          <a href="dashboard.php?seccion=platos" class="block px-4 py-2 rounded text-gray-700 hover:bg-blue-50">🍽️ Platos</a>
          <a href="historial.php" class="block px-4 py-2 rounded bg-yellow-50 text-gray-800">🕒 Historial</a>
          <a href="../login/logout.php" class="block px-4 py-2 rounded text-red-600 hover:bg-red-50">🚪 Cerrar sesión</a>
        </nav>
      </div>
    </div>
    <footer class="text-center text-gray-400 text-xs mb-4">© <?= date("Y") ?> Sistema de Reservas</footer>
  </aside>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="flex-1 overflow-y-auto p-8 space-y-6">
    <div class="bg-white p-6 rounded-xl shadow border border-gray-300">
      <h1 class="text-2xl font-semibold flex items-center gap-2 mb-4">🕒 Historial de Cambios</h1>
      <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
          <thead class="bg-gray-100 text-gray-700">
            <tr>
              <th class="p-2 border">Fecha</th>
              <th class="p-2 border">Usuario</th>
              <th class="p-2 border">Acción</th>
              <th class="p-2 border">Tabla</th>
              <th class="p-2 border">Detalle</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($historial->num_rows > 0): ?>
              <?php while ($row = $historial->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="p-2"><?= htmlspecialchars($row['fecha']) ?></td>
                  <td class="p-2"><?= htmlspecialchars($row['usuario']) ?></td>
                  <td class="p-2"><?= htmlspecialchars(ucfirst($row['accion'])) ?></td>
                  <td class="p-2"><?= htmlspecialchars($row['nombre_tabla']) ?></td>
                  <td class="p-2"><?= htmlspecialchars($row['detalle']) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="p-4 text-center text-gray-500">No hay registros de cambios.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
</body>
</html>
