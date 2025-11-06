<?php
include 'config.php';
session_start();

// Trava de segurança: Se o usuário não estiver logado, chuta para o login.
$user_id = $_SESSION['user_id'] ?? null;
if(!isset($user_id)){
   header('location:user_login.php');
   exit;
}

$message = []; // Array para guardar mensagens de feedback

// Lógica de UPDATE (quando o formulário é enviado)
if(isset($_POST['submit'])){
   
   // Limpa e pega os dados do formulário
   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
   $telefone = filter_var($_POST['telefone'], FILTER_SANITIZE_STRING);
   
   // Validação simples
   if(empty($name) || empty($email)){
        $message[] = 'Nome e E-mail são obrigatórios!';
   } else {
        // Tenta atualizar os dados
        try {
            $update_user = $conn->prepare("UPDATE `users` SET name = ?, email = ?, telefone = ? WHERE id = ?");
            $update_user->execute([$name, $email, $telefone, $user_id]);
            
            // Atualiza os dados da sessão também
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            $message[] = 'Perfil atualizado com sucesso!';

        } catch (Exception $e) {
            $message[] = 'Erro ao atualizar o perfil: ' . $e->getMessage();
        }
   }
}

// Lógica de GET (para preencher o formulário)
// Busca os dados atuais do usuário para exibir no formulário
try {
    $select_user = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
    $select_user->execute([$user_id]);
    $fetch_user = $select_user->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Falha crítica, volta ao login
    session_destroy();
    header('location:user_login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - AchePet</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Fonte Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome (Ícones) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background-color: #E0F2FE; /* bg-sky-100 */
        }
    </style>
</head>
<body class="min-h-screen">

    <!-- Header (Cabeçalho) -->
    <header class="flex justify-between items-center w-full px-4 py-3 bg-white shadow-sm">
        <a href="perfil.php" class="text-sky-700 text-2xl">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-extrabold">
            <span class="text-orange-600">ACHE</span><span class="text-yellow-500">PET!</span>
        </h1>
        <div class="w-8 h-8"></div> <!-- Espaçador -->
    </header>

    <!-- Conteúdo do Formulário -->
    <div class="flex flex-col items-center p-6 pt-10">
        
        <h2 class="text-2xl font-bold text-sky-900 mb-8">Editar Perfil</h2>

        <!-- Mensagens de Feedback (Sucesso ou Erro) -->
        <?php
        if(!empty($message)){
            foreach($message as $msg){
                // Muda a cor da mensagem com base no conteúdo
                $is_success = strpos(strtolower($msg), 'sucesso') !== false;
                $msg_class = $is_success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                echo '<div class="w-full max-w-md p-4 mb-4 text-center rounded-lg '.$msg_class.'">'.$msg.'</div>';
            }
        }
        ?>

        <!-- Formulário de Edição -->
        <form action="" method="post" class="w-full max-w-md bg-white p-6 rounded-xl shadow-lg">
            
            <!-- Campo Nome -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Seu nome</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($fetch_user['name']); ?>" 
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            
            <!-- Campo E-mail -->
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-600 mb-1">E-mail</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($fetch_user['email']); ?>" 
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            
            <!-- Campo Telefone -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-600 mb-1">Telefone</label>
                <input type="text" name="telefone" value="<?php echo htmlspecialchars($fetch_user['telefone'] ?? ''); ?>" 
                       placeholder="Ex: (11) 98765-4321"
                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>

            <!-- Botão Salvar -->
            <button type="submit" name="submit" 
                    class="w-full bg-orange-500 text-white font-bold py-3 rounded-lg shadow hover:bg-orange-600 transition-all">
                Salvar Alterações
            </button>
            
        </form>
    </div>

</body>
</html>

