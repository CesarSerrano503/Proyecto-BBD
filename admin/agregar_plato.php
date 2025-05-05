<?php
// Iniciar sesión y evitar caché para mostrar siempre datos actuales
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Verificar permisos de administrador
if (!isset($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit();
}

// Conexión a la base de datos
$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

$errors = [];
// Valores iniciales para repoblar en caso de error
$nombre = '';
$descripcion = '';
$precio = '';
$limite = '';
$activo = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Recoger y sanitizar entradas de texto
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // 2) Validar campos numéricos
    $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $limite = filter_input(INPUT_POST, 'limite_disponible', FILTER_VALIDATE_INT);
    
    // 3) Checkbox de estado
    $activo = isset($_POST['activo']) ? 1 : 0;

    // ─ Validaciones básicas ─
    if ($nombre === '')      $errors[] = 'El nombre del plato es obligatorio.';
    if ($descripcion === '') $errors[] = 'La descripción es obligatoria.';
    if ($precio === false || $precio <= 0) $errors[] = 'El precio debe ser un número mayor a 0.';
    if ($limite === false || $limite < 0)  $errors[] = 'El límite disponible debe ser un entero ≥ 0.';

    // ─ Validación de imagen ─
    if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'La imagen del plato es obligatoria y debe subirse correctamente.';
    } else {
        $archivo = $_FILES['imagen'];
        $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
        $type = mime_content_type($archivo['tmp_name']);
        if (!in_array($type, $permitidos)) {
            $errors[] = 'Solo se permiten imágenes JPG, PNG o WEBP.';
        } elseif ($archivo['size'] > 2 * 1024 * 1024) {
            $errors[] = 'La imagen no debe superar los 2MB.';
        } else {
            $imagenBinaria = file_get_contents($archivo['tmp_name']);
        }
    }

    // Si todo es válido, llamar al SP para crear plato
    if (empty($errors)) {
        // Nombre de usuario para auditoría
        $usuario = $_SESSION['Usuario']['Nombre'] ?? 'admin';
        
        // Llamada al procedimiento almacenado
        $sql = "CALL sp_crear_plato(?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $null = null;  // placeholder para blob
        
        /* bind_param:
           s = string, d = double, i = integer, b = blob
           Parámetros: nombre, descripción, precio, límite, activo, imagen, usuario */
        $stmt->bind_param(
            'ssdiiis',
            $nombre,
            $descripcion,
            $precio,
            $limite,
            $activo,
            $null,
            $usuario
        );
        // Enviar BLOB de imagen al índice 5
        $stmt->send_long_data(5, $imagenBinaria);

        if ($stmt->execute()) {
            $stmt->close();
            // Redirigir al dashboard en sección Platos
            header('Location: dashboard.php?seccion=platos');
            exit();
        } else {
            $errors[] = 'Error al ejecutar sp_crear_plato: ' . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Agregar Plato</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
    <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">➕ Agregar nuevo plato</h2>

    <!-- Mostrar errores si existen -->
    <?php if (!empty($errors)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded">
        <ul class="list-disc list-inside">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Formulario de alta -->
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Nombre del plato</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required
               class="mt-1 block w-full border rounded p-2" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Descripción</label>
        <textarea name="descripcion" rows="3" required
                  class="mt-1 block w-full border rounded p-2"><?= htmlspecialchars($descripcion) ?></textarea>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Precio ($)</label>
          <input type="number" step="0.01" min="0" name="precio" value="<?= htmlspecialchars($precio) ?>" required
                 class="mt-1 block w-full border rounded p-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Límite disponible</label>
          <input type="number" min="0" name="limite_disponible" value="<?= htmlspecialchars($limite) ?>" required
                 class="mt-1 block w-full border rounded p-2" />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Imagen del plato</label>
        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" required
               class="mt-1 block w-full	border rounded p-2" />
      </div>

      <div class="flex items-center gap-2">
        <input type="checkbox" name="activo" id="activo" <?= $activo ? 'checked' : '' ?>
               class="rounded border-gray-300" />
        <label for="activo" class="text-sm text-gray-700">Activo</label>
      </div>

      <div class="flex justify-between mt-6">
        <a href="dashboard.php?seccion=platos" class="text-gray-600 hover:underline">⬅ Cancelar</a>
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">Guardar</button>
      </div>
    </form>
  </div>
</body>
</html>
