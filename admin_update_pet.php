<?php
include 'config.php';
session_start();

// Trava de segurança: Se o admin não estiver logado, chuta para o login.
$admin_id = $_SESSION['admin_id'] ?? null;
if(!isset($admin_id)){
   header('location:admin_login.php');
   exit;
}

// Pega o ID do pet da URL (?id=...)
$pet_id = $_GET['id'] ?? 0;
if($pet_id == 0){
    header('location:admin_pets.php');
    exit;
}

$message = []; // Array para guardar mensagens de feedback

// --- LÓGICA DE UPDATE (Quando o formulário é enviado) ---
if(isset($_POST['submit'])){
   
   // Coleta e limpa todos os dados do formulário
   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
   $genero = filter_var($_POST['genero'], FILTER_SANITIZE_STRING);
   $especie = filter_var($_POST['especie'], FILTER_SANITIZE_STRING);
   $raca = filter_var($_POST['raca'], FILTER_SANITIZE_STRING);
   $idade = filter_var($_POST['idade'], FILTER_SANITIZE_STRING);
   $porte = filter_var($_POST['porte'], FILTER_SANITIZE_STRING);
   $cor_predominante = filter_var($_POST['cor_predominante'], FILTER_SANITIZE_STRING);
   $cor_olhos = filter_var($_POST['cor_olhos'], FILTER_SANITIZE_STRING);
   $data_desaparecimento = filter_var($_POST['data_desaparecimento'], FILTER_SANITIZE_STRING);
   $local_desaparecimento = filter_var($_POST['local_desaparecimento'], FILTER_SANITIZE_STRING);
   $ponto_referencia = filter_var($_POST['ponto_referencia'], FILTER_SANITIZE_STRING);
   $comentario_tutor = filter_var($_POST['comentario_tutor'], FILTER_SANITIZE_STRING);
   
   // Campos booleanos (0 ou 1)
   $paga_recompensa = filter_var($_POST['paga_recompensa'], FILTER_VALIDATE_INT);
   $destaque = filter_var($_POST['destaque'], FILTER_VALIDATE_INT);

   try {
        $update_pet = $conn->prepare("UPDATE `pets` SET 
            nome = ?, status = ?, genero = ?, especie = ?, raca = ?, idade = ?, porte = ?, 
            cor_predominante = ?, cor_olhos = ?, data_desaparecimento = ?, 
            local_desaparecimento = ?, ponto_referencia = ?, comentario_tutor = ?, 
            paga_recompensa = ?, destaque = ?
            WHERE id = ?");
            
        $update_pet->execute([
            $name, $status, $genero, $especie, $raca, $idade, $porte,
            $cor_predominante, $cor_olhos, $data_desaparecimento,
            $local_desaparecimento, $ponto_referencia, $comentario_tutor,
            $paga_recompensa, $destaque, $pet_id
        ]);
        
        $message[] = 'Perfil do pet atualizado com sucesso!';

   } catch (Exception $e) {
        $message[] = 'Erro ao atualizar o perfil: ' . $e->getMessage();
   }
}

// --- LÓGICA DE GET (para preencher o formulário) ---
// Busca os dados atuais do pet para exibir no formulário
try {
    $select_pet = $conn->prepare("SELECT * FROM `pets` WHERE id = ?");
    $select_pet->execute([$pet_id]);
    $pet = $select_pet->fetch(PDO::FETCH_ASSOC);

    if(!$pet){
        // Se o pet não existir, volta para a lista
        header('location:admin_pets.php');
        exit;
    }
    
    // --- NOVO: Busca a primeira foto do pet ---
    $pet_image_url = 'https://placehold.co/400x300/E2E8F0/333?text=Sem+Foto'; // Padrão
    $select_image = $conn->prepare("SELECT img_url FROM `pet_images` WHERE pet_id = ? LIMIT 1");
    $select_image->execute([$pet_id]);
    $image_result = $select_image->fetch(PDO::FETCH_ASSOC);

    if($image_result && !empty($image_result['img_url'])){
        $pet_image_url = $image_result['img_url']; // Usa o link da internet
    }
    
} catch (Exception $e) {
    die('Erro ao buscar pet: ' . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil do Pet - AchePet</title>
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
    <header class="flex justify-between items-center w-full px-4 py-3 bg-white shadow-sm">
        <a href="admin_pets.php" class="text-sky-700 text-2xl">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-extrabold">
            <span class="text-orange-600">ACHE</span><span class="text-yellow-500">PET!</span>
        </h1>
        <div class="w-8 h-8"></div> <!-- Espaçador -->
    </header>

    <!-- Conteúdo do Formulário -->
    <div class="flex flex-col items-center p-6 pt-10">
        
        <h2 class="text-2xl font-bold text-sky-900 mb-6">Atualizar Perfil do Pet</h2>

        <!-- NOVO: Visualização da Imagem do Pet -->
        <div class="mb-6 w-full max-w-2xl">
            <label class="block text-sm font-semibold text-gray-600 mb-2 text-center">Imagem Atual</label>
            <div class="flex justify-center">
                <img src="<?php echo htmlspecialchars($pet_image_url); ?>" 
                     alt="Foto do <?php echo htmlspecialchars($pet['nome']); ?>" 
                     class="w-48 h-48 rounded-lg object-cover border-4 border-white shadow-lg"
                     onerror="this.src='https://placehold.co/400x300/E2E8F0/333?text=Erro'">
            </div>
        </div>

        <!-- Mensagens de Feedback (Sucesso ou Erro) -->
        <?php
        if(!empty($message)){
            foreach($message as $msg){
                $is_success = strpos(strtolower($msg), 'sucesso') !== false;
                $msg_class = $is_success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                echo '<div class="w-full max-w-2xl p-4 mb-4 text-center rounded-lg '.$msg_class.'">'.$msg.'</div>';
            }
        }
        ?>

        <!-- Formulário de Edição (max-w-2xl para mais espaço) -->
        <form action="" method="post" class="w-full max-w-2xl bg-white p-6 rounded-xl shadow-lg">
            
            <!-- Grid para 2 colunas no desktop -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
            
                <!-- Campo Nome do Pet -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Nome do Pet</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($pet['nome']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Status -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="Perdido" <?php if($pet['status'] == 'Perdido') echo 'selected'; ?>>Perdido</option>
                        <option value="Encontrado" <?php if($pet['status'] == 'Encontrado') echo 'selected'; ?>>Encontrado</option>
                        <option value="Adoção" <?php if($pet['status'] == 'Adoção') echo 'selected'; ?>>Adoção</option>
                    </select>
                </div>

                <!-- Campo Gênero -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Gênero</label>
                    <select name="genero" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="Macho" <?php if($pet['genero'] == 'Macho') echo 'selected'; ?>>Macho</option>
                        <option value="Fêmea" <?php if($pet['genero'] == 'Fêmea') echo 'selected'; ?>>Fêmea</option>
                        <option value="Não sei" <?php if($pet['genero'] == 'Não sei') echo 'selected'; ?>>Não sei</option>
                    </select>
                </div>

                <!-- Campo Espécie -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Espécie (Gato/Cachorro)</label>
                    <input type="text" name="especie" value="<?php echo htmlspecialchars($pet['especie']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Raça -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Raça</label>
                    <input type="text" name="raca" value="<?php echo htmlspecialchars($pet['raca']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Idade -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Idade</label>
                    <input type="text" name="idade" value="<?php echo htmlspecialchars($pet['idade']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Porte -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Porte</label>
                    <input type="text" name="porte" value="<?php echo htmlspecialchars($pet['porte']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Cor Predominante -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Cor Predominante</label>
                    <input type="text" name="cor_predominante" value="<?php echo htmlspecialchars($pet['cor_predominante']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Cor dos Olhos -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Cor dos Olhos</label>
                    <input type="text" name="cor_olhos" value="<?php echo htmlspecialchars($pet['cor_olhos']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Data Desaparecimento -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Data Desaparecimento</label>
                    <input type="text" name="data_desaparecimento" value="<?php echo htmlspecialchars($pet['data_desaparecimento']); ?>" 
                           placeholder="DD/MM/AAAA"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                
                <!-- Campo Paga Recompensa? -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Paga Recompensa?</label>
                    <select name="paga_recompensa" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="1" <?php if($pet['paga_recompensa'] == 1) echo 'selected'; ?>>Sim</option>
                        <option value="0" <?php if($pet['paga_recompensa'] == 0) echo 'selected'; ?>>Não</option>
                    </select>
                </div>
                
                <!-- Campo Destaque? -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Pet em Destaque?</label>
                    <select name="destaque" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="1" <?php if($pet['destaque'] == 1) echo 'selected'; ?>>Sim</option>
                        <option value="0" <?php if($pet['destaque'] == 0) echo 'selected'; ?>>Não</option>
                    </select>
                </div>

                <!-- Campo Local Desaparecimento (Full Width) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Local Desaparecimento</label>
                    <input type="text" name="local_desaparecimento" value="<?php echo htmlspecialchars($pet['local_desaparecimento']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Ponto de Referência (Full Width) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Ponto de Referência</label>
                    <input type="text" name="ponto_referencia" value="<?php echo htmlspecialchars($pet['ponto_referencia']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                
                <!-- Campo Comentário (Full Width) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Comentário do Tutor</label>
                    <textarea name="comentario_tutor" rows="4" 
                              class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500"><?php echo htmlspecialchars($pet['comentario_tutor']); ?></textarea>
                </div>

            </div> <!-- Fim do Grid -->

            <!-- Botão Salvar (Full Width) -->
            <button type="submit" name="submit" 
                    class="w-full bg-orange-500 text-white font-bold py-3 rounded-lg shadow hover:bg-orange-600 transition-all mt-6">
                Salvar Alterações
            </button>
            
        </form>
    </div>

</body>
</html>

