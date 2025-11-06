<?php
// 1. CONECTAR AO BANCO DE DADOS REAL
include 'config.php';

// 2. PEGAR O ID DO PET PELA URL
$pet_id = (int)($_GET['id'] ?? 0);

if ($pet_id === 0) {
    die("Página não encontrada. ID do pet não fornecido.");
}

// 3. BUSCAR OS DADOS REAIS DO PET E TUTOR
// --- LÓGICA CORRIGIDA ---
// Busca o Pet (p.*) E junta a tabela 'users' (u)
// para pegar o nome (u.name) e email (u.email) do tutor.
$sql_pet = "SELECT p.*, u.name AS nome_tutor, u.email AS email_tutor
            FROM pets p
            LEFT JOIN users u ON p.tutor_id = u.id
            WHERE p.id = ?";

$stmt = $conn->prepare($sql_pet);
$stmt->execute([$pet_id]); // Passa o ID para o execute

if ($stmt->rowCount() === 0) {
    die("Pet com ID $pet_id não encontrado.");
}

// Organiza os dados do pet
$pet = $stmt->fetch(PDO::FETCH_ASSOC);

// --- LÓGICA CORRIGIDA ---
// Organiza os dados do tutor
// O Nome e Email vêm da tabela 'users' (puxados pelo JOIN)
// O Telefone vem do campo 'contato_telefone' da tabela 'pets'
$tutor = [
    'nome' => $pet['nome_tutor'] ?? 'Usuário não encontrado',
    'telefone' => $pet['contato_telefone'] ?? 'Não cadastrado', // <-- CORRIGIDO
    'email' => $pet['email_tutor'] ?? 'Email não encontrado'
];

// 4. BUSCAR A GALERIA DE FOTOS DO PET (Usando PDO)
$sql_images = "SELECT img_url FROM pet_images WHERE pet_id = ?";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->execute([$pet_id]);

$pet_images = [];
while ($row = $stmt_images->fetch(PDO::FETCH_ASSOC)) {
    // Agora, o script apenas pega o link do banco (seja da internet ou local)
    // e o usa diretamente, sem adicionar prefixos de pasta.
    $pet_images[] = $row['img_url'];
}


// Se não houver fotos, define uma imagem padrão
if (empty($pet_images)) {
    $pet_images[] = 'https://placehold.co/600x400/ccc/FFF?text=Pet+Sem+Foto';
}

// 5. FECHAR CONEXÕES (PDO fecha o cursor, $conn pode ser mantido ou fechado)
$stmt = null;
$stmt_images = null;
// $conn = null; 

// --- FIM DA LÓGICA DO BANCO DE DADOS ---
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Pet: <?php echo htmlspecialchars($pet['nome']); ?> - AchePet</title>
    <!-- Carregando o Tailwind CSS (para o estilo) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* Estilo base do seu projeto */
        body {
            font-family: 'Inter', sans-serif;
            /* Fundo azul claro do seu app */
            background-color: #F0F9FF; /* bg-blue-50 ou bg-sky-50 */
        }
        
        /* Cor laranja principal do seu app */
        .bg-achepet-orange {
            background-color: #F97316; /* bg-orange-500 */
        }
        .text-achepet-orange {
            color: #F97316;
        }
        .border-achepet-orange {
            border-color: #F97316;
        }
        .bg-achepet-orange-light {
            background-color: #FFF7ED; /* bg-orange-50 */
        }
        .text-achepet-orange-dark {
            color: #EA580C; /* bg-orange-600 */
        }

        /* Melhorias no slider */
        .slider-dots button {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #CBD5E1; /* bg-gray-300 */
            transition: background-color 0.3s ease;
        }
        .slider-dots button.active {
            background-color: #F97316; /* bg-orange-500 */
        }
    </style>
</head>
<body class="antialiased">

    <!-- Header (Estilo AchePet) -->
    <header class="bg-achepet-orange text-white p-4 shadow-md sticky top-0 z-50">
        <!-- Recomendo lincar de volta para seu index -->
        <a href="index.php" class="absolute left-4 top-4 text-white hover:text-gray-200 transition-colors">&larr; Voltar</a>
        <h1 class="text-xl font-bold text-center">AchePet</h1>
    </header>

    <div class="max-w-lg mx-auto pb-10">

        <!-- 1. Seção da Imagem e Status -->
        <div class="relative w-full shadow-lg">
            <!-- Banner de Status (Ex: Perdido) -->
            <div class="absolute top-0 left-0 <?php echo ($pet['status'] == 'Perdido') ? 'bg-red-600' : 'bg-green-600'; ?> text-white px-4 py-1 text-sm font-bold rounded-br-lg z-20">
                <?php echo htmlspecialchars(strtoupper($pet['status'])); ?>
            </div>

            <!-- Slider de Imagens -->
            <div class="relative overflow-hidden rounded-b-lg" id="image-slider">
                <div class="flex transition-transform duration-500 ease-in-out" id="slider-track">
                    <?php foreach ($pet_images as $index => $img_url): ?>
                        <div class="w-full flex-shrink-0">
                            <!-- A tag img agora usará o link da internet diretamente -->
                            <img src="<?php echo htmlspecialchars($img_url); ?>" alt="Foto de <?php echo htmlspecialchars($pet['nome']); ?> <?php echo $index + 1; ?>" class="w-full h-64 object-cover md:h-80" onerror="this.src='https://placehold.co/600x400/ccc/FFF?text=Erro+ao+carregar+imagem'">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Dots de Navegação -->
                <?php if (count($pet_images) > 1): ?>
                <div class="slider-dots absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2 z-20">
                    <?php foreach ($pet_images as $index => $img_url): ?>
                        <button data-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Bloco de Informações Principais (Estilo Referência, Cores AchePet) -->
        <div class="bg-white p-5 rounded-lg shadow-md -mt-10 mx-4 z-30 relative">
            
            <!-- Nome e Infos Básicas -->
            <h2 class="text-3xl font-bold text-center text-gray-800">
                <?php echo htmlspecialchars($pet['nome']); ?>
            </h2>
            <p class="text-gray-600 text-center text-sm mt-1">
                <?php echo htmlspecialchars($pet['genero']); ?> &bull; 
                <?php echo htmlspecialchars($pet['idade']); ?> &bull; 
                <?php echo htmlspecialchars($pet['raca']); ?>
            </p>

            <!-- Banner de Destaque (Se houver) -->
            <?php if ($pet['destaque']): ?>
            <div class="bg-achepet-orange-light text-achepet-orange-dark font-semibold px-4 py-2 rounded-lg flex items-center justify-center space-x-2 my-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span>Pet em Destaque</span>
            </div>
            <?php endif; ?>

            <!-- Compartilhar e Views -->
            <div class="text-center text-gray-500 text-xs my-4 space-y-1">
                <p>
                    <span class="font-semibold text-gray-600">COMPARTILHE</span> 
                </p>
                <p>Atualizado <?php echo htmlspecialchars($pet['atualizado_em']); ?> atrás</p>
                <p><?php echo htmlspecialchars($pet['visualizacoes']); ?> visualizações</p>
            </div>

            <!-- Banner de Recompensa (Se houver) -->
            <?php if ($pet['paga_recompensa']): ?>
            <div class="bg-green-100 text-green-700 font-semibold px-4 py-3 rounded-lg flex items-center justify-center space-x-2 my-4 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm11-4a1 1 0 00-1-1H9a1 1 0 000 2h6a1 1 0 001-1z" clip-rule="evenodd" />
                </svg>
                <span>Paga-se Recompensa</span>
            </div>
            <?php endif; ?>

            <!-- Botão Principal (Estilo AchePet) -->
            <button id="show-contact-btn" class="bg-achepet-orange hover:bg-opacity-90 text-white font-bold py-3 px-6 rounded-lg w-full text-center transition duration-300">
                Mostrar Contato
            </button>
        </div>

        <!-- 3. Informações de Contato (Escondido por padrão) -->
        <!-- O HTML AQUI ESTÁ CORRETO, pois ele usa o array $tutor que definimos no PHP -->
        <div id="contact-info" class="bg-white p-5 rounded-lg shadow-md mx-4 mt-5 hidden">
            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b-2 border-achepet-orange pb-2">
                Informações do Tutor
            </h3>
            <div class="space-y-3">
                <div class="">
                    <span class="text-sm font-semibold text-gray-500 uppercase">NOME</span>
                    <p class="text-gray-800 font-medium text-lg"><?php echo htmlspecialchars($tutor['nome']); ?></p>
                </div>
                <div class="">
                    <span class="text-sm font-semibold text-gray-500 uppercase">TELEFONE</span>
                    <p class="text-gray-800 font-medium text-lg"><?php echo htmlspecialchars($tutor['telefone']); ?></p>
                </div>
                <div class="">
                    <span class="text-sm font-semibold text-gray-500 uppercase">E-MAIL</span>
                    <p class="text-gray-800 font-medium text-lg"><?php echo htmlspecialchars($tutor['email']); ?></p>
                </div>
            </div>
        </div>

        <!-- 4. Informações Básicas -->
        <div class="bg-white p-5 rounded-lg shadow-md mx-4 mt-5">
            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b-2 border-achepet-orange pb-2">
                Informações Básicas
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-500 uppercase">NOME DO PET</span>
                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($pet['nome']); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-500 uppercase">GÊNERO</span>
                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($pet['genero']); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-500 uppercase">ID ACHEPET</span>
                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($pet['id_achepet']); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-500 uppercase">ESPÉCIE</span>
                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($pet['especie']); ?></span>
                </div>
            </div>
        </div>

        <!-- 5. Características -->
        <div class="bg-white p-5 rounded-lg shadow-md mx-4 mt-5">
            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b-2 border-achepet-orange pb-2">
                Características
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-500 uppercase">RAÇA</span>
                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($pet['raca']); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-500 uppercase">IDADE</span>
                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($pet['idade']); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-500 uppercase">PORTE</span>
                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($pet['porte']); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-500 uppercase">COR PREDOMINANTE</span>
                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($pet['cor_predominante']); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm font-semibold text-gray-500 uppercase">COR DOS OLHOS</span>
                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($pet['cor_olhos']); ?></span>
                </div>
            </div>
        </div>

        <!-- 6. Comentário do Tutor -->
        <div class="bg-white p-5 rounded-lg shadow-md mx-4 mt-5">
            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b-2 border-achepet-orange pb-2">
                Comentário do Tutor
            </h3>
            <div class="bg-achepet-orange-light p-4 rounded-lg">
                <p class="text-gray-700 italic leading-relaxed">
                    "<?php echo nl2br(htmlspecialchars($pet['comentario_tutor'])); ?>"
                </p>
            </div>
        </div>

        <!-- 7. Data e Local -->
        <div class="bg-white p-5 rounded-lg shadow-md mx-4 mt-5">
            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b-2 border-achepet-orange pb-2">
                Data e Local
            </h3>
            <div class="space-y-4">
                <div class="">
                    <span class="text-sm font-semibold text-gray-500 uppercase">DATA DO DESAPARECIMENTO</span>
                    <p class="text-gray-800 font-medium text-lg"><?php echo htmlspecialchars($pet['data_desaparecimento']); ?></p>
                </div>
                <div class="">
                    <span class="text-sm font-semibold text-gray-500 uppercase">LOCAL DO DESAPARECIMENTO</span>
                    <p class="text-gray-800 font-medium text-lg"><?php echo htmlspecialchars($pet['local_desaparecimento']); ?></p>
                </div>
                <div class="">
                    <span class="text-sm font-semibold text-gray-500 uppercase">PONTO DE REFERÊNCIA</span>
                    <p class="text-gray-800 font-medium text-lg"><?php echo htmlspecialchars($pet['ponto_referencia']); ?></p>
                </div>

                <?php
                // --- INÍCIO DA LÓGICA DO MAPA ---
                // Combina o local e a referência para uma busca melhor
                $endereco_query = $pet['local_desaparecimento'] . ", " . $pet['ponto_referencia'];
                
                // Cria a URL segura para o iframe
                $map_url = "https://maps.google.com/maps?q=" . urlencode($endereco_query) . "&t=&z=15&ie=UTF8&iwloc=&output=embed";
                // --- FIM DA LÓGICA DO MAPA ---
                ?>

                <!-- Mapa (Substituído) -->
                <div class="w-full h-64 rounded-lg overflow-hidden border border-gray-200">
                    <iframe
                        width="100%"
                        height="100%"
                        frameborder="0"
                        scrolling="no"
                        marginheight="0"
                        marginwidth="0"
                        src="<?php echo $map_url; ?>">
                    </iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript para interatividade -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            
            // --- Lógica do Botão de Contato ---
            const showContactBtn = document.getElementById('show-contact-btn');
            const contactInfo = document.getElementById('contact-info');

            if (showContactBtn && contactInfo) {
                showContactBtn.addEventListener('click', function () {
                    const isHidden = contactInfo.classList.contains('hidden');
                    if (isHidden) {
                        contactInfo.classList.remove('hidden');
                        contactInfo.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        showContactBtn.textContent = 'Esconder Contato';
                    } else {
                        contactInfo.classList.add('hidden');
                        showContactBtn.textContent = 'Mostrar Contato';
                    }
                });
            }

            // --- Lógica do Slider de Imagem ---
            const slider = document.getElementById('image-slider');
            const track = document.getElementById('slider-track');
            
            if (slider && track && track.children.length > 1) { // Só ativa o slider se houver mais de 1 imagem
                const slides = Array.from(track.children);
                const dots = Array.from(slider.querySelectorAll('.slider-dots button'));
                let slideWidth = slides[0].getBoundingClientRect().width;
                let currentSlide = 0;
                let slideInterval;

                function moveToSlide(slideIndex) {
                    if (slideIndex < 0 || slideIndex >= slides.length) {
                        slideIndex = 0;
                    }
                    if(!track || !dots[slideIndex]) return;

                    track.style.transform = 'translateX(-' + (slideWidth * slideIndex) + 'px)';
                    dots.forEach(dot => dot.classList.remove('active'));
                    dots[slideIndex].classList.add('active');
                    currentSlide = slideIndex;
                }
                
                function startSlider() {
                    slideInterval = setInterval(() => {
                        let nextSlide = (currentSlide + 1) % slides.length;
                        moveToSlide(nextSlide);
                    }, 4000);
                }
                
                function stopSlider() {
                    clearInterval(slideInterval);
                }

                // Navegação pelos dots
                dots.forEach(dot => {
                    dot.addEventListener('click', function () {
                        stopSlider(); // Para o automático ao clicar no dot
                        const slideIndex = parseInt(this.getAttribute('data-slide-to'));
                        moveToSlide(slideIndex);
                    });
                });
                
                // Pausar/continuar com mouse
                slider.addEventListener('mouseenter', stopSlider);
                slider.addEventListener('mouseleave', startSlider);

                // Ajustar o tamanho ao redimensionar a tela
                window.addEventListener('resize', () => {
                    stopSlider();
                    slideWidth = slides[0].getBoundingClientRect().width;
                    track.style.transition = 'none'; // Desativa a animação para o reajuste
                    moveToSlide(currentSlide);
                    track.style.transition = 'transform 0.5s ease-in-out'; // Reativa a animação
                    startSlider();
                });
                
                startSlider(); // Inicia o slider automático
            }

        });
    </script>

</body>
</html>