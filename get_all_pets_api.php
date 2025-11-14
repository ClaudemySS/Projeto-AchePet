<?php
include 'config.php';
header('Content-Type: application/json');

$response = ['success' => false, 'pets' => []];

try {
    // Esta query busca todos os pets QUE TÊM coordenadas
    // E também busca a primeira imagem de cada um (usando a mesma subquery do index.php)
    $stmt = $conn->prepare("
        SELECT p.id, p.nome, p.status, p.latitude, p.longitude, pi.img_url
        FROM pets p
        LEFT JOIN (
            -- Sub-consulta para pegar apenas UMA imagem (a primeira) por pet
            SELECT pet_id, img_url 
            FROM pet_images 
            GROUP BY pet_id
        ) pi ON p.id = pi.pet_id
        WHERE p.latitude IS NOT NULL AND p.longitude IS NOT NULL
        ORDER BY p.data_cadastro DESC
    ");
    
    $stmt->execute();
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['success'] = true;
    $response['pets'] = $pets;

} catch (Exception $e) {
    $response['error'] = 'Erro de banco de dados: ' . $e->getMessage();
}

// Retorna o JSON
echo json_encode($response);
?>