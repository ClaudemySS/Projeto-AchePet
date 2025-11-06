<?php
include 'config.php';
session_start();

// Se o usuário já logou, manda para o index
if(isset($_SESSION['user_id'])){
   header('location:index.php');
   exit;
}

if(isset($_POST['submit'])){
   $email = $_POST['email'];
   $pass = sha1($_POST['pass']);

   // Procura na tabela 'users'
   $select_user = $conn->prepare("SELECT * FROM `users` WHERE email = ? AND password = ?");
   $select_user->execute([$email, $pass]);
   
   if($select_user->rowCount() > 0){
      $fetch_user = $select_user->fetch(PDO::FETCH_ASSOC);
      $_SESSION['user_id'] = $fetch_user['id'];
      $_SESSION['user_name'] = $fetch_user['name'];
      $_SESSION['user_email'] = $fetch_user['email'];
      // Envia o usuário normal para o index
      header('location:index.php'); 
   } else {
      $message[] = 'Email ou senha incorretos!';
   }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Login - AchePet</title>
   <!-- Tailwind CSS via CDN -->
   <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-sky-100 flex items-center justify-center min-h-screen p-4">

<?php
if(isset($message)){
   foreach($message as $msg){
      // Estilizando a mensagem de erro
      echo '
      <div class="message absolute top-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg" style="z-index: 100;">
         <span>'.$msg.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();" style="cursor: pointer; margin-left: 15px;"></i>
      </div>
      ';
   }
}
?>

<!-- Card de Login (Layout da Imagem) -->
<section class="bg-white p-8 rounded-xl shadow-lg w-full max-w-sm">
   <form action="" method="post">
      <h3 class="text-3xl font-bold text-center text-orange-500 mb-6">AchePet!</h3>
      
      <div class="mb-4">
         <label for="email" class="block text-sm font-medium text-gray-700 sr-only">Email</label>
         <input type="email" id="email" name="email" required placeholder="Email" class="box w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400">
      </div>
      
      <div class="mb-4">
         <label for="pass" class="block text-sm font-medium text-gray-700 sr-only">Senha</label>
         <input type="password" id="pass" name="pass" required placeholder="Senha" class="box w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400">
      </div>
      
      <input type="submit" value="Entrar" name="submit" class="btn w-full bg-orange-500 text-white p-3 rounded-md font-bold cursor-pointer hover:bg-orange-600 transition-colors">
      
      <p class="text-center text-sm text-gray-600 mt-4">
         Não tem conta? <a href="user_register.php" class="text-blue-600 hover:underline">Cadastre-se</a>
      </p>
   </form>
</section>

<!-- Script para Font Awesome (ícone de fechar a msg de erro) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/all.min.js"></script>
</body>
</html>

