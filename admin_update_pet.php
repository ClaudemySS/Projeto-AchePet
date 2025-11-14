<?php
include 'config.php';
session_start();
// *** ADICIONADO: Header UTF-8 ***
header('Content-Type: text/html; charset=utf-8');

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
    
    // *** ADICIONADO: Captura de Latitude e Longitude ***
    $latitude = filter_var($_POST['latitude'], FILTER_SANITIZE_STRING);
    $longitude = filter_var($_POST['longitude'], FILTER_SANITIZE_STRING);

    if(empty($latitude) || empty($longitude)){
        $message[] = 'Você precisa marcar a localização exata no mapa!';
    }
    // *** FIM DA ADIÇÃO ***

    // Se não houver mensagens de erro, continua
    if(empty($message)) {
       try {
            // *** MODIFICADO: Query UPDATE para incluir lat/lon ***
            $update_pet = $conn->prepare("UPDATE `pets` SET 
                nome = ?, status = ?, genero = ?, especie = ?, raca = ?, idade = ?, porte = ?, 
                cor_predominante = ?, cor_olhos = ?, data_desaparecimento = ?, 
                local_desaparecimento = ?, ponto_referencia = ?, comentario_tutor = ?, 
                paga_recompensa = ?, destaque = ?,
                latitude = ?, longitude = ? 
                WHERE id = ?");
                
            $update_pet->execute([
                $name, $status, $genero, $especie, $raca, $idade, $porte,
                $cor_predominante, $cor_olhos, $data_desaparecimento,
                $local_desaparecimento, $ponto_referencia, $comentario_tutor,
                $paga_recompensa, $destaque, 
                $latitude, $longitude, // <-- Adicionados
                $pet_id // <-- WHERE
            ]);
            // *** FIM DA MODIFICAÇÃO ***
            
            $message[] = 'Perfil do pet atualizado com sucesso!';

       } catch (Exception $e) {
            $message[] = 'Erro ao atualizar o perfil: ' . $e->getMessage();
       }
    }
}

// --- LÓGICA DE GET (para preencher o formulário) ---
// Busca os dados atuais do pet para exibir no formulário
try {
    // (A sua query original SELECT * já pega latitude e longitude, está correto)
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
    
    <!-- *** ADICIONADO: CSS do Mapa Leaflet *** -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <style>
        body { 
            font-family: 'Inter', sans-serif;
            background-color: #E0F2FE; /* bg-sky-100 */
        }
        /* *** ADICIONADO: Estilo para o Mapa *** */
        #map {
            height: 300px; /* Altura do mapa */
            z-index: 10;
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
                // *** ADICIONADO: ID para controle do JS ***
                echo '<div class="w-full max-w-2xl p-4 mb-4 text-center rounded-lg '.$msg_class.'" id="error-message">'.$msg.'</div>';
            }
        }
        ?>
        <!-- *** ADICIONADO: Placeholder para mensagens de JS *** -->
        <div id="js-message-placeholder" class="w-full max-w-2xl"></div>


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
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Endereço de Referência (Texto)</label> 
                    <!-- *** ADICIONADO: id="addressInput" *** -->
                    <input type="text" name="local_desaparecimento" id="addressInput" value="<?php echo htmlspecialchars($pet['local_desaparecimento']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                           
                    <!-- *** ADICIONADO: Botão de Busca *** -->
                    <button type="button" id="searchAddressButton"
                            class="w-full mt-2 bg-sky-600 text-white font-semibold py-2 px-4 rounded-lg shadow hover:bg-sky-700 transition-all">
                        <i class="fas fa-search mr-2"></i>Buscar Endereço no Mapa
                    </button>
                </div>

                <!-- Campo Ponto de Referência (Full Width) -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Ponto de Referência</label>
                    <input type="text" name="ponto_referencia" value="<?php echo htmlspecialchars($pet['ponto_referencia']); ?>" 
                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                
                <!-- *** ADICIONADO: Seção do Mapa (Full Width) *** -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-600 mb-1">Marque no Mapa (Obrigatório)</label>
                    <p class="text-xs text-gray-500 mb-2">Clique para redefinir o local ou use a busca de endereço.</p>
                    <div id="map" class="w-full h-72 rounded-lg border border-gray-300"></div>
                    <!-- Inputs escondidos para as coordenadas -->
                    <input type="hidden" name="latitude" id="latitudeInput" value="<?php echo htmlspecialchars($pet['latitude']); ?>">
                    <input type="hidden" name="longitude" id="longitudeInput" value="<?php echo htmlspecialchars($pet['longitude']); ?>">
                </div>
                <!-- *** FIM DA ADIÇÃO *** -->
                
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

    <!-- *** ADICIONADO: JS do Mapa Leaflet *** -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // Pega os dados do pet (latitude e longitude) que o PHP buscou
            const currentLat = <?php echo json_encode($pet['latitude'] ?? null); ?>;
            const currentLon = <?php echo json_encode($pet['longitude'] ?? null); ?>;

            // Pega os inputs escondidos
            const latInput = document.getElementById('latitudeInput');
            const lonInput = document.getElementById('longitudeInput');
            const mapElement = document.getElementById('map');
            let marker = null;
            let map;

            // Elementos da Busca
            const searchButton = document.getElementById('searchAddressButton');
            const addressInput = document.getElementById('addressInput');
            const messagePlaceholder = document.getElementById('js-message-placeholder');


            // Tenta centralizar no Brasil, ou usa uma localização padrão
            const defaultCenter = [-14.2350, -51.9253]; // Centro do Brasil

            // Z-Index
            if(mapElement) {
                mapElement.style.zIndex = '10';
            } else {
                console.error("Elemento 'map' não encontrado.");
                return; // Para o script se o mapa não existir
            }


            try {
                // Inicia o mapa
                map = L.map(mapElement).setView(defaultCenter, 5);
            } catch (e) {
                console.error("Erro ao iniciar o mapa:", e);
                mapElement.innerHTML = "Erro ao carregar o mapa. Tente recarregar a página.";
                return;
            }

            // Adiciona a camada de mapa (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);


            // --- LÓGICA DE INICIALIZAÇÃO DO MAPA ---
            // Se o pet JÁ TEM coordenadas salvas, centraliza e marca o pino nele
            if (currentLat && currentLon) {
                const petPos = [parseFloat(currentLat), parseFloat(currentLon)];
                map.setView(petPos, 16); // Zoom próximo
                updateMapPin(petPos[0], petPos[1]); // Chama a função para criar o pino
            } else {
                // Se o pet NÃO tem coordenadas, tenta pegar a localização do admin
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const userPos = [position.coords.latitude, position.coords.longitude];
                        map.setView(userPos, 13);
                    }, function() {
                        console.log("Falha ao obter geolocalização. Usando padrão.");
                    });
                }
            }
            // --- FIM DA LÓGICA DE INICIALIZAÇÃO ---


            // *** FUNÇÃO: Atualiza os inputs E o marcador ***
            function updateMapPin(lat, lon, zoomLevel = 16) {
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


            // *** FUNÇÃO: Buscar Endereço (Geocodificação) ***
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
                    // **IMPORTANTE**: Email de contato para a API
                    const response = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(address)}&format=json&limit=1&email=claudemyseverio36@gmail.com`);
                    
                    if (!response.ok) {
                        throw new Error('Serviço de busca de endereço indisponível.');
                    }
                    
                    const data = await response.json();

                    if (data && data.length > 0) {
                        const result = data[0];
                        const lat = parseFloat(result.lat);
                        const lon = parseFloat(result.lon);
                        
                        // SUCESSO! Atualiza o mapa
                        updateMapPin(lat, lon, 16);
                        showMessage('Endereço encontrado! Verifique o pino e ajuste se necessário.', 'success');

                    } else {
                        // Não encontrou
                        showMessage('Endereço não encontrado. Tente ser mais específico ou clique manualmente no mapa.', 'error');
                    }

                } catch (error) {
                    console.error('Erro na Geocodificação:', error);
                    showMessage('Erro ao buscar endereço. Verifique sua conexão ou clique manualmente.', 'error');
                } finally {
                    searchButton.disabled = false;
                    searchButton.innerHTML = '<i class="fas fa-search mr-2"></i>Buscar Endereço no Mapa';
                }
            });

            // *** FUNÇÃO: Mostrar Mensagens (para o JS) ***
            function showMessage(msg, type = 'success') {
                const phpError = document.getElementById('error-message');
                if (phpError) phpError.style.display = 'none';

                if (!msg) {
                    messagePlaceholder.innerHTML = '';
                    return;
                }
                
                const bgColor = type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
                messagePlaceholder.innerHTML = `<div class="w-full p-4 mb-4 text-center rounded-lg ${bgColor}">${msg}</div>`;
            }
        });
    </script>
</body>
</html>