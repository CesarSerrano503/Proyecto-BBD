<?php
// ───────── SESIÓN Y CACHE ─────────
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ───────── ADMIN CHECK ─────────
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol'] !== 'admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// ───────── CONEXIÓN A LA BBDD ─────────
$conn = new mysqli('localhost', 'root', '', 'reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

// ───────── INYECTAR USUARIO PARA TRIGGERS ─────────
$adminName = $conn->real_escape_string($_SESSION['Usuario']['Nombre']);
$conn->query("SET @usuario = '{$adminName}';");

// ───────── VALIDAR ID DE PLATO ─────────
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die('ID de plato inválido.');
}

// ───────── OBTENER DATOS EXISTENTES ─────────
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

// ───────── INICIALIZAR VALORES DEL FORMULARIO ─────────
$errors      = [];
$nombre      = $plato['nombre'];
$descripcion = $plato['descripcion'];
$precio      = $plato['precio'];
$limite      = $plato['limite_disponible'];
$activo      = (int)$plato['activo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Sanear y validar
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $limite      = filter_input(INPUT_POST, 'limite_disponible', FILTER_VALIDATE_INT);
    $activo      = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '')      $errors[] = 'El nombre es obligatorio.';
    if ($descripcion === '') $errors[] = 'La descripción es obligatoria.';
    if ($precio === false || $precio <= 0) $errors[] = 'El precio debe ser mayor a 0.';
    if ($limite === false || $limite < 0)  $errors[] = 'El límite debe ser un entero ≥ 0.';

    // 2) Procesar imagen (opcional)
    $nuevoBlob = null;
    if (!empty($_FILES['imagen']['tmp_name'])) {
        $file    = $_FILES['imagen'];
        $allowed = ['image/jpeg','image/png','image/webp'];
        $type    = mime_content_type($file['tmp_name']);
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error al subir la imagen.';
        } elseif (!in_array($type, $allowed)) {
            $errors[] = 'Solo JPG, PNG o WEBP (≤2MB).';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'La imagen no debe superar 2MB.';
        } else {
            $nuevoBlob = file_get_contents($file['tmp_name']);
        }
    }

    // 3) Llamar al SP si no hay errores
    if (empty($errors)) {
        $sql = 'CALL sp_editar_plato(?, ?, ?, ?, ?, ?, ?, ?)';
        $stmtSP = $conn->prepare($sql);
        $nullBlob = null;
        // bind_param types: i = id, s = nombre, s = descripcion, d = precio, i = limite, b = imagen, i = activo, s = usuario
        $stmtSP->bind_param(
            'issdiibs',
            $id,           // p_id
            $nombre,       // p_nombre
            $descripcion,  // p_descripcion
            $precio,       // p_precio
            $limite,       // p_limite
            $nullBlob,     // p_imagen placeholder
            $activo,       // p_activo
            $adminName     // p_usuario
        );
        if ($nuevoBlob !== null) {
            // posición 5 (0-based) es p_imagen
            $stmtSP->send_long_data(5, $nuevoBlob);
        }

        if ($stmtSP->execute()) {
            $stmtSP->close();
            header('Location: ../dashboard.php?seccion=platos');
            exit;
        } else {
            $errors[] = 'Error al actualizar: ' . htmlspecialchars($stmtSP->error);
            $stmtSP->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>✏️ Editar Plato</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
    <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">✏️ Editar Plato</h2>

    <?php if ($errors): ?>
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
      <div class="flex items-center">
        <input type="checkbox" name="activo" id="activo" <?= $activo ? 'checked' : '' ?> class="mr-2">
        <label for="activo" class="text-sm font-medium text-gray-700">Activo</label>
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
