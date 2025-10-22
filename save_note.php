<?php
header("Content-Type: application/json");
require 'db_connect.php';
session_start();
if (!isset($_SESSION['id_res'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);      
    exit();
}
// Récupérer les données JSON envoyées  
$id_res = $_SESSION['id_res'];  
$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $conn->prepare("INSERT INTO notess (content,id_res) VALUES (?, $id_res)");
    $stmt->execute([$data['content']]);
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}