<?php
session_start();

// Evitar caché del navegador
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

// Sección actual
$seccion = $_GET['seccion'] ?? 'dashboard';

// Filtros de fecha para pedidos
$desde = $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days'));
$hasta = $_GET['hasta'] ?? date('Y-m-d');

// Obtener datos de pedidos
$pedidos = [];
if ($seccion === 'pedidos') {
    $sql = "SELECT 
                p.fecha_reserva AS fecha, 
                a.nombre         AS alumno, 
                p.carnet_alumno  AS carnet, 
                p.descripcion_pedido AS descripcion, 
                COALESCE(SUM(pp.monto),0) AS monto
            FROM pedidos p
            JOIN alumnos a ON p.carnet_alumno = a.carnet
            LEFT JOIN pedido_plato pp ON pp.id_pedido = p.id_pedido
            WHERE p.fecha_reserva BETWEEN ? AND ?
            GROUP BY p.id_pedido
            ORDER BY p.fecha_reserva DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $desde, $hasta);
    $stmt->execute();
    $pedidos = $stmt->get_result();
}

// Obtener platos
define('DEFAULT_ORDER', 'activo DESC, nombre');
$platos = $conn->query("SELECT * FROM platos ORDER BY " . DEFAULT_ORDER);

// Obtener complementos (corrección incluida)
$complementos = $conn->query(
  'SELECT id_complemento, nombre, tipo, precio, activo FROM complementos ORDER BY id_complemento'
);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">
<div class="flex h-screen">
    <!-- SIDEBAR -->
    <aside class="w-64 bg-white shadow-lg border-r flex flex-col justify-between">
        <div>
            <div class="bg-blue-600 text-white text-center p-4 text-xl font-bold">Admin Panel</div>
            <nav class="p-4 space-y-2">
                <a href="dashboard.php?seccion=dashboard"
                   class="block px-4 py-2 rounded hover:bg-blue-50 <?= $seccion === 'dashboard' ? 'bg-blue-100' : '' ?>">
                    📊 Dashboard
                </a>
                <a href="dashboard.php?seccion=pedidos"
                   class="block px-4 py-2 rounded hover:bg-blue-50 <?= $seccion === 'pedidos' ? 'bg-blue-100' : '' ?>">
                    📝 Pedidos
                </a>
                <a href="dashboard.php?seccion=platos"
                   class="block px-4 py-2 rounded hover:bg-blue-50 <?= $seccion === 'platos' ? 'bg-blue-100' : '' ?>">
                    🍽 Platos
                </a>
                <a href="dashboard.php?seccion=complementos"
                   class="block px-4 py-2 rounded hover:bg-blue-50 <?= $seccion === 'complementos' ? 'bg-blue-100' : '' ?>">
                    🧩 Complementos
                </a>
                <a href="historial.php" class="block px-4 py-2 rounded hover:bg-blue-50">🕒 Historial</a>
                <a href="../login/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50">🚪 Cerrar sesión</a>
            </nav>
        </div>
        <footer class="text-center text-gray-400 text-xs mb-4">© <?= date('Y') ?></footer>
    </aside>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="flex-1 overflow-y-auto p-8">
        <!-- Dashboard -->
        <section class="<?= $seccion === 'dashboard' ? '' : 'hidden' ?>">
            <h1 class="text-3xl font-bold mb-4">Panel de Administración</h1>
            <p class="text-gray-600">Bienvenido/a, selecciona una opción del menú.</p>
        </section>

        <!-- Pedidos -->
        <section class="<?= $seccion === 'pedidos' ? '' : 'hidden' ?>">
            <h2 class="text-2xl font-semibold mb-4">📝 Pedidos</h2>
            <form method="get" class="flex items-center gap-4 mb-6">
                <input type="hidden" name="seccion" value="pedidos">
                <input type="date" name="desde" value="<?= $desde ?>" class="border px-2 py-1 rounded">
                <input type="date" name="hasta" value="<?= $hasta ?>" class="border px-2 py-1 rounded">
                <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded">Filtrar</button>
            </form>
            <div class="overflow-x-auto border rounded">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 uppercase text-gray-600">
                        <tr>
                            <th class="p-2">Fecha</th>
                            <th class="p-2">Alumno</th>
                            <th class="p-2">Carnet</th>
                            <th class="p-2">Descripción</th>
                            <th class="p-2 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($pedidos && $pedidos->num_rows): ?>
                            <?php while ($row = $pedidos->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2"><?= htmlspecialchars($row['fecha']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['alumno']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['carnet']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($row['descripcion']) ?></td>
                                    <td class="p-2 text-right">$<?= number_format($row['monto'], 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-4 text-center text-gray-500">No hay registros.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Platos -->
        <section class="<?= $seccion === 'platos' ? '' : 'hidden' ?> mt-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold">🍽 Platos</h2>
                <a href="platos/agregar_plato.php" class="bg-green-600 text-white px-4 py-1 rounded">+ Agregar</a>
            </div>
            <div class="overflow-x-auto border rounded">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 uppercase text-gray-600">
                        <tr>
                            <th class="p-2">Imagen</th>
                            <th class="p-2">Nombre</th>
                            <th class="p-2">Descripción</th>
                            <th class="p-2">Precio</th>
                            <th class="p-2">Estado</th>
                            <th class="p-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($platos->num_rows): ?>
                            <?php while ($pl = $platos->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2">
                                        <?php if ($pl['imagen']): ?>
                                            <img src="data:image/jpeg;base64,<?= base64_encode($pl['imagen']) ?>" class="w-16 h-12 rounded">
                                        <?php else: ?>
                                            <span class="italic text-gray-400">--</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-2"><?= htmlspecialchars($pl['nombre']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($pl['descripcion']) ?></td>
                                    <td class="p-2">$<?= number_format($pl['precio'], 2) ?></td>
                                    <td class="p-2"><?= $pl['activo'] ? 'Activo' : 'Inactivo' ?></td>
                                    <td class="p-2 space-x-2">
                                        <a href="platos/editar_plato.php?id=<?= $pl['id_plato'] ?>" class="text-blue-600">Editar</a>
                                        <a href="platos/deshabilitar_plato.php?id=<?= $pl['id_plato'] ?>&estado=<?= $pl['activo'] ? 0 : 1 ?>"
                                           class="text-yellow-600">
                                            <?= $pl['activo'] ? 'Deshabilitar' : 'Habilitar' ?>
                                        </a>
                                        <a href="platos/eliminar_plato.php?id=<?= $pl['id_plato'] ?>" class="text-red-600" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="p-4 text-center text-gray-500">Sin registros.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Complementos -->
        <section class="<?= $seccion === 'complementos' ? '' : 'hidden' ?> mt-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold">🧩 Complementos</h2>
                <a href="complementos/agregar_complemento.php" class="bg-green-600 text-white px-4 py-1 rounded">+ Agregar</a>
            </div>
            <div class="overflow-x-auto border rounded">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-100 uppercase text-gray-600">
                        <tr>
                            <th class="p-2">ID</th>
                            <th class="p-2">Nombre</th>
                            <th class="p-2">Tipo</th>
                            <th class="p-2">Precio</th>
                            <th class="p-2">Estado</th>
                            <th class="p-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($complementos->num_rows): ?>
                            <?php while ($c = $complementos->fetch_assoc()): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2"><?= htmlspecialchars($c['id_complemento']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($c['nombre']) ?></td>
                                    <td class="p-2"><?= htmlspecialchars($c['tipo']) ?></td>
                                    <td class="p-2">$<?= number_format($c['precio'], 2) ?></td>
                                    <td class="p-2"><?= $c['activo'] ? 'Activo' : 'Inactivo' ?></td>
                                    <td class="p-2 space-x-2">
                                        <a href="../admin/complementos/editar_complemento.php?id=<?= $c['id_complemento'] ?>" class="text-blue-600">Editar</a>
                                        <a href="../admin/complementos/deshabilitar_complemento.php?id=<?= $c['id_complemento'] ?>&estado=<?= $c['activo'] ? 0 : 1 ?>"
                                           class="text-yellow-600">
                                            <?= $c['activo'] ? 'Deshabilitar' : 'Habilitar' ?>
                                        </a>
                                        <a href="../admin/complementos/eliminar_complemento.php?id=<?= $c['id_complemento'] ?>" class="text-red-600" onclick="return confirm('¿Eliminar?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="p-4 text-center text-gray-500">Sin registros.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>
</body>
</html>
