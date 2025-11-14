<?php
// Define um cabeçalho de resposta como JSON
header('Content-Type: application/json');

// *** MODIFICADO: Pasta alterada para 'uploaded_img' ***
$upload_dir = 'uploaded_img/';

// Verifica se a pasta existe, se não, tenta criar
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Falha ao criar diretório de uploads.']);
        exit;
    }
}

// Verifica se o arquivo foi enviado corretamente
if (isset($_FILES['petImage']) && $_FILES['petImage']['error'] == 0) {
    
    // Pega a extensão (vamos forçar .jpg já que comprimimos para jpeg)
    $file_extension = '.jpg';
    
    // Cria um nome de arquivo único para evitar sobreposições
    $file_name = uniqid('pet_', true) . $file_extension;
    
    // Caminho completo do destino do arquivo
    $destination = $upload_dir . $file_name;

    // Move o arquivo temporário (enviado) para o destino final
    if (move_uploaded_file($_FILES['petImage']['tmp_name'], $destination)) {
        
        // Sucesso! Retorna o caminho do arquivo para o JavaScript
        echo json_encode([
            'success' => true,
            'filePath' => $destination // Envia ex: "uploaded_img/pet_1234567.jpg"
        ]);
        
    } else {
        // Erro ao mover o arquivo
        echo json_encode(['success' => false, 'error' => 'Falha ao salvar o arquivo no servidor. Verifique permissões.']);
    }
    
} else {
    // Nenhum arquivo recebido ou erro no upload
    echo json_encode(['success' => false, 'error' => 'Nenhum arquivo recebido ou erro no envio. Código: ' . ($_FILES['petImage']['error'] ?? 'N/A')]);
}

?>