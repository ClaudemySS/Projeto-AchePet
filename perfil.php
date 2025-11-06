<?php
include 'config.php';
session_start();

// Trava de segurança: Se o usuário não estiver logado, chuta para o login.
$user_id = $_SESSION['user_id'] ?? null;
if(!isset($user_id)){
   header('location:user_login.php');
   exit;
}

// Busca os dados mais recentes do usuário no banco
try {
    $select_user = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
    $select_user->execute([$user_id]);
    $fetch_user = $select_user->fetch(PDO::FETCH_ASSOC);

    // Se o usuário não for encontrado (raro, mas possível), desloga.
    if(!$fetch_user){
        session_destroy();
        header('location:user_login.php');
        exit;
    }

} catch (Exception $e) {
    // Se a tabela 'users' tiver mudado (ex: 'telefone' não existe),
    // usa os dados da sessão para não quebrar a página.
    $fetch_user = [
        'name' => $_SESSION['user_name'] ?? 'Usuário',
        'email' => $_SESSION['user_email'] ?? 'Sem email',
        'telefone' => '(Execute o SQL)', // Mensagem de erro
        'foto_perfil' => null
    ];
}


// Define as variáveis para o HTML
$nome = $fetch_user['name'];
$email = $fetch_user['email'];
$telefone = $fetch_user['telefone'] ?? 'Não cadastrado'; // Usa o novo campo
$foto_perfil = $fetch_user['foto_perfil'] ?? 'https://placehold.co/150x150/E2E8F0/333?text=👤'; // Placeholder

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - AchePet</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Fonte Inter (para combinar com o login) -->
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
        
        <!-- Botão Voltar (Arrow Left) -->
        <a href="index.php" class="text-sky-700 text-2xl">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <!-- Logo -->
        <h1 class="text-2xl font-extrabold">
            <span class="text-orange-600">ACHE</span><span class="text-yellow-500">PET!</span>
        </h1>
        
        <!-- Ícone de Perfil (placeholder) -->
        <div class="w-8 h-8 flex items-center justify-center text-sky-700 text-2xl opacity-0">
             <i class="fas fa-user"></i>
        </div>
    </header>

    <!-- Conteúdo do Perfil -->
    <div class="flex flex-col items-center p-6 pt-10">
        
        <!-- Imagem de Perfil -->
        <img src="<?php echo htmlspecialchars($foto_perfil); ?>" 
             alt="Foto de Perfil" 
             class="w-32 h-32 md:w-40 md:h-40 rounded-full object-cover border-4 border-white shadow-lg mb-4"
             onerror="this.src='https://placehold.co/150x150/E2E8F0/333?text=👤'">
        
        <!-- Nome -->
        <h2 class="text-2xl font-bold text-sky-900 mb-8"><?php echo htmlspecialchars($nome); ?></h2>

        <!-- Container de Informações -->
        <div class="w-full max-w-md">
            
            <!-- Campo E-mail -->
            <div class="mb-4">
                <label class="text-sm font-semibold text-gray-600 ml-4">E-mail</label>
                <div class="bg-white p-4 rounded-full shadow-md text-gray-800 text-center text-lg">
                    <?php echo htmlspecialchars($email); ?>
                </div>
            </div>
            
            <!-- Campo Telefone -->
            <div class="mb-6">
                <label class="text-sm font-semibold text-gray-600 ml-4">Telefone</label>
                <div class="bg-white p-4 rounded-full shadow-md text-gray-800 text-center text-lg">
                    <?php echo htmlspecialchars($telefone); ?>
                </div>
            </div>
        </div>

        <!-- CORREÇÃO: Link de Edição agora aponta para o arquivo de update -->
        <a href="user_update_profile.php" class="text-blue-600 hover:underline font-medium mt-4">
            Editar Perfil
        </a>
        
        <!-- NOVO: Botão de Deslogar (aponta para o logout.php do seu projeto) -->
        <a href="logout.php" class="mt-6 bg-red-500 text-white font-semibold py-2 px-6 rounded-lg shadow hover:bg-red-600 transition-all">
            <i class="fas fa-sign-out-alt mr-2"></i>Deslogar
        </a>
    </div>

</body>
</html>

