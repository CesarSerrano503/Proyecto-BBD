<div class="bg-green-200 p-4 justify-between items-center hidden md:flex">
<nav class="flex items-center space-x-4">
    <span class="bg-yellow-300 px-2 py-2 rounded"><strong><?= $_SESSION["Usuario"]["Carnet"] ?></strong></span>
    <a href="index.php" class="font-semibold hover:bg-green-600 transition duration-[2000ms] p-2 rounded">Menú del día</a>
    <a href="pedido.php" class="font-semibold hover:bg-green-600 transition duration-[2000ms] p-2 rounded">Su pedido</a>
</nav>
<div class="flex items-center space-x-4">
    <span class="font-semibold">Total a pagar: 3.99</span>
    <a href="templates/logout.php" class="bg-red-500 text-white px-4 py-2 rounded">Cerrar sesión</a>
</div>
</div>

<div class="bg-green-200 p-4 flex justify-between items-center md:hidden">
    <button id="menu-toggle" class="text-white bg-gray-800 px-4 py-2 rounded">☰</button>
    <span class="font-semibold">Total a pagar: 3.99</span>
</div>

<nav id="mobile-menu" class="hidden flex-col bg-green-300 p-4 space-y-2 md:hidden">
    <center><span class="bg-yellow-300 px-2 py-2 rounded"><strong><?= $_SESSION["Usuario"]["Carnet"] ?></strong></span></center><br>
    <a href="index.php" class="font-semibold hover:bg-green-600 transition duration-[2000ms] p-2 rounded">Menú del día</a><br><br>
    <a href="pedido.php" class="font-semibold hover:bg-green-600 transition duration-[2000ms] p-2 rounded">Su pedido</a><br><br>
    <a href="templates/logout.php" class="bg-red-500 text-white px-4 py-2 rounded">Cerrar sesión</a>
</nav>
