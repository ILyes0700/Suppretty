<?php
header("Content-Type: application/json");
session_start();

if (!isset($_SESSION['id_res'])) {  
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);      
    exit();
}

$id_res = $_SESSION['id_res'];  

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;
$content = $input['content'] ?? '';

if (!$id || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit();
}

try {
    $conn = new PDO("mysql:host=localhost;dbname=produits_db", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->prepare("UPDATE notess SET content = ? WHERE id = ? AND id_res = ?");
    $stmt->execute([$content, $id, $id_res]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucune note modifiée']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>