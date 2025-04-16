<?php
session_start();
// Validación de admin si es necesario
$conn = new mysqli("localhost", "root", "", "reservas_db");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener todos los platos
$platos = $conn->query("SELECT * FROM platos ORDER BY activo DESC, nombre");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Platos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-6xl mx-auto bg-white shadow-lg p-6 rounded-xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">🍽️ Gestión de Platos</h1>
            <a href="agregar_plato.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">+ Nuevo Plato</a>
        </div>

        <table class="w-full text-sm border border-collapse">
            <thead>
                <tr class="bg-gray-200 text-left">
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
                            <td class="p-2"><?= htmlspecialchars($plato['nombre']) ?></td>
                            <td class="p-2"><?= htmlspecialchars($plato['descripcion']) ?></td>
                            <td class="p-2">$<?= number_format($plato['precio'], 2) ?></td>
                            <td class="p-2"><?= $plato['activo'] ? '🟢 Activo' : '🔴 Inactivo' ?></td>
                            <td class="p-2 text-center space-x-2">
                                <a href="editar_plato.php?id=<?= $plato['id_plato'] ?>" class="text-blue-600 hover:underline">Editar</a>
                                <?php if ($plato['activo']): ?>
                                    <a href="deshabilitar_plato.php?id=<?= $plato['id_plato'] ?>&estado=0" class="text-yellow-500 hover:underline">Deshabilitar</a>
                                <?php else: ?>
                                    <a href="deshabilitar_plato.php?id=<?= $plato['id_plato'] ?>&estado=1" class="text-green-600 hover:underline">Habilitar</a>
                                <?php endif; ?>
                                <a href="eliminar_plato.php?id=<?= $plato['id_plato'] ?>" class="text-red-600 hover:underline" onclick="return confirm('¿Estás seguro de eliminar este plato?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="p-4 text-center text-gray-500">No hay platos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
