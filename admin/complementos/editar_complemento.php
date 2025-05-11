<?php
session_start();
if (empty($_SESSION['Usuario']['Rol']) || $_SESSION['Usuario']['Rol']!=='admin') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) die('Error de conexión');

// ───────── INYECTAR USUARIO PARA TRIGGERS ─────────
$admin = $conn->real_escape_string($_SESSION['Usuario']['Nombre']);
$conn->query("SET @usuario = '{$admin}';");

// ───────── VALIDAR ID ─────────
$id = filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: ../dashboard.php?seccion=complementos');
    exit;
}

$errors = [];
// ───────── LEER DATOS CON SP sp_leer_complemento ─────────
$stmt = $conn->prepare("CALL sp_leer_complemento(?)");
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$res) {
    die('Complemento no encontrado');
}

$nombre      = $res['nombre'];
$tipo        = $res['tipo'];
$precio      = $res['precio'];
$activo      = (int)$res['activo'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $tipo     = trim($_POST['tipo']   ?? '');
    $precio   = filter_input(INPUT_POST,'precio',FILTER_VALIDATE_FLOAT);
    $activo   = isset($_POST['activo']) ? 1 : 0;

    if ($nombre==='')  $errors[] = 'El nombre es obligatorio.';
    if ($tipo==='')    $errors[] = 'El tipo es obligatorio.';
    if ($precio===false || $precio<0) $errors[] = 'El precio debe ser ≥ 0.';

    if (empty($errors)) {
        // ───────── ACTUALIZAR CON SP sp_actualizar_complemento ─────────
        $stmt = $conn->prepare("CALL sp_actualizar_complemento(?,?,?,?,?)");
        $stmt->bind_param('issdi',
            $id,
            $nombre,
            $tipo,
            $precio,
            $activo
        );
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: ../dashboard.php?seccion=complementos');
            exit;
        } else {
            $errors[] = 'Error al actualizar: '.$stmt->error;
            $stmt->close();
        }
    }
}
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

  <?php if (!empty($errors)): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded">
      <ul class="list-disc list-inside">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" class="space-y-4 bg-white p-6 rounded shadow">
    <div>
      <label class="block mb-1 font-medium">Nombre:</label>
      <input name="nombre" value="<?= htmlspecialchars($nombre) ?>" required
             class="border rounded w-full px-3 py-2">
    </div>

    <div>
      <label class="block mb-1 font-medium">Tipo:</label>
      <select name="tipo" required class="border rounded w-full px-3 py-2">
        <option value="">-- Selecciona tipo --</option>
        <option value="bebida"     <?= $tipo==='bebida'     ? 'selected':'' ?>>Bebida</option>
        <option value="guarnicion" <?= $tipo==='guarnicion' ? 'selected':'' ?>>Guarnición</option>
        <option value="ensalada"   <?= $tipo==='ensalada'   ? 'selected':'' ?>>Ensalada</option>
        <option value="extra"      <?= $tipo==='extra'      ? 'selected':'' ?>>Extra</option>
      </select>
    </div>

    <div>
      <label class="block mb-1 font-medium">Precio:</label>
      <input name="precio" type="number" step="0.01" min="0"
             value="<?= htmlspecialchars($precio) ?>" required
             class="border rounded w-full px-3 py-2">
    </div>

    <div class="flex items-center">
      <input name="activo" id="activo" type="checkbox" <?= $activo?'checked':'' ?>
             class="mr-2">
      <label for="activo">Activo</label>
    </div>

    <div class="flex space-x-4 mt-4">
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
