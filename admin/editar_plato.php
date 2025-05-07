<?php
// Iniciar sesión y evitar caché para mostrar datos actualizados
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

// Validar ID de plato pasado por GET
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die('ID de plato inválido.');
}

// Obtener datos actuales del plato para mostrar en el formulario
$stmt = $conn->prepare('SELECT nombre, descripcion, precio, limite_disponible, activo, imagen FROM platos WHERE id_plato = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$plato = $result->fetch_assoc();
if (!$plato) {
    die('Plato no encontrado.');
}

// Inicializar variables para repoblar el formulario
$errors = [];
$nombre      = $plato['nombre'];
$descripcion = $plato['descripcion'];
$precio      = $plato['precio'];
$limite      = $plato['limite_disponible'];
$activo      = (int)$plato['activo'];

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
    if ($limite === false || $limite < 0)  $errors[] = 'El límite debe ser un entero ≥ 0.';

    // ─ Procesar imagen nueva si existe ─
    $nuevoBlob = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $archivo = $_FILES['imagen'];
        $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
          $errors[] = 'Error al subir la imagen.';
      } elseif (!is_uploaded_file($archivo['tmp_name'])) {
          $errors[] = 'El archivo no es válido.';
      } else {
          $type = mime_content_type($archivo['tmp_name']);
          if (!in_array($type, $permitidos)) {
              $errors[] = 'Solo JPG, PNG o WEBP (≤2MB).';
          } elseif ($archivo['size'] > 2*1024*1024) {
              $errors[] = 'La imagen no debe superar 2MB.';
          } else {
              $nuevoBlob = file_get_contents($archivo['tmp_name']);
          }
      }
      
    }

    // Si no hay errores, llamar al SP para actualizar plato
    if (empty($errors)) {
        $usuario = $_SESSION['Usuario']['Nombre'] ?? 'admin';
        // Preparar llamada al procedimiento almacenado
        $sql = "CALL sp_editar_plato(?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $null = null; // placeholder para blob en bind_param
        /* Parámetros bind_param:
           i = id, s = nombre, s = descripcion, d = precio,
           i = limite, i = activo, b = imagen (o NULL), s = usuario
        */
        $stmt->bind_param(
            'issdiibs',
            $id,
            $nombre,
            $descripcion,
            $precio,
            $limite,
            $activo,
            $null,
            $usuario
        );
        // Si hay imagen nueva, enviarla al índice 6
        if ($nuevoBlob !== null) {
            $stmt->send_long_data(6, $nuevoBlob);
        }

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: dashboard.php?seccion=platos');
            exit();
        } else {
            $errors[] = 'Error al ejecutar sp_editar_plato: ' . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Editar Plato</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
    <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">✏️ Editar Plato</h2>

    <?php if (!empty($errors)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded">
        <ul class="list-disc list-inside">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Nombre del plato</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required class="mt-1 block w-full border rounded p-2" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Descripción</label>
        <textarea name="descripcion" rows="3" required class="mt-1 block w-full border rounded p-2"><?= htmlspecialchars($descripcion) ?></textarea>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Precio ($)</label>
          <input type="number" step="0.01" min="0" name="precio" value="<?= htmlspecialchars($precio) ?>" required class="mt-1 block w-full border rounded p-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Límite disponible</label>
          <input type="number" min="0" name="limite_disponible" value="<?= htmlspecialchars($limite) ?>" required class="mt-1 block w-full border rounded p-2" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Imagen actual</label><br>
        <?php if (!empty($plato['imagen'])): ?>
          <img src="data:image/jpeg;base64,<?= base64_encode($plato['imagen']) ?>" class="w-24 h-20 object-cover rounded mb-2" />
        <?php else: ?>
          <span class="text-gray-500 italic">No hay imagen</span>
        <?php endif; ?>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Nueva imagen (opcional)</label>
        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full border rounded p-2" />
      </div>
      <div class="flex items-center gap-2">
        <input type="checkbox" name="activo" id="activo" <?= $activo ? 'checked' : '' ?> class="rounded border-gray-300" />
        <label for="activo" class="text-sm text-gray-700">Activo</label>
      </div>
      <div class="flex justify-between mt-6">
        <a href="dashboard.php?seccion=platos" class="text-gray-600 hover:underline">⬅ Cancelar</a>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Guardar cambios</button>
      </div>
    </form>
  </div>
</body>
</html>
