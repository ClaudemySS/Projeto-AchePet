<?php
include 'config.php';
session_start();

// Esta é a trava de segurança.
// Se o 'admin_id' não existir na sessão (ou seja, não logou),
// ele chuta o usuário de volta para a tela de login.
$admin_id = $_SESSION['admin_id'] ?? null;
if(!isset($admin_id)){
   header('location:admin_login.php');
   exit;
}

// Busca o email do admin logado para saudação
$admin_email = $_SESSION['admin_email'] ?? 'Admin';

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Painel Admin - AchePet</title>
   
   <!-- Tailwind CSS via CDN -->
   <script src="https://cdn.tailwindcss.com"></script>
   
   <!-- Font Awesome (Ícones) -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body class="bg-sky-100 flex items-center justify-center min-h-screen p-4">

<!-- 
  Este é o "card" principal do painel, inspirado no layout do login,
  mas maior para acomodar as opções.
-->
<section class="bg-white p-8 rounded-xl shadow-lg w-full max-w-4xl">

   <!-- Cabeçalho do Painel -->
   <div class="flex justify-between items-center border-b border-gray-200 pb-4 mb-6">
       <div>
            <!-- Título "AchePet!" -->
            <h3 class="text-3xl font-bold text-orange-500">AchePet!</h3>
            <p class="text-lg text-gray-600">Painel Administrativo</p>
       </div>
       <!-- Informação do Admin Logado -->
       <div class="text-right">
            <p class="text-sm text-gray-500">Logado como:</p>
            <p class="text-md font-medium text-sky-800"><?php echo htmlspecialchars($admin_email); ?></p>
            <!-- O logout.php (do seu projeto) encerra a sessão -->
            <a href="logout.php" class="text-sm text-red-600 hover:underline font-medium">
                Sair <i class="fas fa-sign-out-alt ml-1"></i>
            </a>
       </div>
   </div>

   <!-- 
     Grid de Opções (Inspirado na imagem do BlueBox [cite: image_021f9f.png])
     - 1 coluna no celular (padrão)
     - 3 colunas no desktop (md:grid-cols-3)
   -->
   <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

      <!-- Card 1: Gerenciar Pets -->
      <a href="admin_pets.php" class="block bg-gray-50 p-6 rounded-lg shadow-md text-center transition-all hover:shadow-lg hover:scale-105 hover:bg-white">
          <i class="fas fa-paw text-4xl text-orange-500 mb-3"></i>
          <h4 class="text-lg font-semibold text-sky-900 mb-1">Gerenciar Pets</h4>
          <p class="text-sm text-gray-600">Editar ou excluir perfis de pets.</p>
      </a>

      <!-- Card 2: Gerenciar Usuários -->
      <a href="users_accounts.php" class="block bg-gray-50 p-6 rounded-lg shadow-md text-center transition-all hover:shadow-lg hover:scale-105 hover:bg-white">
          <i class="fas fa-users text-4xl text-orange-500 mb-3"></i>
          <h4 class="text-lg font-semibold text-sky-900 mb-1">Gerenciar Usuários</h4>
          <p class="text-sm text-gray-600">Visualizar contas de usuários.</p>
      </a>

      <!-- Card 3: Adicionar Admin -->
      <a href="admin_register.php" class="block bg-gray-50 p-6 rounded-lg shadow-md text-center transition-all hover:shadow-lg hover:scale-105 hover:bg-white">
          <i class="fas fa-user-shield text-4xl text-orange-500 mb-3"></i>
          <h4 class="text-lg font-semibold text-sky-900 mb-1">Adicionar Admin</h4>
          <p class="text-sm text-gray-600">Registrar um novo administrador.</p>
      </a>

   </div>

</section>

</body>
</html>

