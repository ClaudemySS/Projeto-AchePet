<?php
include 'config.php';
session_start();

// Trava de segurança: Se o admin não estiver logado, chuta para o login.
$admin_id = $_SESSION['admin_id'] ?? null;
if(!isset($admin_id)){
   header('location:admin_login.php');
   exit;
}

// --- LÓGICA DE DELETE ---
// Se o admin clicar em "Excluir" (passando ?delete=ID na URL)
if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   
   try {
       // 1. Deleta as imagens associadas na tabela 'pet_images'
       $delete_pet_images = $conn->prepare("DELETE FROM `pet_images` WHERE pet_id = ?");
       $delete_pet_images->execute([$delete_id]);

       // 2. Deleta o pet principal na tabela 'pets'
       $delete_pet = $conn->prepare("DELETE FROM `pets` WHERE id = ?");
       $delete_pet->execute([$delete_id]);

       // Redireciona de volta para a página limpa
       header('location:admin_pets.php');
       exit;

   } catch (Exception $e) {
       $message[] = 'Erro ao excluir o pet: ' . $e->getMessage();
   }
}

// --- LÓGICA DE SELECT (para exibir todos) ---
$select_pets = $conn->prepare("SELECT * FROM `pets` ORDER BY id DESC");
$select_pets->execute();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Pets - AchePet</title>
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
<body class="min-h-screen pb-12">

    <!-- Header (Cabeçalho) -->
    <header class="flex justify-between items-center w-full px-4 py-3 bg-white shadow-sm sticky top-0 z-50">
        <a href="admin_page.php" class="text-sky-700 text-2xl">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-extrabold">
            <span class="text-orange-600">ACHE</span><span class="text-yellow-500">PET!</span>
        </h1>
        <div class="w-8 h-8"></div> <!-- Espaçador -->
    </header>

    <!-- Conteúdo Principal -->
    <div class="flex flex-col items-center p-6 pt-10">
        
        <h2 class="text-2xl font-bold text-sky-900 mb-8">Gerenciar Perfis de Pets</h2>

        <!-- Container dos Cards -->
        <!-- 
          Layout responsivo:
          - 1 coluna no celular (padrão)
          - 2 colunas em telas médias (md:grid-cols-2)
          - 3 colunas em telas grandes (lg:grid-cols-3)
        -->
        <div class="w-full max-w-6xl grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <?php
            if($select_pets->rowCount() > 0){
                while($pet = $select_pets->fetch(PDO::FETCH_ASSOC)){
                    
                    // Lógica para buscar a imagem de cada pet
                    $pet_id_loop = $pet['id'];
                    $pet_image_url = 'https://placehold.co/400x300/E2E8F0/333?text=Sem+Foto'; // Padrão
                    
                    $select_image = $conn->prepare("SELECT img_url FROM `pet_images` WHERE pet_id = ? LIMIT 1");
                    $select_image->execute([$pet_id_loop]);
                    $image_result = $select_image->fetch(PDO::FETCH_ASSOC);

                    if($image_result && !empty($image_result['img_url'])){
                        $pet_image_url = $image_result['img_url'];
                    }
            ?>

            <!-- Card Individual do Pet -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden flex flex-col justify-between">
                
                <!-- Imagem -->
                <img src="<?php echo htmlspecialchars($pet_image_url); ?>" 
                     alt="Foto de <?php echo htmlspecialchars($pet['nome']); ?>" 
                     class="w-full h-48 object-cover"
                     onerror="this.src='https://placehold.co/400x300/E2E8F0/333?text=Erro'">
                
                <!-- Informações -->
                <div class="p-4">
                    <h3 class="text-xl font-bold text-sky-900 mb-2"><?php echo htmlspecialchars($pet['nome']); ?></h3>
                    
                    <p class="text-sm text-gray-600 mb-1">ID: <?php echo $pet['id']; ?></p>
                    
                    <!-- Lógica da Cor do Status -->
                    <?php
                        $status_color = 'text-gray-700';
                        if ($pet['status'] == 'Perdido') {
                            $status_color = 'text-red-600 font-semibold';
                        } elseif ($pet['status'] == 'Encontrado') {
                            $status_color = 'text-green-600 font-semibold';
                        }
                    ?>
                    <p class="text-md <?php echo $status_color; ?> mb-3">
                        Status: <?php echo htmlspecialchars($pet['status']); ?>
                    </p>
                    <p class="text-sm text-gray-500 line-clamp-2">
                        <?php echo htmlspecialchars($pet['local_desaparecimento']); ?>
                    </p>
                </div>

                <!-- Botões de Ação -->
                <div class="p-4 bg-gray-50 flex gap-4">
                    <!-- Link de Edição -->
                    <a href="admin_update_pet.php?id=<?php echo $pet['id']; ?>" class="flex-1 text-center bg-orange-500 text-white font-bold py-2 px-4 rounded-lg shadow hover:bg-orange-600 transition-all">
                        <i class="fas fa-edit mr-1"></i> Editar
                    </a>
                    
                    <!-- Link de Excluir (com confirmação JS) -->
                    <a href="admin_pets.php?delete=<?php echo $pet['id']; ?>" 
                       class="flex-1 text-center bg-red-600 text-white font-bold py-2 px-4 rounded-lg shadow hover:bg-red-700 transition-all"
                       onclick="return confirm('Tem certeza que quer excluir o perfil de <?php echo htmlspecialchars($pet['nome']); ?>? Esta ação não pode ser desfeita.');">
                       <i class="fas fa-trash mr-1"></i> Excluir
                    </a>
                </div>
            </div>
            <!-- Fim do Card -->

            <?php
                } // Fim do while loop
            } else {
                // Mensagem se não houver pets
            ?>
                <div class="bg-white p-10 rounded-lg shadow-lg col-span-full text-center">
                    <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-sky-900">Nenhum Pet Cadastrado</h3>
                    <p class="text-gray-600">Ainda não há perfis de pet no sistema.</p>
                </div>
            <?php
            } // Fim do if/else
            ?>

        </div> <!-- Fim do Grid -->
    </div> <!-- Fim do Conteúdo Principal -->

</body>
</html>

