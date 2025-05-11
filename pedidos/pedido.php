<?php
// ───────── SESIÓN Y CACHE ─────────
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// ───────── VERIFICAR SESIÓN COMO ALUMNO ─────────
if (!isset($_SESSION['Usuario']['Carnet']) || $_SESSION['Usuario']['Rol'] !== 'alumno') {
    header('Location: ../login/login.php?cerrado=1');
    exit;
}

// ───────── CONEXIÓN A LA BASE DE DATOS ─────────
$conn = new mysqli('localhost','root','','reservas_db');
if ($conn->connect_error) {
    die('Error de conexión: '.$conn->connect_error);
}

// ───────── LEER ID DE PLATO ─────────
$platoId = filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);
if (!$platoId) {
    die("<p class='text-center mt-10 text-red-500 font-bold'>❌ ID de plato inválido.</p>");
}

// ───────── OBTENER DATOS DEL PLATO ─────────
$stmt = $conn->prepare("
    SELECT id_plato, nombre, descripcion, precio, imagen, limite_disponible
      FROM platos
     WHERE id_plato = ?
       AND activo = 1
");
$stmt->bind_param('i', $platoId);
$stmt->execute();
$plato = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plato) {
    die("<p class='text-center mt-10 text-red-500 font-bold'>❌ Plato no encontrado o inactivo.</p>");
}

// ───────── USAR directly limite_disponible ─────────
$limite      = (int)$plato['limite_disponible'];
$disponibles = $limite > 0 ? $limite : 'Sin límite';
$bloqueado   = ($limite === 0);

// ───────── OBTENER COMPLEMENTOS ACTIVOS ─────────
$res    = $conn->query("
    SELECT id_complemento, nombre, tipo, precio
      FROM complementos
     WHERE activo = 1
     ORDER BY tipo, nombre
");
$extras = [];
$otros  = [];
while ($c = $res->fetch_assoc()) {
    if ($c['tipo'] === 'extra') {
        $extras[] = $c;
    } else {
        $otros[$c['tipo']][] = $c;
    }
}

// ───────── VARIABLES PARA JS ─────────
$precioBase = floatval($plato['precio']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reservar <?= htmlspecialchars($plato['nombre']) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6 min-h-screen">
  <div class="max-w-4xl mx-auto bg-white shadow-md rounded-xl p-6 flex flex-col md:flex-row gap-6">

    <!-- INFORMACIÓN DEL PLATO -->
    <div class="md:w-1/2">
      <?php if (!empty($plato['imagen'])): ?>
        <img src="data:image/jpeg;base64,<?= base64_encode($plato['imagen']) ?>"
             alt="<?= htmlspecialchars($plato['nombre']) ?>"
             class="w-full h-48 object-cover rounded-lg"/>
      <?php else: ?>
        <div class="w-full h-48 bg-gray-200 rounded-lg flex items-center justify-center text-gray-500">
          Sin imagen
        </div>
      <?php endif; ?>
      <h2 class="text-2xl font-semibold mt-4"><?= htmlspecialchars($plato['nombre']) ?></h2>
      <p class="text-gray-700 mt-2">Precio base: $<?= number_format($precioBase,2) ?> USD</p>
      <p class="text-gray-500 mt-1 text-sm">
        Disponibles hoy: <?= htmlspecialchars($disponibles) ?>
      </p>
    </div>

    <!-- FORMULARIO DE PEDIDO -->
    <div class="md:w-1/2">
      <?php if ($bloqueado): ?>
        <p class="text-red-500 font-bold text-center">
          ❌ No hay disponibilidad para este plato hoy.
        </p>
      <?php else: ?>
        <form action="guardar_pedido.php" method="POST" class="space-y-4">
          <input type="hidden" name="id_plato"    value="<?= $platoId ?>"/>
          <input type="hidden" name="precio_base"  id="precio_base" value="<?= $precioBase ?>"/>

          <!-- Extras (select múltiple) -->
          <?php if ($extras): foreach ($extras as $ex): ?>
            <div>
              <label class="block font-medium">
                <?= htmlspecialchars($ex['nombre']) ?> ($<?= number_format($ex['precio'],2) ?> c/u)
              </label>
              <select name="extra_<?= $ex['id_complemento'] ?>"
                      data-price="<?= $ex['precio'] ?>"
                      class="cantidad border rounded p-2 w-24">
                <?php for ($i=0; $i<=5; $i++): ?>
                  <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
          <?php endforeach; endif; ?>

          <!-- Complementos (radio por tipo) -->
          <?php foreach ($otros as $tipo => $gr): ?>
            <p class="font-medium capitalize mt-4"><?= htmlspecialchars($tipo) ?>:</p>
            <?php foreach ($gr as $c): ?>
              <label class="block ml-4">
                <input type="radio"
                       name="comp_<?= htmlspecialchars($tipo) ?>"
                       value="<?= $c['id_complemento'] ?>"
                       data-price="<?= $c['precio'] ?>"
                       class="complemento inline-block mr-2"/>
                <?= htmlspecialchars($c['nombre']) ?> ($<?= number_format($c['precio'],2) ?>)
              </label>
            <?php endforeach; ?>
          <?php endforeach; ?>

          <!-- Total y enviar -->
          <div class="mt-6">
            <p class="text-lg font-bold">
              Total: <span id="total">$<?= number_format($precioBase,2) ?></span>
            </p>
            <input type="hidden" name="total" id="total-hidden" value="<?= number_format($precioBase,2) ?>"/>
            <button type="submit"
                    class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
              Confirmar pedido
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- CÁLCULO DE TOTAL EN JS -->
  <script>
    const base       = parseFloat(document.getElementById('precio_base').value),
          totalSpan  = document.getElementById('total'),
          totalInput = document.getElementById('total-hidden'),
          selects    = document.querySelectorAll('.cantidad'),
          radios     = document.querySelectorAll('.complemento');

    function actualizarTotal() {
      let sum = base;
      selects.forEach(sel => {
        const qty   = parseInt(sel.value)   || 0,
              price = parseFloat(sel.dataset.price) || 0;
        sum += qty * price;
      });
      radios.forEach(r => {
        if (r.checked) sum += parseFloat(r.dataset.price) || 0;
      });
      const t = sum.toFixed(2);
      totalSpan.textContent = '$' + t;
      totalInput.value     = t;
    }

    selects.forEach(s => s.addEventListener('change', actualizarTotal));
    radios .forEach(r => r.addEventListener('change', actualizarTotal));
  </script>
</body>
</html>
