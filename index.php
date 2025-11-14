<?php
include 'config.php';
session_start();
header('Content-Type: text/html; charset=utf-8'); // <-- ADICIONADO PARA FORÇAR UTF-8

// Verifica se o usuário já está logado
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;

// --- LÓGICA: Buscar pets em destaque e perdidos ---
try {
    $select_pets = $conn->prepare("
        SELECT p.id, p.nome, p.status, p.destaque, p.local_desaparecimento, p.atualizado_em, pi.img_url
        FROM pets p
        LEFT JOIN (
            -- Sub-consulta para pegar apenas UMA imagem (a primeira) por pet
            SELECT pet_id, img_url
            FROM pet_images
            GROUP BY pet_id
        ) pi ON p.id = pi.pet_id
        WHERE p.status = 'Perdido' OR p.destaque = 1
        ORDER BY
            p.destaque DESC, -- Destaque vem primeiro
            p.data_cadastro DESC -- Mais recentes primeiro
        LIMIT 4 -- Limitar a 4 para a página inicial
    ");
    $select_pets->execute();
    $pets_para_mostrar = $select_pets->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pets_para_mostrar = []; // Em caso de erro, a lista fica vazia
    // echo 'Erro ao buscar pets: ' . $e->getMessage(); // Descomente para depurar
}

// --- NOVA LÓGICA: Buscar pets para ADOÇÃO ---
try {
    $select_adocao = $conn->prepare("
        SELECT p.id, p.nome, p.status, p.local_desaparecimento, p.atualizado_em, pi.img_url
        FROM pets p
        LEFT JOIN (
            SELECT pet_id, img_url
            FROM pet_images
            GROUP BY pet_id
        ) pi ON p.id = pi.pet_id
        WHERE TRIM(LOWER(p.status)) = 'adoção' -- CORRIGIDO: Procurando por 'adoção'
        ORDER BY
            p.data_cadastro DESC -- Mais recentes primeiro
        LIMIT 4 -- Limitar a 4 para a página inicial
    ");
    $select_adocao->execute();
    $pets_para_adocao = $select_adocao->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pets_para_adocao = []; // Em caso de erro, a lista fica vazia
    echo 'Erro ao buscar pets para adoção: ' . $e->getMessage(); // ATIVANDO MENSAGEM DE ERRO
}


// --- NOVO: Lista de imagens para o banner slideshow ---
$banner_images = [
    "https://www.al.sp.gov.br/repositorio/noticia/N-12-2019/fg245895.jpg",
    "https://conteudo.imguol.com.br/c/entretenimento/e4/2020/11/18/pets-no-mato-1605715705944_v2_900x506.jpg",
    "https://admin.cnnbrasil.com.br/wp-content/uploads/sites/12/2024/02/pets-dengue.jpg?w=1200&h=900&crop=0",
    "https://f.i.uol.com.br/fotografia/2020/10/06/16020012105f7c993a85c94_1602001210_5x2_rt.jpg"
];

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AchePet</title>
  <!-- Tailwind via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Fonte Inter (para combinar com o login) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome (Ícones) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  <style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #F0F9FF; /* Fundo azul claro */
    }
    /* (O CSS para .video-play-button não é mais necessário, mas pode ficar) */
    
    /* Esconde o input de arquivo */
    #cameraInput {
        display: none;
    }
  </style>
</head>
<body class="bg-sky-100 min-h-screen pb-24">

  <!-- Header -->
  <header class="flex justify-between items-center w-full px-4 py-3 bg-white shadow-sm sticky top-0 z-50">
   <!-- MODIFICADO: Adicionado link para home e logo -->
   <a href="index.php" class="flex items-center gap-2"> <!-- 'gap-2' adiciona espaço -->
<img src="/logo.png" alt="Logo AchePet" class="h-12 w-12 rounded-full"> <!-- Logo (Caminho absoluto) -->
<h1 class="text-2xl font-extrabold">
    <span class="text-orange-600">ACHE</span>
    <span class="text-yellow-500">PET!</span>
</h1>
</a>
<!-- Fim da Modificação -->
    <div class="flex flex-col items-center text-sm">
      <div class="w-8 h-8 bg-sky-200 rounded-full flex items-center justify-center">
        <?php if($user_id): ?>
          <!-- Se logado, vai para o perfil.php -->
          <a href="perfil.php" class="text-sky-700 text-lg">👤</a>
        <?php else: ?>
          <!-- Se não logado, vai para o user_login.php -->
          <a href="user_login.php" class="text-sky-700 text-lg">👤</a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- Container Principal -->
  <main class="w-full max-w-5xl mx-auto px-4 py-8">

    <!--
      CORREÇÃO: Banner agora é um slideshow
    -->
    <div class="w-full mt-2 relative rounded-xl shadow-lg overflow-hidden h-48 md:h-80" id="banner-slider">
        <?php foreach ($banner_images as $index => $img_url): ?>
            <img src="<?php echo htmlspecialchars($img_url); ?>"
                 alt="Banner Pet <?php echo $index + 1; ?>"
                 class="banner-slide absolute top-0 left-0 w-full h-full object-cover transition-opacity duration-1000 ease-in-out <?php echo $index == 0 ? 'opacity-100' : 'opacity-0'; ?>"
                 onerror="this.style.display='none'"> <!-- Esconde se a imagem falhar -->
        <?php endforeach; ?>
    </div>
    <!-- Fim do Banner Slideshow -->

    <!--
      SEÇÃO: Achados e Perdidos (Dinâmico)
    -->
    <div class="w-full mt-6">
      <div class="flex justify-between items-center mb-2">
        <div>
          <h2 class="text-base md:text-lg font-medium text-sky-900">Achados e Perdidos</h2>
          <p class="text-sm text-sky-600">Pets em destaque e perdidos recentemente</p>
        </div>
        <!-- Link para ver todos (aponta para a página que já fizemos) -->
        <a href="view_all_pets.php" class="text-sm text-orange-600 font-semibold hover:underline flex-shrink-0">Ver todos</a>
      </div>

      <!-- Grid responsivo para os cards dos pets -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mt-2">

        <?php if (empty($pets_para_mostrar)): ?>
          <p class="text-gray-500 col-span-full">Nenhum pet perdido ou em destaque no momento.</p>
        <?php else: ?>
          <?php foreach ($pets_para_mostrar as $pet): ?>
            
            <!-- Card do Pet -->
            <a href="perfil_pet.php?id=<?php echo $pet['id']; ?>" class="block bg-white rounded-lg shadow-md overflow-hidden transition-all hover:shadow-lg hover:scale-105">
              
              <!-- Imagem com status -->
              <div class="relative">
                <img src="<?php echo htmlspecialchars($pet['img_url'] ?? 'https://placehold.co/300x300/E2E8F0/333?text=Sem+Foto'); ?>"
                     alt="Foto de <?php echo htmlspecialchars($pet['nome']); ?>"
                     class="w-full h-32 md:h-40 object-cover"
                     onerror="this.src='https://placehold.co/300x300/E2E8F0/333?text=Erro'">
                
                <!-- Tag de Status (como na image_213b48.png) -->
                <?php
                  $status_color = 'bg-gray-500';
                  if ($pet['status'] == 'Perdido') $status_color = 'bg-red-600';
                  if ($pet['status'] == 'Encontrado') $status_color = 'bg-green-600';
                  if ($pet['status'] == 'Adoção') $status_color = 'bg-blue-600'; // Cor para Adoção
                ?>
                <span class="absolute top-2 left-2 <?php echo $status_color; ?> text-white text-xs font-bold px-2 py-1 rounded">
                  <?php echo htmlspecialchars(mb_strtoupper($pet['status'], 'UTF-8')); // <-- CORRIGIDO ?>
                </span>
                
                <!-- Tag de Destaque (se for destaque) -->
                <?php if ($pet['destaque'] == 1): ?>
                  <span class="absolute bottom-2 right-2 bg-yellow-400 text-yellow-900 text-xs font-bold p-1 rounded-full w-6 h-6 flex items-center justify-center">
                    <i class="fas fa-star"></i>
                  </span>
                <?php endif; ?>
              </div>
              
              <!-- Informações do Card -->
              <div class="p-3">
                <h4 class="text-md md:text-lg font-bold text-sky-900 truncate"><?php echo htmlspecialchars($pet['nome']); ?></h4>
                <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($pet['local_desaparecimento']); ?></p>
                <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($pet['atualizado_em']); ?> atrás</p>
              </div>
            </a>
            
          <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </div>


    <!--
        *** SEÇÃO RESTAURADA E CORRIGIDA ***
        SEÇÃO: Pets para Doação (Dinâmico)
    -->
    <div class="w-full mt-6">
      <div class="flex justify-between items-center mb-2">
        <div>
          <h2 class="text-base md:text-lg font-medium text-sky-900">Pets para doação</h2>
          <p class="text-sm text-sky-600">Animais esperando por um novo lar</p>
        </div>
        <!-- Link para ver todos (aponta para a página que já fizemos) -->
        <a href="view_all_pets.php" class="text-sm text-orange-600 font-semibold hover:underline flex-shrink-0">Ver todos</a>
      </div>

      <!-- Grid responsivo para os cards dos pets -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mt-2">

        <?php if (empty($pets_para_adocao)): ?>
          <p class="text-gray-500 col-span-full">Nenhum pet para adoção no momento.</p>
        <?php else: ?>
          <?php foreach ($pets_para_adocao as $pet): ?>
            
            <!-- Card do Pet (usando a nova variável $pets_para_adocao) -->
            <a href="perfil_pet.php?id=<?php echo $pet['id']; ?>" class="block bg-white rounded-lg shadow-md overflow-hidden transition-all hover:shadow-lg hover:scale-105">
              
              <!-- Imagem com status -->
              <div class="relative">
                <img src="<?php echo htmlspecialchars($pet['img_url'] ?? 'https://placehold.co/300x300/E2E8F0/333?text=Sem+Foto'); ?>"
                     alt="Foto de <?php echo htmlspecialchars($pet['nome']); ?>"
                     class="w-full h-32 md:h-40 object-cover"
                     onerror="this.src='https://placehold.co/300x300/E2E8F0/333?text=Erro'">
                
                <!-- Tag de Status (Adoção) - CORRIGIDO para ser dinâmico -->
                <span class="absolute top-2 left-2 bg-blue-600 text-white text-xs font-bold px-2 py-1 rounded">
                  <?php echo htmlspecialchars(mb_strtoupper($pet['status'], 'UTF-8')); // <-- CORRIGIDO ?>
                </span>
              </div>
              
              <!-- Informações do Card -->
              <div class="p-3">
                <h4 class="text-md md:text-lg font-bold text-sky-900 truncate"><?php echo htmlspecialchars($pet['nome']); ?></h4>
                <!-- Usando 'local_desaparecimento' como local, ajuste se o campo for outro -->
                <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($pet['local_desaparecimento']); ?></p>
                <p class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($pet['atualizado_em']); ?> atrás</p>
              </div>
            </a>
            
          <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </div>
    <!-- Fim da Seção Pets para Doação -->


  </main> <!-- Fim do Container Principal -->

  <!--
    Menu inferior
  -->
  <nav class="fixed bottom-0 w-full bg-yellow-400 flex justify-between items-center px-6 py-2 shadow-inner-top z-40" style="box-shadow: 0 -2px 5px rgba(0,0,0,0.1);">
    
    <!-- *** MODIFICADO: Link aponta para map.php *** -->
    <a href="map.php" class="flex flex-col items-center text-sky-900 font-bold">
      📍
      <span class="text-sm">PETS NO MAPA</span>
    </a>
    <!-- *** FIM DA MODIFICAÇÃO *** -->

    <!--
      Botão da Câmera (agora um <label> para o input escondido)
    -->
    <label for="cameraInput" id="cameraButton" class="relative -mt-8 bg-white w-16 h-16 rounded-full flex items-center justify-center shadow-lg border-4 border-yellow-400 cursor-pointer">
      📷
      <span class="absolute text-xl -top-2 -right-2 text-sky-700">+</span>
    </label>

    <!--
      Link de "PET PERDIDO" (apontando para a lista)
    -->
    <a href="view_all_pets.php" class="flex flex-col items-center text-sky-900 font-bold">
      🔍
      <span class="text-sm">PET PERDIDO</span>
    </a>
  </nav>

  <!--
    Input de Câmera Escondido
  -->
  <!-- MODIFICADO: removido 'capture="user"' para permitir escolha (Câmera ou Galeria) -->
  <input type="file" accept="image/*" id="cameraInput">

  <!--
    JavaScript para Câmera e Slideshow
  -->
  <script>
    // --- NOVO: Script do Slideshow do Banner ---
    document.addEventListener('DOMContentLoaded', function() {
        const slides = document.querySelectorAll('#banner-slider .banner-slide');
        let currentSlide = 0;
        
        if (slides.length > 1) {
            setInterval(() => {
                // Esconde o slide atual
                slides[currentSlide].classList.remove('opacity-100');
                slides[currentSlide].classList.add('opacity-0');
                
                // Calcula o próximo slide
                currentSlide = (currentSlide + 1) % slides.length;
                
                // Mostra o próximo slide
                slides[currentSlide].classList.remove('opacity-0');
                slides[currentSlide].classList.add('opacity-100');
            }, 4000); // Muda a cada 4 segundos
        }
    });
    // --- Fim do Script do Slideshow ---


    // --- Script da Câmera (Com Compressão e Upload AJAX) ---
    
    // Passa o status do login (ID do usuário) do PHP para o JavaScript
    const USER_LOGGED_IN = <?php echo json_encode(isset($user_id)); ?>;
    
    // Elementos
    const cameraButton = document.getElementById('cameraButton');
    const cameraInput = document.getElementById('cameraInput');
    let loadingOverlay = null; // Variável para o overlay

    // 1. O que acontece quando o botão da câmera é clicado
    cameraButton.addEventListener('click', function (event) {
        // Se o usuário NÃO está logado...
        if (!USER_LOGGED_IN) {
            // ...Impede a câmera de abrir...
            event.preventDefault();
            // ...e envia ele para o login.
            console.log('Usuário não logado. Redirecionando para login...');
            window.location.href = 'user_login.php';
        }
        // Se estiver logado, o clique no <label> já abre o <input> da câmera.
    });

    // 2. O que acontece depois que o usuário tira a foto (MODIFICADO)
    cameraInput.addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (!file) {
            return; // O usuário cancelou
        }

        // Mostra um "Carregando..."
        if (!loadingOverlay) {
            loadingOverlay = document.createElement('div');
            loadingOverlay.id = 'loading-overlay';
            loadingOverlay.style = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); color: white; display: flex; align-items: center; justify-content: center; z-index: 9999; font-family: Inter, sans-serif; font-size: 1.2rem;";
            document.body.appendChild(loadingOverlay);
        }
        loadingOverlay.textContent = 'Processando foto...';

        // --- LÓGICA DE COMPRESSÃO ---
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d'); // Corrigido de 'd2' para '2d'

                // Define o tamanho máximo (ex: 800 pixels)
                const MAX_SIZE = 800; // Tamanho razoável
                let width = img.width;
                let height = img.height;

                // Calcula o redimensionamento mantendo a proporção
                if (width > height) {
                    if (width > MAX_SIZE) {
                        height *= MAX_SIZE / width;
                        width = MAX_SIZE;
                    }
                } else {
                    if (height > MAX_SIZE) {
                        width *= MAX_SIZE / height;
                        height = MAX_SIZE;
                    }
                }
                
                canvas.width = width;
                canvas.height = height;
                
                // Desenha a imagem redimensionada no canvas
                ctx.drawImage(img, 0, 0, width, height);
                
                // Converte o canvas para um Blob
                canvas.toBlob(function(blob) {
                    
                    const formData = new FormData();
                    formData.append('petImage', blob, 'pet.jpg'); 

                    loadingOverlay.textContent = 'Enviando...';

                    // Envia o Blob para o script PHP (SEM a barra "/", pois não usamos mais .htaccess)
                    fetch('ajax_upload_image.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.filePath) {
                            // SUCESSO! Salva SÓ O CAMINHO na sessionStorage
                            sessionStorage.setItem('newPetImagePath', data.filePath);
                            // Redireciona para a página do formulário
                            window.location.href = 'pet_register.php';
                        } else {
                            // Erro retornado pelo PHP
                            console.error('Erro no servidor:', data.error);
                            if (loadingOverlay) loadingOverlay.remove();
                            loadingOverlay = null;
                        }
                    })
                    .catch(error => {
                        // Erro de rede ou de fetch
                        console.error('Erro de rede:', error);
                        if (loadingOverlay) loadingOverlay.remove();
                        loadingOverlay = null;
                    });

                }, 'image/jpeg', 0.7); // Qualidade de 70%
            };
            img.src = e.target.result;
        };
        reader.onerror = function () {
            console.error('Falha ao ler o arquivo de imagem.');
            if (loadingOverlay) loadingOverlay.remove();
            loadingOverlay = null;
        };
        // Lê o arquivo
        reader.readAsDataURL(file);
        
        // Limpa o valor do input para permitir selecionar a mesma foto novamente
        event.target.value = null;
    });
  </script>

</body>
</html>