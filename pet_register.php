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
    
    // --- MODIFICADO: Captura de Latitude e Longitude ---
    $latitude = filter_var($_POST['latitude'], FILTER_SANITIZE_STRING);
    $longitude = filter_var($_POST['longitude'], FILTER_SANITIZE_STRING);

    if(empty($latitude) || empty($longitude)){
        $message[] = 'Você precisa marcar a localização exata no mapa!';
    }
    // --- FIM DA MODIFIFICAÇÃO ---

    // --- LÓGICA DE UPLOAD DA IMAGEM (MODIFICADA) ---
    // A imagem JÁ FOI ENVIADA (AJAX) e salva pelo ajax_upload_image.php.
    // Estamos apenas pegando o CAMINHO (path) que o JS colocou no input escondido.
    $image_path_to_db = filter_var($_POST['imagePath'], FILTER_SANITIZE_STRING); // <-- MODIFICADO

    if (empty($image_path_to_db)) {
        $message[] = 'Erro: Nenhuma imagem foi enviada. Por favor, volte e tente novamente.';
    } elseif (!file_exists($image_path_to_db)) {
        // Checagem de segurança
        $message[] = 'Erro: Arquivo de imagem não encontrado no servidor. Tente novamente.';
        $image_path_to_db = ''; // Limpa para não salvar um link quebrado
    }
    // --- FIM DA LÓGICA MODIFICADA ---

    // Se não houver mensagens de erro, continua
    if(empty($message)) {
         try {
             // 1. Insere o Pet na tabela `pets` (MODIFICADO para incluir lat/lon)
             $insert_pet = $conn->prepare("INSERT INTO `pets`
                 (tutor_id, nome, status, genero, especie, raca, idade, porte, cor_predominante, cor_olhos, 
                  data_desaparecimento, local_desaparecimento, ponto_referencia, 
                  latitude, longitude, /* <-- NOVAS COLUNAS */
                  comentario_tutor, contato_telefone,
                  paga_recompensa, destaque, id_achepet, atualizado_em) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); // <-- 21 VALORES
            
             $id_achepet = 'ACHE' . rand(10000, 99999);
             $atualizado_em = 'agora';
            
             $insert_pet->execute([
                 $tutor_id, $name, $status, $genero, $especie, $raca, $idade, $porte,
                 $cor_predominante, $cor_olhos, $data_desaparecimento,
                 $local_desaparecimento, $ponto_referencia,
                 $latitude, $longitude, // <-- NOVOS VALORES
                 $comentario_tutor, $contato_telefone, 
                 $paga_recompensa, $destaque, $id_achepet, $atualizado_em
             ]); // <-- 21 VALORES
            
             $new_pet_id = $conn->lastInsertId();

             // 2. Insere a imagem na tabela `pet_images` (LÓGICA MODIFICADA)
             if ($new_pet_id && !empty($image_path_to_db)) { // <-- Condição modificada
                 $insert_image = $conn->prepare("INSERT INTO `pet_images` (pet_id, img_url) VALUES (?, ?)");
                 // $image_path_to_db já é o caminho correto (ex: "uploaded_img/pet_123.jpg")
                 $insert_image->execute([$new_pet_id, $image_path_to_db]); // <-- Variável modificada
             }

             // 3. Redireciona para o perfil do novo pet
             header('location:perfil_pet.php?id=' . $new_pet_id);
             exit;

         } catch (Exception $e) {
             $message[] = 'Erro ao cadastrar o pet: ' . $e->getMessage();
             // Boa prática: se o cadastro falhar, remove a imagem órfã
             if (!empty($image_path_to_db)) {
                 @unlink($image_path_to_db); // <-- MODIFICADO
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
    
    <!-- *** CSS do Mapa Leaflet *** -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
    <!-- *** FIM DO CSS *** -->

    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background-color: #E0F2FE; /* bg-sky-100 */
        }
        /* *** Estilo para o Mapa *** */
        #map {
            height: 300px; /* Altura do mapa */
            z-index: 10;   /* Garante que o mapa fique visível */
        }
        /* *** FIM DO ESTILO *** */
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
            <label class="block text-sm font-semibold text-gray-600 mb-2 text-center">Foto Selecionada</label> <!-- Texto modificado -->
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
                 // *** MODIFICADO: Adicionado ID para controle do JS ***
                echo '<div class="w-full max-w-2xl p-4 mb-4 text-center rounded-lg bg-red-100 text-red-700" id="error-message">'.$msg.'</div>';
            }
        }
        ?>
        <!-- *** NOVO: Placeholder para mensagens de JS *** -->
        <div id="js-message-placeholder" class="w-full max-w-2xl"></div>


        <!-- Formulário de Cadastro -->
        <form action="" method="post" class="w-full max-w-2xl bg-white p-6 rounded-xl shadow-lg">
            
            <!-- MODIFICADO: Input escondido para enviar o *caminho* da imagem -->
            <input type="hidden" name="imagePath" id="imagePathInput">

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
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Endereço de Referência</label>
                     <!-- *** MODIFICADO: Adicionado ID 'addressInput' *** -->
                    <input type="text" name="local_desaparecimento" id="addressInput" placeholder="Ex: Rua A, Bairro B, São Paulo (Apenas texto)" required
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                    
                    <!-- *** NOVO BOTÃO DE BUSCA *** -->
                    <button type="button" id="searchAddressButton"
                            class="w-full mt-2 bg-sky-600 text-white font-semibold py-2 px-4 rounded-lg shadow hover:bg-sky-700 transition-all">
                        <i class="fas fa-search mr-2"></i>Buscar Endereço no Mapa
                    </button>
                    <!-- *** FIM DO BOTÃO *** -->
                </div>

                <!-- Campo Ponto de Referência (Full Width) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Ponto de Referência</label>
                    <input type="text" name="ponto_referencia" placeholder="Ex: Perto do mercado X"
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                
                <!-- *** Seção do Mapa (Full Width) *** -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Marque no Mapa (Obrigatório)</label>
                    <p class="text-xs text-gray-500 mb-2">Clique no local exato onde o pet foi visto. Dê zoom para mais precisão.</p>
                    <div id="map" class="w-full h-72 rounded-lg border border-gray-300"></div>
                    <!-- Inputs escondidos para as coordenadas -->
                    <input type="hidden" name="latitude" id="latitudeInput">
                    <input type="hidden" name="longitude" id="longitudeInput">
                </div>
                <!-- *** FIM DA SEÇÃO DO MAPA *** -->

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

    <!-- *** JS do Mapa Leaflet *** -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
    <!-- *** FIM DO JS DO MAPA *** -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // *** (Início do seu código JavaScript existente - Lógica da Imagem) ***
            
            const imagePath = sessionStorage.getItem('newPetImagePath'); 

            if (imagePath) {
                document.getElementById('pet-preview').src = imagePath; 
                document.getElementById('imagePathInput').value = imagePath; 
                sessionStorage.removeItem('newPetImagePath'); 
            } else {
                document.getElementById('pet-preview').src = 'https://placehold.co/400x300/FEE2E2/B91C1C?text=ERRO!+Tire+a+foto+pela+página+inicial.';
                const submitButton = document.querySelector('button[name="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'ERRO: VOLTE E TIRE A FOTO';
                    submitButton.classList.add('bg-gray-400');
                    submitButton.classList.remove('bg-orange-500', 'hover:bg-orange-600');
                }
            }
            // *** (Fim do seu código JavaScript existente) ***


            // --- INÍCIO: Script do Mapa Leaflet (LÓGICA ATUALIZADA) ---
            
            // Pega os inputs escondidos
            const latInput = document.getElementById('latitudeInput');
            const lonInput = document.getElementById('longitudeInput');
            const mapElement = document.getElementById('map');
            let marker = null;
            let map;

            // *** NOVOS ELEMENTOS ***
            const searchButton = document.getElementById('searchAddressButton');
            const addressInput = document.getElementById('addressInput'); // <-- ID do campo de endereço
            const messagePlaceholder = document.getElementById('js-message-placeholder'); // <-- Div de mensagens


            // Tenta centralizar no Brasil, ou usa uma localização padrão
            const defaultCenter = [-14.2350, -51.9253]; // Centro do Brasil

            // Z-Index: Garante que o mapa não conflite com outros elementos
            mapElement.style.zIndex = '10';

            try {
                map = L.map(mapElement).setView(defaultCenter, 5); // 5 = zoom
            } catch (e) {
                console.error("Erro ao iniciar o mapa:", e);
                mapElement.innerHTML = "Erro ao carregar o mapa. Tente recarregar a página.";
                return;
            }

            // Adiciona a camada de mapa (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Tenta pegar a localização do usuário para centralizar
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const userPos = [position.coords.latitude, position.coords.longitude];
                    map.setView(userPos, 13); // Zoom maior perto do usuário
                }, function() {
                    // Usuário negou ou falhou, continua no centro do Brasil
                    console.log("Falha ao obter geolocalização. Usando padrão.");
                });
            }

            // *** FUNÇÃO MODIFICADA: Atualiza os inputs E o marcador ***
            function updateMapPin(lat, lon, zoomLevel = 15) {
                // Atualiza os inputs do formulário
                latInput.value = lat.toFixed(7);
                lonInput.value = lon.toFixed(7);

                // Remove o marcador antigo se existir
                if (marker) {
                    map.removeLayer(marker);
                }

                // Adiciona um novo marcador
                marker = L.marker([lat, lon]).addTo(map)
                    .bindPopup('<b>Local selecionado!</b>')
                    .openPopup();
                
                // Centraliza o mapa no novo pino
                map.setView([lat, lon], zoomLevel);
            }

            // Adiciona o listener de clique no mapa (chama a nova função)
            map.on('click', function(e) {
                updateMapPin(e.latlng.lat, e.latlng.lng);
            });


            // *** NOVA FUNÇÃO: Buscar Endereço (Geocodificação) ***
            searchButton.addEventListener('click', async function() {
                const address = addressInput.value;
                if (!address) {
                    showMessage('Por favor, digite um endereço para buscar.', 'error');
                    return;
                }

                searchButton.disabled = true;
                searchButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Buscando...';
                showMessage(''); // Limpa mensagens antigas

                try {
                    // Usamos o serviço gratuito Nominatim do OpenStreetMap
                    // 'mailto=' é uma exigência da política de uso para informar um email de contato
                    // **IMPORTANTE**: Troque 'seu-email-aqui@dominio.com' pelo seu email
                    const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=json&limit=1&email=aranhapvp2@hotmail.com`);
                    
                    if (!response.ok) {
                        throw new Error('Serviço de busca de endereço indisponível.');
                    }
                    
                    const data = await response.json();

                    if (data && data.length > 0) {
                        const result = data[0];
                        const lat = parseFloat(result.lat);
                        const lon = parseFloat(result.lon);
                        
                        // SUCESSO! Atualiza o mapa
                        updateMapPin(lat, lon, 16); // Zoom mais próximo para busca
                        showMessage('Endereço encontrado! Verifique o pino e ajuste se necessário.', 'success');

                    } else {
                        // Não encontrou
                        showMessage('Endereço não encontrado. Por favor, seja mais específico ou clique manualmente no mapa.', 'error');
                    }

                } catch (error) {
                    console.error('Erro na Geocodificação:', error);
                    showMessage('Erro ao buscar endereço. Verifique sua conexão ou clique manualmente.', 'error');
                } finally {
                    searchButton.disabled = false;
                    searchButton.innerHTML = '<i class="fas fa-search mr-2"></i>Buscar Endereço no Mapa';
                }
            });

            // *** NOVA FUNÇÃO: Mostrar Mensagens (para o JS) ***
            function showMessage(msg, type = 'success') {
                // Remove qualquer mensagem de erro do PHP para não duplicar
                const phpError = document.getElementById('error-message');
                if (phpError) phpError.style.display = 'none';

                if (!msg) {
                    messagePlaceholder.innerHTML = ''; // Limpa a mensagem
                    return;
                }
                
                const bgColor = type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                messagePlaceholder.innerHTML = `<div class="w-full p-4 mb-4 text-center rounded-lg ${bgColor}">${msg}</div>`;
            }

            // --- FIM: Script do Mapa Leaflet ---
        });
    </script>

</body>
</html>