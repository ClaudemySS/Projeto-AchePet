<?php
include 'config.php';
session_start();

if(isset($_POST['submit'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $pass = md5($_POST['password']);
    $select = mysqli_query($conn, "SELECT * FROM `users` WHERE email = '$email' AND password = '$pass'") or die('query failed');
    if(mysqli_num_rows($select) > 0){
        $row = mysqli_fetch_assoc($select);
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_name'] = $row['name'];
        header('location:index.php');
    }else{
        $message = 'Email ou senha incorretos!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - AchePet</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-sky-100 flex items-center justify-center min-h-screen">
  <div class="bg-white p-8 rounded-lg shadow-lg w-80">
    <h1 class="text-2xl font-bold text-yellow-500 mb-4 text-center">Ache<span class="text-orange-600">Pet!</span></h1>
    <?php if(isset($message)) echo '<p class="text-red-500 mb-3">'.$message.'</p>'; ?>
    <form action="" method="post" class="space-y-4">
      <input type="email" name="email" placeholder="Email" required class="w-full p-2 border rounded">
      <input type="password" name="password" placeholder="Senha" required class="w-full p-2 border rounded">
      <input type="submit" name="submit" value="Entrar" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded cursor-pointer">
    </form>
    <p class="mt-4 text-sm text-center">Não tem conta? <a href="user_register.php" class="text-blue-600">Cadastre-se</a></p>
  </div>
</body>
</html>
