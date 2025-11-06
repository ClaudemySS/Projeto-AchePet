<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('location:user_login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AchePet</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-sky-100 min-h-screen flex flex-col">

  <header class="flex justify-between items-center p-4 bg-white shadow-md">
    <h1 class="text-2xl font-bold text-yellow-500">ACHE<span class="text-orange-600">PET!</span></h1>
    <div class="flex items-center space-x-4">
      <a href="logout.php" class="text-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded">Sair</a>
      <div class="w-8 h-8 rounded-full bg-sky-300 flex items-center justify-center text-white font-bold">
        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
      </div>
    </div>
  </header>

  <main class="flex-1 p-4">
    <div class="relative w-full h-40 rounded-lg overflow-hidden mb-6">
      <img src="https://placehold.co/400x200" alt="banner" class="w-full h-full object-cover">
      <div class="absolute inset-0 bg-black bg-opacity-20 flex items-center justify-center">
        <a href="#" class="bg-white px-4 py-2 rounded shadow text-yellow-600 font-semibold">
          Adote um Pet!
        </a>
      </div>
    </div>

    <h2 class="text-lg font-bold mb-2">A procura de uma companhia?</h2>
    <p class="text-gray-600 mb-3">Adote um pet</p>
    <div class="grid grid-cols-3 gap-3 mb-6">
      <img src="https://placehold.co/120x120" class="rounded-lg shadow">
      <img src="https://placehold.co/120x120" class="rounded-lg shadow">
      <img src="https://placehold.co/120x120" class="rounded-lg shadow">
    </div>

    <h2 class="text-lg font-bold mb-2">Aprenda a como cuidar do seu pet</h2>
    <p class="text-gray-600 mb-3">Gato ou cachorro, conheça detalhes sobre cada um</p>
    <div class="grid grid-cols-3 gap-3">
      <img src="https://placehold.co/120x120" class="rounded-lg shadow">
      <img src="https://placehold.co/120x120" class="rounded-lg shadow">
      <img src="https://placehold.co/120x120" class="rounded-lg shadow">
    </div>
  </main>

  <footer class="bg-yellow-400 p-4 flex justify-around items-center">
    <a href="#" class="flex flex-col items-center text-blue-900 font-bold">
      <span>📍</span><span class="text-sm">PETS NO MAPA</span>
    </a>
    <a href="#" class="flex flex-col items-center text-blue-900 font-bold">
      <span>📷</span><span class="text-sm">+</span>
    </a>
    <a href="#" class="flex flex-col items-center text-blue-900 font-bold">
      <span>🔍</span><span class="text-sm">PET PERDIDO</span>
    </a>
  </footer>
</body>
</html>
