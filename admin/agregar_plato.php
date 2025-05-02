<?php
$conn = new mysqli("localhost", "root", "", "reservas_db");
if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = htmlspecialchars(trim($_POST["nombre"]));
    $descripcion = htmlspecialchars(trim($_POST["descripcion"]));
    $precio = filter_input(INPUT_POST, "precio", FILTER_VALIDATE_FLOAT);
    $limite = filter_input(INPUT_POST, "limite_disponible", FILTER_VALIDATE_INT);
    $activo = isset($_POST["activo"]) ? 1 : 0;

    if (!$nombre || !$descripcion || !$precio || $precio <= 0 || $limite < 0 || !isset($_FILES["imagen"])) {
        $error = "Por favor, completa todos los campos correctamente y sube una imagen válida.";
    } else {
        $archivo = $_FILES["imagen"];
        $permitidos = ["image/jpeg", "image/png", "image/webp"];

        if (!in_array($archivo["type"], $permitidos)) {
            $error = "Solo se permiten imágenes JPG, PNG o WEBP.";
        } elseif ($archivo["size"] > 2 * 1024 * 1024) {
            $error = "La imagen no debe superar los 2MB.";
        } else {
            $imagenBinaria = file_get_contents($archivo["tmp_name"]);

            $stmt = $conn->prepare("INSERT INTO platos (nombre, descripcion, precio, activo, imagen, limite_disponible) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdisi", $nombre, $descripcion, $precio, $activo, $imagenBinaria, $limite);
            $stmt->send_long_data(4, $imagenBinaria);
            
            if ($stmt->execute()) {
                header("Location: dashboard.php?seccion=platos");
                exit();
            } else {
                $error = "Error al guardar en la base de datos.";
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
  <h2 class="text-2xl font-bold mb-6 text-gray-800 text-center">➕ Agregar nuevo plato</h2>

  <?php if ($error): ?>
    <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700">Nombre del plato</label>
      <input type="text" name="nombre" required class="mt-1 block w-full border border-gray-300 rounded p-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Descripción</label>
      <textarea name="descripcion" rows="3" required class="mt-1 block w-full border border-gray-300 rounded p-2"></textarea>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Precio ($)</label>
      <input type="number" step="0.01" min="0" name="precio" required class="mt-1 block w-full border border-gray-300 rounded p-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Límite disponible</label>
      <input type="number" name="limite_disponible" min="0" required class="mt-1 block w-full border border-gray-300 rounded p-2" />
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700">Imagen del plato</label>
      <input type="file" name="imagen" accept="image/*" required class="mt-1 block w-full border border-gray-300 rounded p-2" />
    </div>

    <div class="flex items-center gap-2">
      <input type="checkbox" name="activo" id="activo" checked class="rounded border-gray-300">
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
