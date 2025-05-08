<?php
session_start();
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol']!=='admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}
$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) die('Error de conexión');

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nombre = $_POST['nombre'];
    $tipo   = $_POST['tipo'];
    $precio = $_POST['precio'];
    $activo = isset($_POST['activo']) ? 1 : 0;

    $stmt = $conn->prepare(
      "INSERT INTO complementos (nombre,tipo,precio,activo) VALUES (?,?,?,?)"
    );
    $stmt->bind_param('ssdi',$nombre,$tipo,$precio,$activo);
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
<body class="p-6">
  <h1 class="text-2xl mb-4">+ Agregar Complemento</h1>
  <form method="post" class="space-y-4">
    <div>
      <label class="block">
        Nombre:
        <input name="nombre" required class="border p-1 w-full">
      </label>
    </div>
    <div>
      <label class="block">
        Tipo:
        <select name="tipo" required class="border p-1 w-full">
          <option value="">-- Selecciona --</option>
          <option value="bebida">Bebida</option>
          <option value="guarnicion">Guarnición</option>
          <option value="ensalada">Ensalada</option>
          <option value="extra">Extra</option>
        </select>
      </label>
    </div>
    <div>
      <label class="block">
        Precio:
        <input name="precio" type="number" step="0.01" required class="border p-1 w-full">
      </label>
    </div>
    <div>
      <label>
        <input name="activo" type="checkbox" checked> Activo
      </label>
    </div>
    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Guardar</button>
    <a href="../dashboard.php?seccion=complementos" class="ml-4 text-gray-600">Cancelar</a>
  </form>
</body>
</html>
