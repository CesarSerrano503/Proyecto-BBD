<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Ver Pedidos</title>
</head>
<body class="bg-white flex flex-col min-h-screen">

  <!-- Header móvil -->
  <header class="bg-gray-300 p-4 flex justify-between items-center md:hidden">
    <h2 class="font-semibold">Domingo Savio</h2>
    <button id="menu-toggle" class="text-gray-800 text-2xl">☰</button>
  </header>

  <!-- Menú hamburguesa móvil -->
  <nav id="mobile-menu" class="hidden flex-col bg-gray-200 p-4 space-y-2 fixed top-0 left-0 w-48 h-full shadow-lg z-50 md:hidden">
    <button id="close-menu" class="text-right text-xl">✖</button>
    <a href="#" class="font-semibold hover:bg-gray-400 p-2 rounded">Agregar producto</a>
    <a href="#" class="font-semibold hover:bg-gray-400 p-2 rounded">Agregar complementos</a>
    <a href="#" class="font-semibold hover:bg-gray-400 p-2 rounded">Verificar pedidos</a>
    <a href="#" class="font-semibold hover:bg-gray-400 p-2 rounded text-red-600">Cerrar sesión</a>
  </nav>

  <div class="flex flex-1">
    <!-- Menú lateral escritorio -->
    <aside class="bg-gray-300 w-64 p-6 flex-col items-center space-y-6 hidden md:flex">
      <img src="../../imgs/Domingo.jpeg" alt="Logo" class="w-20 h-25 rounded-full">
      <h2 class="font-semibold text-lg text-center">Domingo Savio</h2>
      <nav class="w-full">
        <a href="#" class="flex items-center space-x-2 p-3 hover:bg-gray-400 rounded">
          <img src="../../imgs/plato.png" class="w-10" alt="Agregar producto"> <span>Agregar producto</span>
        </a>
        <a href="#" class="flex items-center space-x-2 p-3 hover:bg-gray-400 rounded">
          <img src="../../imgs/saludable.png" class="w-14" alt="Agregar complementos"> <span>Agregar complementos</span>
        </a>
        <a href="#" class="flex items-center space-x-2 p-3 hover:bg-gray-400 rounded">
          <img src="../../imgs/foto.png" class="w-10" alt="Verificar pedidos"> <span>Verificar pedidos</span>
        </a>
        <a href="#" class="flex items-center space-x-2 p-3 hover:bg-gray-400 rounded text-red-600">
          <img src="../../imgs/cerrar-sesion.png" class="w-10" alt="Cerrar sesión"> <span>Cerrar sesión</span>
        </a>
      </nav>
    </aside>

    <!-- Contenido principal -->
    <main class="flex-1 p-6 flex justify-center items-start">
        <div class="w-full max-w-4xl bg-yellow-100 rounded shadow-md overflow-auto">
            <table class="w-full text-left">
              <thead>
                <tr class="bg-yellow-200 text-center font-bold">
                  <th class="p-3 border-b">Nombre</th>
                  <th class="p-3 border-b">Sección</th>
                  <th class="p-3 border-b">Grado</th>
                  <th class="p-3 border-b">Pedido</th>
                  <th class="p-3 border-b">Completado</th>
                </tr>
              </thead>
              <tbody class="text-sm">
                <tr class="border-t">
                  <td class="p-3">Christopher Tommy Núñez Pineda</td>
                  <td class="p-3 text-center">B</td>
                  <td class="p-3 text-center">8</td>
                  <td class="p-3">Carne asada</td>
                  <td class="p-3 text-center">
                    <input type="checkbox" class="w-5 h-5 accent-green-600" />
                  </td>
                </tr>
                <!-- Puedes duplicar este <tr> para más filas -->
              </tbody>
            </table>
          </div>
          
    </main>
  </div>

  <script>
    const menuToggle = document.getElementById('menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const closeMenu = document.getElementById('close-menu');

    menuToggle.addEventListener('click', () => {
      mobileMenu.classList.toggle('hidden');
    });

    closeMenu.addEventListener('click', () => {
      mobileMenu.classList.add('hidden');
    });
  </script>
</body>
</html>
