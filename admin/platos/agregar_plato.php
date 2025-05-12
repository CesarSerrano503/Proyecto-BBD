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

$errors      = [];
$nombre      = '';
$descripcion = '';
$precio      = '';
$limite      = '';
$activo      = 1; // siempre activo por defecto
$blob        = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Sanitizar/validar
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = filter_input(INPUT_POST, 'precio', FILTER_VALIDATE_FLOAT);
    $limite      = filter_input(INPUT_POST, 'limite_disponible', FILTER_VALIDATE_INT);

    if ($nombre === '')      $errors[] = 'El nombre del plato es obligatorio.';
    if ($descripcion === '') $errors[] = 'La descripción es obligatoria.';
    if ($precio === false || $precio <= 0) $errors[] = 'El precio debe ser un número mayor a 0.';
    if ($limite === false || $limite < 0)  $errors[] = 'El límite debe ser un entero ≥ 0.';

    // 2) Validar imagen
    if (empty($_FILES['imagen']['tmp_name'])) {
        $errors[] = 'La imagen del plato es obligatoria.';
    } else {
        $file    = $_FILES['imagen'];
        $allowed = ['image/jpeg','image/png','image/webp'];
        $type    = mime_content_type($file['tmp_name']);
        if (!in_array($type, $allowed)) {
            $errors[] = 'Solo JPG, PNG o WEBP (≤2MB).';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = 'La imagen no debe superar 2MB.';
        } else {
            $blob = file_get_contents($file['tmp_name']);
        }
    }

    // 3) Llamada al SP si no hay errores
    if (empty($errors)) {
        $stmt = $conn->prepare("CALL sp_crear_plato(?,?,?,?,?,?,?)");
        if (!$stmt) {
            $errors[] = 'Error al preparar el SP: ' . htmlspecialchars($conn->error);
        } else {
            // bind_param tipos:
            //   s => nombre
            //   s => descripcion
            //   d => precio
            //   i => limite
            //   b => imagen (blob)
            //   i => activo
            //   s => usuario (para trigger)
            // Nota: tipo string es 'ssdibis' (b en posición 5)
            $nullBlob = ''; 
            $stmt->bind_param(
                'ssdibis',
                $nombre,
                $descripcion,
                $precio,
                $limite,
                $nullBlob,
                $activo,
                $adminName
            );
            // enviamos el contenido al parámetro 5 (0-based index = 4)
            $stmt->send_long_data(4, $blob);

            if ($stmt->execute()) {
                $stmt->close();
                header('Location: ../dashboard.php?seccion=platos');
                exit;
            } else {
                $errors[] = 'Error al crear plato: ' . htmlspecialchars($stmt->error);
                $stmt->close();
            }
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
    <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">➕ Agregar Nuevo Plato</h2>

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
        <label class="block text-sm font-medium text-gray-700">Nombre del plato</label>
        <input type="text" name="nombre" value="<?= htmlspecialchars($nombre) ?>"
               required class="mt-1 block w-full border rounded p-2" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Descripción</label>
        <textarea name="descripcion" rows="3" required
                  class="mt-1 block w-full border rounded p-2"><?= htmlspecialchars($descripcion) ?></textarea>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Precio ($)</label>
          <input type="number" step="0.01" min="0" name="precio"
                 value="<?= htmlspecialchars($precio) ?>" required
                 class="mt-1 block w-full border rounded p-2" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Límite disponible</label>
          <input type="number" min="0" name="limite_disponible"
                 value="<?= htmlspecialchars($limite) ?>" required
                 class="mt-1 block w-full border rounded p-2" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Imagen del plato</label>
        <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp" required
               class="mt-1 block w-full border rounded p-2" />
      </div>
      <div class="flex justify-between mt-6">
        <a href="../dashboard.php?seccion=platos" class="text-gray-600 hover:underline">⬅ Cancelar</a>
        <button type="submit"
                class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
          Guardar
        </button>
      </div>
    </form>
  </div>
</body>
</html>
