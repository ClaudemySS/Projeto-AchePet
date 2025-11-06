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

// Lógica para Deletar um Usuário
if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   
   // Deleta o usuário da tabela 'users'
   $delete_user = $conn->prepare("DELETE FROM `users` WHERE id = ?");
   $delete_user->execute([$delete_id]);
   
   header('location:users_accounts.php');
   exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Gerenciar Usuários - Admin AchePet</title>
   
   <!-- Tailwind CSS via CDN -->
   <script src="https://cdn.tailwindcss.com"></script>
   
   <!-- Font Awesome (Ícones) -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body class="bg-sky-100 min-h-screen">

<div class="container mx-auto p-4 md:p-8 max-w-6xl">
   
   <!-- Título da Página -->
   <h1 class="text-3xl font-bold text-center text-orange-500 mb-8">Contas de Usuários</h1>

   <!-- Botão de Voltar ao Painel -->
   <div class="mb-6">
        <a href="admin_page.php" class="bg-sky-600 text-white px-4 py-2 rounded-lg shadow hover:bg-sky-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Voltar ao Painel
        </a>
   </div>

   <!-- 
     Grid de Contas de Usuários
     - 1 coluna no celular (padrão)
     - 3 colunas no desktop (lg:grid-cols-3)
   -->
   <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

      <?php
         // CORREÇÃO: Busca na tabela 'users'
         $select_users = $conn->prepare("SELECT * FROM `users`");
         $select_users->execute();
         if($select_users->rowCount() > 0){
            while($fetch_user = $select_users->fetch(PDO::FETCH_ASSOC)){   
      ?>
      
      <!-- Card do Usuário -->
      <div class="bg-white p-5 rounded-xl shadow-lg flex flex-col items-center text-center">
          
          <!-- Ícone do Usuário -->
          <div class="w-16 h-16 rounded-full bg-sky-100 flex items-center justify-center mb-4 border border-gray-200">
              <i class="fas fa-user text-3xl text-sky-600"></i>
          </div>
          
          <!-- Informações do Usuário -->
          <p class="text-sm text-gray-500">User ID: <span class="font-semibold text-gray-700"><?= $fetch_user['id']; ?></span></p>
          <h3 class="text-xl font-semibold text-sky-900 mt-1"><?= htmlspecialchars($fetch_user['name']); ?></h3>
          <p class="text-md text-gray-700"><?= htmlspecialchars($fetch_user['email']); ?></p>
          
          <!-- Botão de Deletar -->
          <a href="users_accounts.php?delete=<?= $fetch_user['id']; ?>" 
             onclick="return confirm('Tem certeza que quer deletar este usuário? (<?= htmlspecialchars($fetch_user['name']); ?>)');" 
             class="mt-4 bg-red-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-600 transition-colors">
             <i class="fas fa-trash-alt mr-1"></i> Deletar Usuário
          </a>
      </div>
      
      <?php
            } // Fim do while
         } else {
            // Mensagem se não houver usuários
            echo '<p class="col-span-full text-center text-2xl font-semibold text-gray-500 mt-10">Nenhuma conta de usuário encontrada!</p>';
         }
      ?>

   </div> <!-- Fim do Grid -->

</div> <!-- Fim do Container -->

</body>
</html>
