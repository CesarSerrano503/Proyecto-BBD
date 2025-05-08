<?php
session_start();
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol']!=='admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) die('Error de conexión');

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ../dashboard.php?seccion=complementos');
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nombre = $_POST['nombre'];
    $tipo   = $_POST['tipo'];
    $precio = $_POST['precio'];
    $activo = isset($_POST['activo']) ? 1 : 0;

    $stmt = $conn->prepare(
      "UPDATE complementos 
         SET nombre = ?, tipo = ?, precio = ?, activo = ?
       WHERE id_complemento = ?"
    );
    $stmt->bind_param('ssdii', $nombre, $tipo, $precio, $activo, $id);
    $stmt->execute();

    header('Location: ../dashboard.php?seccion=complementos');
    exit;
}

// Traer datos existentes
$stmt = $conn->prepare("SELECT nombre, tipo, precio, activo FROM complementos WHERE id_complemento = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
if (!$res) {
    die('Complemento no encontrado');
}

// Opciones fijas de tipo
$tipos = [
    'bebida'     => 'Bebida',
    'guarnicion' => 'Guarnición',
    'ensalada'   => 'Ensalada',
    'extra'      => 'Extra',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Complemento</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 bg-gray-50">
  <h1 class="text-2xl font-bold mb-6">✏️ Editar Complemento</h1>
  <form method="post" class="space-y-4 bg-white p-6 rounded shadow">
    <div>
      <label class="block font-medium mb-1">Nombre:</label>
      <input name="nombre"
             value="<?= htmlspecialchars($res['nombre']) ?>"
             required
             class="border rounded w-full px-3 py-2">
    </div>

    <div>
      <label class="block font-medium mb-1">Tipo:</label>
      <select name="tipo" required class="border rounded w-full px-3 py-2">
        <option value="">-- Selecciona tipo --</option>
        <?php foreach ($tipos as $key => $label): ?>
          <option value="<?= $key ?>"
            <?= $res['tipo'] === $key ? 'selected' : '' ?>>
            <?= $label ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label class="block font-medium mb-1">Precio:</label>
      <input name="precio"
             type="number"
             step="0.01"
             value="<?= htmlspecialchars($res['precio']) ?>"
             required
             class="border rounded w-full px-3 py-2">
    </div>

    <div class="flex items-center">
      <input name="activo"
             id="activo"
             type="checkbox"
             <?= $res['activo'] ? 'checked' : '' ?>
             class="mr-2">
      <label for="activo">Activo</label>
    </div>

    <div class="flex space-x-4">
      <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700">
        Actualizar
      </button>
      <a href="../dashboard.php?seccion=complementos"
         class="px-5 py-2 rounded border text-gray-600 hover:bg-gray-100">
        Cancelar
      </a>
    </div>
  </form>
</body>
</html>
