<?php
include 'config.php';
session_start();

// Se o admin já logou, manda para o painel
if(isset($_SESSION['admin_id'])){
   header('location:admin_page.php');
   exit;
}

if(isset($_POST['submit'])){

   // VERIFICA SE JÁ EXISTE UM ADMIN
   $check_admins = $conn->prepare("SELECT id FROM `administrador` LIMIT 1");
   $check_admins->execute();

   if($check_admins->rowCount() > 0){
      $message[] = 'O registro de administrador já foi feito e está bloqueado!';
   } else {
      // Se não existe admin, continua com o registro
      $email = $_POST['email'];
      $pass = sha1($_POST['pass']);
      $cpass = sha1($_POST['cpass']);

      // Verifica se o email já existe (só por segurança, mas a trava acima é a principal)
      $select_admin = $conn->prepare("SELECT * FROM `administrador` WHERE email = ?");
      $select_admin->execute([$email]);

      if($select_admin->rowCount() > 0){
         $message[] = 'Este email já está cadastrado!'; // Improvável de acontecer
      } else {
         if($pass != $cpass){
            $message[] = 'As senhas não conferem!';
         } else {
            // Insere o novo admin na tabela 'administrador' (sem nome)
            $insert_admin = $conn->prepare("INSERT INTO `administrador`(email, password) VALUES(?,?)");
            $insert_admin->execute([$email, $cpass]);
            
            // Loga o admin automaticamente
            $select_new_admin = $conn->prepare("SELECT * FROM `administrador` WHERE email = ? AND password = ?");
            $select_new_admin->execute([$email, $cpass]);
            $fetch_admin = $select_new_admin->fetch(PDO::FETCH_ASSOC);

            if($fetch_admin){
                $_SESSION['admin_id'] = $fetch_admin['id'];
                $_SESSION['admin_name'] = $fetch_admin['email']; // Usa o email como nome
                $_SESSION['admin_email'] = $fetch_admin['email'];
                header('location:admin_page.php'); // Envia para o painel de admin
            } else {
                $message[] = 'Falha ao registrar e logar.';
            }
         }
      }
   }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Registrar Admin - AchePet</title>
   <!-- Tailwind CSS via CDN -->
   <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-sky-100 flex items-center justify-center min-h-screen p-4">

<?php
if(isset($message)){
   foreach($message as $msg){
      echo '
      <div class="message absolute top-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg" style="z-index: 100;">
         <span>'.$msg.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();" style="cursor: pointer; margin-left: 15px;"></i>
      </div>
      ';
   }
}
?>

<!-- Card de Registro (Layout da Imagem) -->
<section class="bg-white p-8 rounded-xl shadow-lg w-full max-w-sm">
   <form action="" method="post">
      <h3 class="text-3xl font-bold text-center text-orange-500 mb-6">AchePet! <span class="text-lg block">(Admin)</span></h3>

      <div class="mb-4">
         <label for="email" class="block text-sm font-medium text-gray-700 sr-only">Email do Administrador</label>
         <input type="email" id="email" name="email" required placeholder="Email do Administrador" class="box w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400">
      </div>
      
      <div class="mb-4">
         <label for="pass" class="block text-sm font-medium text-gray-700 sr-only">Senha</label>
         <input type="password" id="pass" name="pass" required placeholder="Senha" class="box w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400">
      </div>

      <div class="mb-4">
         <label for="cpass" class="block text-sm font-medium text-gray-700 sr-only">Confirme a senha</label>
         <input type="password" id="cpass" name="cpass" required placeholder="Confirme a senha" class="box w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-400">
      </div>
      
      <input type="submit" value="CADASTRAR ADMIN" name="submit" class="btn w-full bg-orange-500 text-white p-3 rounded-md font-bold cursor-pointer hover:bg-orange-600 transition-colors">
      
      <p class="text-center text-sm text-gray-600 mt-4">
         Já tem conta? <a href="admin_login.php" class="text-blue-600 hover:underline">Faça login</a>
      </p>
   </form>
</section>

<!-- Script para Font Awesome (ícone de fechar a msg de erro) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/js/all.min.js"></script>
</body>
</html>

