<?php
// ───────── SESSION & CACHE ─────────
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ───────── ADMIN CHECK ─────────
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// ───────── DB CONNECTION ─────────
$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
// Inyectar en MySQL el nombre del admin para que el trigger lo use
$adminName = $conn->real_escape_string($_SESSION['Usuario']['Nombre']);
$conn->query("SET @usuario = '{$adminName}';");

// ───────── VALIDATE PLATO ID ─────────
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die('ID de plato inválido.');
}

// ───────── FETCH CURRENT DATA ─────────
$stmt = $conn->prepare(
    'SELECT nombre, descripcion, precio, limite_disponible, activo, imagen
      FROM platos
      WHERE id_plato = ?'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$plato = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plato) {
    die('Plato no encontrado.');
}

// ───────── INITIALIZE FORM VALUES ─────────
$errors      = [];
$nombre      = $plato['nombre'];
$descripcion = $plato['descripcion'];
$precio      = $plato['precio'];
$limite      = $plato['limite_disponible'];
$activo      = (int)$plato['activo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Sanitize inputs
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // 2) Validate numeric fields
    $precio = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $limite = filter_input(INPUT_POST, 'limite_disponible', FILTER_VALIDATE_INT);

    // 3) Basic validations
    if ($nombre === '')      $errors[] = 'El nombre del plato es obligatorio.';
    if ($descripcion === '') $errors[] = 'La descripción es obligatoria.';
    if ($precio === false || $precio <= 0) $errors[] = 'El precio debe ser un número mayor a 0.';
    if ($limite === false || $limite < 0)  $errors[] = 'El límite debe ser un entero ≥ 0.';

    // 4) Optional image upload
    $nuevoBlob = null;
    if (!empty($_FILES['imagen']['tmp_name'])) {
        $archivo    = $_FILES['imagen'];
        $permitidos = ['image/jpeg','image/png','image/webp'];
        $type       = mime_content_type($archivo['tmp_name']);
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error al subir la imagen.';
        } elseif (!in_array($type, $permitidos)) {
            $errors[] = 'Solo JPG, PNG o WEBP (≤2MB).';
        } elseif ($archivo['size'] > 2 * 1024 * 1024) {
            $errors[] = 'La imagen no debe superar 2MB.';
        } else {
            $nuevoBlob = file_get_contents($archivo['tmp_name']);
        }
    }

    // 5) If no errors, perform UPDATE
    if (empty($errors)) {
        if ($nuevoBlob !== null) {
            // actualizar todos los campos incluyendo imagen
            $sql = "UPDATE platos
                      SET nombre = ?, descripcion = ?, precio = ?,
                          limite_disponible = ?, activo = ?, imagen = ?
                    WHERE id_plato = ?";
            $stmt = $conn->prepare($sql);
            // prepare a null var for the blob placeholder
            $nullBlob = null;
            // types: s,s,d,i,i,b,i
            $stmt->bind_param(
                'ssdiibi',
                $nombre,
                $descripcion,
                $precio,
                $limite,
                $activo,
                $nullBlob,
                $id
            );
            // send the actual blob data to parameter 5 (0-based)
            $stmt->send_long_data(5, $nuevoBlob);
        } else {
            // actualizar todo excepto imagen
            $sql = "UPDATE platos
                      SET nombre = ?, descripcion = ?, precio = ?,
                          limite_disponible = ?, activo = ?
                    WHERE id_plato = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                'ssdi i',
                $nombre,
                $descripcion,
                $precio,
                $limite,
                $activo,
                $id
            );
        }

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header('Location: ../dashboard.php?seccion=platos');
            exit;
        } else {
            $errors[] = 'Error al actualizar plato: ' . htmlspecialchars($stmt->error);
            $stmt->close();
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
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Nombre</label>
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
          <label class="block text-sm font-medium text-gray-700">Límite Disponible</label>
          <input type="number" min="0" name="limite_disponible" value="<?= htmlspecialchars($limite) ?>" required class="mt-1 block w-full border rounded p-2" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Imagen Actual</label>
        <?php if (!empty($plato['imagen'])): ?>
          <img src="data:image/jpeg;base64,<?= base64_encode($plato['imagen']) ?>" class="w-24 h-20 object-cover rounded mb-2" />
        <?php else: ?>
          <span class="text-gray-500 italic">No hay imagen</span>
        <?php endif; ?>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Nueva Imagen (opcional)</label>
        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full border rounded p-2" />
      </div>
      <div class="flex justify-between mt-6">
        <a href="../dashboard.php?seccion=platos" class="text-gray-600 hover:underline">⬅ Cancelar</a>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Guardar Cambios</button>
      </div>
    </form>
  </div>
</body>
</html>
