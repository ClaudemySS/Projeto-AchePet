<?php
include 'config.php';
session_start();

// Trava de segurança: Se o usuário NÃO estiver logado, chuta para o login.
$user_id = $_SESSION['user_id'] ?? null;
if(!isset($user_id)){
   header('location:user_login.php');
   exit;
}

// --- Pega os dados do usuário logado para exibir no formulário ---
$tutor_id = $user_id; 
$tutor_name = $_SESSION['user_name'] ?? 'Usuário';
$tutor_email = $_SESSION['user_email'] ?? 'Email';
// -------------------------------------------------------------------

$message = []; // Array para guardar mensagens de feedback

// --- LÓGICA DE CADASTRO (Quando o formulário é enviado) ---
if(isset($_POST['submit'])){
   
   // Coleta todos os dados do formulário
   $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
   
   // --- CAMPO OBRIGATÓRIO DE TELEFONE ---
   $contato_telefone_raw = $_POST['contato_telefone'] ?? '';
   // Limpa caracteres não numéricos (parênteses, traços, espaços)
   $contato_telefone_digits = preg_replace('/[^0-9]/', '', $contato_telefone_raw);

   if(empty($contato_telefone_digits)){
       $message[] = 'O campo Telefone de Contato é obrigatório!';
   } 
   // --- VALIDAÇÃO DE 11 DÍGITOS ---
   elseif(strlen($contato_telefone_digits) != 11){
       $message[] = 'Telefone inválido! Por favor, inclua 2 dígitos do DDD e 9 dígitos do número (Total de 11 dígitos).';
   }
   // ------------------------------------
   
   // Sanitiza a versão original (com máscara) para salvar no banco
   $contato_telefone = filter_var($contato_telefone_raw, FILTER_SANITIZE_STRING);
   // -------------------------------

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
   $paga_recompensa = filter_var($_POST['paga_recompensa'], FILTER_VALIDATE_INT);
   $destaque = filter_var($_POST['destaque'], FILTER_VALIDATE_INT);
   
   // --- LÓGICA DE UPLOAD DA IMAGEM ---
   $image_data_b64 = $_POST['imageData'] ?? null;
   $image_filename = '';

   if ($image_data_b64) {
       if (preg_match('/^data:image\/(\w+);base64,/', $image_data_b64, $type)) {
           $data = substr($image_data_b64, strpos($image_data_b64, ',') + 1);
           $image_data = base64_decode($data);
           $type = strtolower($type[1]); // jpg, png, gif

           if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
               $message[] = 'Formato de imagem inválido.';
           } else {
               $image_filename = uniqid('pet_', true) . '.' . $type;
               if(!file_put_contents('uploaded_img/' . $image_filename, $image_data)){
                   $message[] = 'Erro ao salvar a imagem no servidor.';
               }
           }
       } else {
           $message[] = 'Erro: Formato de dados da imagem inválido.';
       }
   } else {
       $message[] = 'Erro: Nenhuma imagem foi enviada.';
   }

   // Se não houver mensagens de erro, continua
   if(empty($message)) {
        try {
            // 1. Insere o Pet na tabela `pets`
            $insert_pet = $conn->prepare("INSERT INTO `pets`
                (tutor_id, nome, status, genero, especie, raca, idade, porte, cor_predominante, cor_olhos, 
                 data_desaparecimento, local_desaparecimento, ponto_referencia, comentario_tutor, contato_telefone,
                 paga_recompensa, destaque, id_achepet, atualizado_em) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $id_achepet = 'ACHE' . rand(10000, 99999);
            $atualizado_em = 'agora';
            
            $insert_pet->execute([
                $tutor_id, $name, $status, $genero, $especie, $raca, $idade, $porte,
                $cor_predominante, $cor_olhos, $data_desaparecimento,
                $local_desaparecimento, $ponto_referencia, $comentario_tutor, $contato_telefone, // Salva o telefone com máscara
                $paga_recompensa, $destaque, $id_achepet, $atualizado_em
            ]);
            
            $new_pet_id = $conn->lastInsertId();

            // 2. Insere a imagem na tabela `pet_images`
            if ($new_pet_id && !empty($image_filename)) {
                $insert_image = $conn->prepare("INSERT INTO `pet_images` (pet_id, img_url) VALUES (?, ?)");
                $image_path_to_db = 'uploaded_img/' . $image_filename;
                $insert_image->execute([$new_pet_id, $image_path_to_db]);
            }

            // 3. Redireciona para o perfil do novo pet
            header('location:perfil_pet.php?id=' . $new_pet_id);
            exit;

        } catch (Exception $e) {
            $message[] = 'Erro ao cadastrar o pet: ' . $e->getMessage();
            if (!empty($image_filename)) {
                @unlink('uploaded_img/' . $image_filename);
            }
        }
   }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Pet - AchePet</title>
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
        <a href="index.php" class="text-sky-700 text-2xl">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-2xl font-extrabold">
            <span class="text-orange-600">ACHE</span><span class="text-yellow-500">PET!</span>
        </h1>
        <div class="w-8 h-8"></div> <!-- Espaçador -->
    </header>

    <!-- Conteúdo do Formulário -->
    <div class="flex flex-col items-center p-6 pt-10">
        
        <h2 class="text-2xl font-bold text-sky-900 mb-6">Cadastrar Novo Pet</h2>

        <!-- Visualização da Imagem da Câmera -->
        <div class="mb-6 w-full max-w-2xl">
            <label class="block text-sm font-semibold text-gray-600 mb-2 text-center">Foto Tirada</label>
            <div class="flex justify-center">
                <img src="https://placehold.co/400x300/E2E8F0/333?text=Carregando+Foto..." 
                     alt="Foto do Pet" 
                     id="pet-preview" 
                     class="w-48 h-48 rounded-lg object-cover border-4 border-white shadow-lg">
            </div>
        </div>

        <!-- Mensagens de Feedback (Sucesso ou Erro) -->
        <?php
        if(!empty($message)){
            foreach($message as $msg){
                echo '<div class="w-full max-w-2xl p-4 mb-4 text-center rounded-lg bg-red-100 text-red-700">'.$msg.'</div>';
            }
        }
        ?>

        <!-- Formulário de Cadastro -->
        <form action="" method="post" class="w-full max-w-2xl bg-white p-6 rounded-xl shadow-lg">
            
            <!-- Input escondido para enviar a foto em Base64 -->
            <input type="hidden" name="imageData" id="imageData">

            <!-- --- Seção de Informações do Tutor --- -->
            <div class="mb-6 border-b border-gray-200 pb-4">
                <h3 class="text-lg font-semibold text-sky-800 mb-2">Informações do Tutor</h3>
                <div class="bg-sky-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600"><strong>Nome:</strong> <?php echo htmlspecialchars($tutor_name); ?></p>
                    <p class="text-sm text-gray-600"><strong>Email:</strong> <?php echo htmlspecialchars($tutor_email); ?></p>
                </div>
            </div>
            <!-- --------------------------------------- -->

            <h3 class="text-lg font-semibold text-sky-800 mb-4">Informações do Pet</h3>
            
            <!-- Grid para 2 colunas no desktop -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
            
                <!-- Campo Nome do Pet -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Nome do Pet</label>
                    <input type="text" name="name" required
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- --- CAMPO DE TELEFONE OBRIGATÓRIO (COM VALIDAÇÃO E MÁSCARA) --- -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Telefone de Contato (WhatsApp)</label>
                    <input type="tel" name="contato_telefone" required 
                           placeholder="(XX) 9XXXX-XXXX"
                           maxlength="15" 
                           pattern="\([0-9]{2}\) [0-9]{5}-[0-9]{4}"
                           title="Formato esperado: (XX) 9XXXX-XXXX"
                           oninput="this.value = this.value.replace(/\D/g, '').replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <!-- ----------------------------------------------------------- -->

                <!-- Campo Status -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="Perdido" selected>Perdido</option>
                        <option value="Encontrado">Encontrado</option>
                        <option value="Adoção">Adoção</option>
                    </select>
                </div>

                <!-- Campo Gênero -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Gênero</label>
                    <select name="genero" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="Macho">Macho</option>
                        <option value="Fêmea">Fêmea</option>
                        <option value="Não sei" selected>Não sei</option>
                    </select>
                </div>

                <!-- Campo Espécie -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Espécie (Gato/Cachorro)</label>
                    <input type="text" name="especie" placeholder="Ex: Cachorro" required
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Raça -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Raça</label>
                    <input type="text" name="raca" placeholder="Ex: Vira-lata (SRD)" required
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Idade -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Idade</label>
                    <input type="text" name="idade" placeholder="Ex: Adulto"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Porte -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Porte</label>
                    <input type="text" name="porte" placeholder="Ex: Pequeno"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Cor Predominante -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Cor Predominante</label>
                    <input type="text" name="cor_predominante" placeholder="Ex: Caramelo"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Cor dos Olhos -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Cor dos Olhos</label>
                    <input type="text" name="cor_olhos" placeholder="Ex: Castanhos"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                
                <!-- Campo Paga Recompensa? -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Paga Recompensa?</label>
                    <select name="paga_recompensa" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="0" selected>Não</option>
                        <option value="1">Sim</option>
                    </select>
                </div>
                
                <!-- Campo Destaque? -->
                <div>
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Pet em Destaque?</label>
                    <select name="destaque" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                        <option value="0" selected>Não (Padrão)</option>
                        <option value="1">Sim (Requer aprovação ADM)</option>
                    </select>
                </div>
                
                <!-- Campo Data Desaparecimento (Full Width) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Data (Perdido/Encontrado)</label>
                    <input type="text" name="data_desaparecimento" placeholder="Ex: 20/10/2025" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Local Desaparecimento (Full Width) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Local (Onde foi visto?)</label>
                    <input type="text" name="local_desaparecimento" placeholder="Ex: Rua A, Bairro B, São Paulo" required
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <!-- Campo Ponto de Referência (Full Width) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Ponto de Referência</label>
                    <input type="text" name="ponto_referencia" placeholder="Ex: Perto do mercado X"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                
                <!-- Campo Comentário (Full Width) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Comentário Adicional</label>
                    <textarea name="comentario_tutor" rows="4" placeholder="Ex: Estava usando coleira azul, mancava da pata direita..."
                              class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500"></textarea>
                </div>

            </div> <!-- Fim do Grid -->

            <!-- Botão Salvar (Full Width) -->
            <button type="submit" name="submit" 
                    class="w-full bg-orange-500 text-white font-bold py-3 rounded-lg shadow hover:bg-orange-600 transition-all mt-6">
                <i class="fas fa-check mr-2"></i> Cadastrar Pet
            </button>
            
        </form>
    </div>

    <!-- 
      JavaScript para pegar a imagem da câmera (do sessionStorage) 
      e colocá-la no formulário.
    -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Pega a imagem que o index.php salvou
            const imageData = sessionStorage.getItem('newPetImage');

            if (imageData) {
                // Mostra a imagem na tag <img>
                document.getElementById('pet-preview').src = imageData;
                
                // Coloca o texto Base64 no input escondido para ser enviado
                document.getElementById('imageData').value = imageData;
                
                // Limpa a memória para não usar a mesma foto duas vezes
                sessionStorage.removeItem('newPetImage');
            } else {
                // Se o usuário chegou aqui sem tirar foto, mostra um erro
                document.getElementById('pet-preview').src = 'https://placehold.co/400x300/FEE2E2/B91C1C?text=ERRO!+Tire+a+foto+pela+página+inicial.';
                // Você pode querer desabilitar o botão de submit aqui
                const submitButton = document.querySelector('button[name="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'ERRO: VOLTE E TIRE A FOTO';
                    submitButton.classList.add('bg-gray-400');
                    submitButton.classList.remove('bg-orange-500', 'hover:bg-orange-600');
                }
            }
        });
    </script>

</body>
</html>