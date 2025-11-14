<?php
include 'config.php';
session_start();
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pets no Mapa - AchePet</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Fonte Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome (Ícones) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- CSS do Mapa Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>

    <style>
        body { 
            font-family: 'Inter', sans-serif;
            overflow: hidden; /* Impede a rolagem da página, só o mapa mexe */
        }
        /* O mapa ocupará toda a tela abaixo do header */
        #map {
            position: fixed;
            top: 60px; /* Altura do header */
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10;
        }
        /* Estilo para o loading */
        #loading-spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            font-family: 'Inter', sans-serif;
            font-size: 1.2rem;
            color: #0369a1; /* text-sky-800 */
        }
        /* Ajuste para o popup do Leaflet */
        .leaflet-popup-content {
            margin: 13px 19px;
            line-height: 1.4;
        }
        .leaflet-popup-content-wrapper {
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-sky-100">

    <!-- Header (Cabeçalho) -->
    <header class="flex justify-between items-center w-full px-4 py-3 bg-white shadow-sm sticky top-0 z-50" style="height: 60px;">
        <!-- Botão Voltar -->
        <a href="index.php" class="text-sky-700 text-2xl">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="text-xl font-bold text-sky-900">
            PETS NO MAPA!
        </h1>
        <div class="w-8 h-8"></div> <!-- Espaçador -->
    </header>

    <!-- Mapa -->
    <div id="map"></div>

    <!-- Loading Spinner -->
    <div id="loading-spinner">
        <i class="fas fa-spinner fa-spin mr-2"></i>
        Procurando pets...
    </div>

    <!-- JS do Mapa Leaflet -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
     integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
     crossorigin=""></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            const mapElement = document.getElementById('map');
            const loadingSpinner = document.getElementById('loading-spinner');
            
            // 1. Inicializa o mapa
            const defaultCenter = [-14.2350, -51.9253]; // Centro do Brasil
            let map = L.map(mapElement).setView(defaultCenter, 5); // 5 = zoom

            // 2. Adiciona a camada de mapa (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // 3. Tenta pegar a localização do usuário para centralizar
            if (navigator.geolocation) {
                loadingSpinner.textContent = 'Obtendo sua localização...';
                navigator.geolocation.getCurrentPosition(function(position) {
                    const userPos = [position.coords.latitude, position.coords.longitude];
                    map.setView(userPos, 13); // Zoom maior perto do usuário
                    // Adiciona um marcador azul para o usuário
                    L.marker(userPos, {
                        icon: L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        })
                    }).addTo(map).bindPopup('<b>Você está aqui!</b>');
                    loadPets(); // Carrega os pets DEPOIS de centralizar
                }, function() {
                    console.log("Falha ao obter geolocalização. Carregando pets...");
                    loadPets(); // Falhou? Apenas carrega os pets
                });
            } else {
                // Navegador não suporta geolocalização
                console.log("Geolocalização não suportada. Carregando pets...");
                loadPets();
            }

            // 4. Função para carregar os pets da nossa API
            async function loadPets() {
                loadingSpinner.textContent = 'Procurando pets...';
                try {
                    // (Usamos o caminho relativo, já que você removeu o .htaccess)
                    const response = await fetch('get_all_pets_api.php');
                    const data = await response.json();

                    if (data.success && data.pets) {
                        createPetMarkers(data.pets);
                    } else {
                        console.error('Erro ao buscar pets:', data.error);
                    }
                } catch (error) {
                    console.error('Erro de rede ao buscar pets:', error);
                } finally {
                    // Esconde o loading
                    loadingSpinner.style.display = 'none';
                }
            }

            // 5. Função para criar os marcadores no mapa
            function createPetMarkers(pets) {
                pets.forEach(pet => {
                    // Pula pets sem coordenadas válidas (segurança extra)
                    if (!pet.latitude || !pet.longitude) return;

                    // Define o ícone (Vermelho para Perdido, Verde para Encontrado/Adoção)
                    let iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png';
                    if (pet.status === 'Perdido') {
                        iconUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png';
                    }

                    const petIcon = L.icon({
                        iconUrl: iconUrl,
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    });

                    // Conteúdo do Popup (o balão que abre ao clicar)
                    const popupContent = `
                        <div style="font-family: 'Inter', sans-serif; max-width: 150px; text-align: center;">
                            <img src="${pet.img_url || 'https://placehold.co/150x150/E2E8F0/333?text=Sem+Foto'}" 
                                 alt="${pet.nome}" 
                                 class="w-full h-24 object-cover rounded-md mb-2"
                                 onerror="this.src='https://placehold.co/150x150/E2E8F0/333?text=Erro'">
                            
                            <h4 class="font-bold text-lg text-sky-900 truncate">${pet.nome}</h4>
                            <p class="text-sm text-gray-600 font-semibold">${pet.status}</p>
                            
                            <a href="perfil_pet.php?id=${pet.id}" 
                               class="text-orange-600 font-semibold text-sm mt-1 inline-block">
                                Ver Perfil &rarr;
                            </a>
                        </div>
                    `;

                    // Adiciona o marcador no mapa
                    L.marker([pet.latitude, pet.longitude], { icon: petIcon })
                        .addTo(map)
                        .bindPopup(popupContent);
                });
            }
        });
    </script>

</body>
</html>