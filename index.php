<?php
include 'config.php';
session_start();

// Verifica se o usuário já está logado
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;
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
    /* Ícone de play (apenas para a seção 'Aprenda') */
    .video-play-button {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 32px;
        height: 32px;
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0.8;
        transition: all 0.2s ease;
    }
    .video-play-button:hover {
        opacity: 1;
    }
    .video-play-button::after {
        content: '';
        display: block;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 6px 0 6px 10px;
        border-color: transparent transparent transparent #333;
        margin-left: 3px;
    }
    /* Esconde o input de arquivo */
    #cameraInput {
        display: none;
    }
  </style>
</head>
<body class="bg-sky-100 min-h-screen pb-24">

  <!-- Header -->
  <header class="flex justify-between items-center w-full px-4 py-3 bg-white shadow-sm sticky top-0 z-50">
    <h1 class="text-2xl font-extrabold">
      <span class="text-orange-600">ACHE</span>
      <span class="text-yellow-500">PET!</span>
    </h1>
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

    <!-- Banner -->
    <div class="w-full mt-2 relative">
      <img src="https://www.al.sp.gov.br/repositorio/noticia/N-12-2019/fg245895.jpg" alt="banner pet"
        class="rounded-xl w-full object-cover shadow-lg h-48 md:h-80"> <!-- Altura ajustada -->
      <button class="absolute bottom-4 left-4 bg-white border-2 border-yellow-400 text-sky-700 font-semibold rounded-lg px-4 py-1 shadow-md">
        ✅ Ache um Pet!
      </button>
      <button class="absolute top-1/2 right-4 -translate-y-1/2 bg-white p-2 rounded-full shadow">
        <i class="fas fa-chevron-right text-sky-700"></i>
      </button>
    </div>

    <!-- Seu pet se perdeu? -->
    <div class="w-full mt-6">
      <h2 class="text-base font-medium text-sky-900">Seu pet se perdeu?</h2>
      <p class="text-sm text-sky-600">Procure aqui!</p>
      <div class="flex gap-2 md:gap-4 mt-2">
        
        <!-- Link para o Pet ID 1 -->
        <a href="perfil_pet.php?id=1">
          <img src="https://meusanimais.com.br/wp-content/uploads/2017/05/gato-de-rua.jpg" class="w-20 h-20 md:w-32 md:h-32 rounded-lg object-cover shadow-md hover:shadow-lg transition-all">
        </a>
        
        <!-- Link para o Pet ID 2 -->
        <a href="perfil_pet.php?id=2">
          <img src="https://ogimg.infoglobo.com.br/in/9905525-bef-05d/FT1086A/2013-644517267-20130910023604224ap.jpg_20130910.jpg" class="w-20 h-20 md:w-32 md:h-32 rounded-lg object-cover shadow-md hover:shadow-lg transition-all">
        </a>
        
        <!-- Link para o Pet ID 3 -->
        <a href="perfil_pet.php?id=3">
          <img src="https://img.quizur.com/f/img6436006769e776.21477100.jpg?lastEdited=1681260651" class="w-20 h-20 md:w-32 md:h-32 rounded-lg object-cover shadow-md hover:shadow-lg transition-all">
        </a>

      </div>
    </div>

    <!-- Cuidados -->
    <div class="w-full mt-6">
      <h2 class="text-base font-medium text-sky-900">Aprenda a como cuidar do seu pet</h2>
      <p class="text-sm text-sky-600">Gato ou cachorro, conheça detalhes sobre cada um</p>
      <div class="flex gap-2 md:gap-4 mt-2 overflow-x-auto">
        <div class="relative w-28 h-20 md:w-40 md:h-24 rounded-lg overflow-hidden flex-shrink-0 shadow-md">
          <img src="https://pt.quizur.com/_image?href=https://img.quizur.com/f/img5e6328181de3d9.26777868.jpg?lastEdited=1583556635&w=1024&h=1024&f=webp" class="w-full h-full object-cover">
          <a href="#" class="absolute inset-0 flex items-center justify-center text-white text-2xl">
            <span class="video-play-button"></span>
          </a>
        </div>
        <div class="relative w-28 h-20 md:w-40 md:h-24 rounded-lg overflow-hidden flex-shrink-0 shadow-md">
          <img src="https://admin.cnnbrasil.com.br/wp-content/uploads/sites/12/2021/06/41479_2FF050B33087A556.png?w=1200&h=675&crop=1" class="w-full h-full object-cover">
          <a href="#" class="absolute inset-0 flex items-center justify-center text-white text-2xl">
            <span class="video-play-button"></span>
          </a>
        </div>
        <div class="relative w-28 h-20 md:w-40 md:h-24 rounded-lg overflow-hidden flex-shrink-0 shadow-md">
        <img src="https://i.pinimg.com/474x/89/c1/03/89c1037ac7cadef0fc4bf2989ca64383.jpg" class="w-full h-full object-cover">
          <a href="#" class="absolute inset-0 flex items-center justify-center text-white text-2xl">
            <span class="video-play-button"></span>
          </a>
        </div>
      </div>
    </div>

  </main> <!-- Fim do Container Principal -->

  <!-- 
    Menu inferior 
  -->
  <nav class="fixed bottom-0 w-full bg-yellow-400 flex justify-between items-center px-6 py-2 shadow-inner-top z-40" style="box-shadow: 0 -2px 5px rgba(0,0,0,0.1);">
    <a href="#" class="flex flex-col items-center text-sky-900 font-bold">
      📍
      <span class="text-sm">PETS NO MAPA</span>
    </a>

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
  <input type="file" accept="image/*" capture="user" id="cameraInput">

  <!-- 
    CORREÇÃO: 
    Novo JavaScript para Câmera com compressão de imagem
  -->
  <script>
    // Passa o status do login (ID do usuário) do PHP para o JavaScript
    const USER_LOGGED_IN = <?php echo json_encode(isset($user_id)); ?>;
    
    // Elementos
    const cameraButton = document.getElementById('cameraButton');
    const cameraInput = document.getElementById('cameraInput');

    // 1. O que acontece quando o botão da câmera é clicado
    cameraButton.addEventListener('click', function (event) {
        // Se o usuário NÃO está logado...
        if (!USER_LOGGED_IN) {
            // ...Impede a câmera de abrir...
            event.preventDefault(); 
            // ...e envia ele para o login.
            alert('Você precisa estar logado para cadastrar um pet!');
            window.location.href = 'user_login.php';
        }
        // Se estiver logado, o clique no <label> já abre o <input> da câmera.
    });

    // 2. O que acontece depois que o usuário tira a foto
    cameraInput.addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (!file) {
            return; // O usuário cancelou
        }

        // Mostra um "Carregando..."
        const loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'loading-overlay';
        loadingOverlay.style = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); color: white; display: flex; align-items: center; justify-content: center; z-index: 9999; font-family: Inter, sans-serif; font-size: 1.2rem;";
        loadingOverlay.textContent = 'Processando foto...';
        document.body.appendChild(loadingOverlay);

        // --- INÍCIO DA LÓGICA DE COMPRESSÃO ---

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                // Define o tamanho máximo (ex: 1080 pixels)
                const MAX_SIZE = 1080;
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
                
                // Converte o canvas para um Data URL (Base64) com compressão (JPEG, 80% de qualidade)
                // Isso reduz drasticamente o tamanho da string
                const compressedImageData = canvas.toDataURL('image/jpeg', 0.8);

                try {
                    // Salva a imagem COMPRIMIDA na memória temporária
                    sessionStorage.setItem('newPetImage', compressedImageData);
                    
                    // Redireciona para a página do formulário
                    window.location.href = 'pet_register.php';

                } catch (error) {
                    // Se der erro (mesmo comprimido)
                    alert('Erro ao processar a imagem. A foto pode ser muito grande.');
                    loadingOverlay.remove();
                }
            };
            img.src = e.target.result;
        };

        reader.onerror = function () {
            alert('Falha ao ler o arquivo de imagem.');
            loadingOverlay.remove();
        };

        // Lê o arquivo
        reader.readAsDataURL(file);
        
        // --- FIM DA LÓGICA DE COMPRESSÃO ---
    });
  </script>

</body>
</html>