<?php
session_start();
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// Conexión a la base de datos y set de usuario para triggers
$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
// Inyectar el nombre del admin en MySQL para que los triggers lo usen
$adminName = $conn->real_escape_string($_SESSION['Usuario']['Nombre']);
$conn->query("SET @currentAdmin = '{$adminName}';");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $tipo   = $_POST['tipo'];
    $precio = $_POST['precio'];
    $activo = isset($_POST['activo']) ? 1 : 0;

      $stmt = $conn->prepare("CALL sp_crear_complemento(?,?,?,?)");
    $stmt->bind_param('ssdi', $nombre, $tipo, $precio, $activo);
    $stmt->execute();


    header('Location: ../dashboard.php?seccion=complementos');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Agregar Complemento</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-6 bg-gray-50">
  <h1 class="text-2xl font-bold mb-6">+ Agregar Complemento</h1>
  <form method="post" class="space-y-4 bg-white p-6 rounded shadow">
    <div>
      <label class="block font-medium mb-1">Nombre:</label>
      <input name="nombre" required class="border rounded w-full px-3 py-2">
    </div>

    <div>
      <label class="block font-medium mb-1">Tipo:</label>
      <select name="tipo" required class="border rounded w-full px-3 py-2">
        <option value="">-- Selecciona tipo --</option>
        <option value="bebida">Bebida</option>
        <option value="guarnicion">Guarnición</option>
        <option value="ensalada">Ensalada</option>
        <option value="extra">Extra</option>
      </select>
    </div>

    <div>
      <label class="block font-medium mb-1">Precio:</label>
      <input name="precio" type="number" step="0.01" required class="border rounded w-full px-3 py-2">
    </div>

    <div class="flex items-center">
      <input id="activo" name="activo" type="checkbox" checked class="mr-2">
      <label for="activo">Activo</label>
    </div>

    <div class="flex space-x-4">
      <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded hover:bg-green-700">
        Guardar
      </button>
      <a href="../dashboard.php?seccion=complementos"
         class="px-5 py-2 rounded border text-gray-600 hover:bg-gray-100">
        Cancelar
      </a>
    </div>
  </form>
</body>
</html>
