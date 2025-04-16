<?php
$conn = new mysqli("localhost", "root", "", "reservas_db");
if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) die("ID de plato inválido.");

$stmt = $conn->prepare("SELECT * FROM platos WHERE id_plato = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$plato = $resultado->fetch_assoc();

if (!$plato) die("Plato no encontrado.");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = htmlspecialchars(trim($_POST["nombre"]));
    $descripcion = htmlspecialchars(trim($_POST["descripcion"]));
    $precio = filter_input(INPUT_POST, "precio", FILTER_VALIDATE_FLOAT);
    $activo = isset($_POST["activo"]) ? 1 : 0;

    if (!$nombre || !$descripcion || !$precio || $precio <= 0) {
        $error = "Por favor completa todos los campos correctamente.";
    } else {
        if (!empty($_FILES["imagen"]["tmp_name"])) {
            $archivo = $_FILES["imagen"];
            $permitidos = ["image/jpeg", "image/png", "image/webp"];

            if (!in_array($archivo["type"], $permitidos)) {
                $error = "Formato de imagen no válido.";
            } elseif ($archivo["size"] > 2 * 1024 * 1024) {
                $error = "La imagen no debe superar los 2MB.";
            } else {
                $imagenBinaria = file_get_contents($archivo["tmp_name"]);
                $stmt = $conn->prepare("UPDATE platos SET nombre=?, descripcion=?, precio=?, activo=?, imagen=? WHERE id_plato=?");
                $stmt->bind_param("ssdibi", $nombre, $descripcion, $precio, $activo, $imagenBinaria, $id);
                if ($stmt->send_long_data(4, $imagenBinaria) && $stmt->execute()) {
                    header("Location: dashboard.php?seccion=platos");
                    exit();
                } else {
                    $error = "Error al actualizar el plato con imagen.";
                }
            }
        } else {
            $stmt = $conn->prepare("UPDATE platos SET nombre=?, descripcion=?, precio=?, activo=? WHERE id_plato=?");
            $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $activo, $id);
            if ($stmt->execute()) {
                header("Location: dashboard.php?seccion=platos");
                exit();
            } else {
                $error = "Error al actualizar el plato.";
            }
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
  <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">✏️ Editar plato</h2>

  <?php if ($error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700">Nombre del plato</label>
      <input type="text" name="nombre" value="<?= htmlspecialchars($plato['nombre']) ?>" required class="mt-1 block w-full border border-gray-300 rounded p-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Descripción</label>
      <textarea name="descripcion" rows="3" required class="mt-1 block w-full border border-gray-300 rounded p-2"><?= htmlspecialchars($plato['descripcion']) ?></textarea>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Precio ($)</label>
      <input type="number" step="0.01" name="precio" value="<?= htmlspecialchars($plato['precio']) ?>" required class="mt-1 block w-full border border-gray-300 rounded p-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Imagen actual:</label><br>
      <?php if (!empty($plato['imagen'])): ?>
        <img src="data:image/jpeg;base64,<?= base64_encode($plato['imagen']) ?>" class="w-24 h-20 object-cover rounded mb-2" />
      <?php else: ?>
        <span class="text-gray-500 italic">No hay imagen</span>
      <?php endif; ?>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Nueva imagen (opcional)</label>
      <input type="file" name="imagen" accept="image/*" class="mt-1 block w-full border border-gray-300 rounded p-2" />
    </div>

    <div class="flex items-center gap-2">
      <input type="checkbox" name="activo" id="activo" <?= $plato['activo'] ? "checked" : "" ?> class="rounded border-gray-300">
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
