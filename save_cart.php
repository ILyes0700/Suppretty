<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['id_res'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Non autorisé']));
}
$id_res = $_SESSION['id_res'];
// Log des données reçues
file_put_contents('debug.log', "[" . date('Y-m-d H:i:s') . "] Données reçues:\n" . file_get_contents('php://input') . "\n\n", FILE_APPEND);

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => 'Données JSON invalides. Erreur: ' . json_last_error_msg(),
        'received' => file_get_contents('php://input')
    ]));
}

// Configuration BDD (à adapter)
$config = [
    'host' => 'localhost',
    'dbname' => 'produits_db',
    'user' => 'root',
    'pass' => ''
];

try {
    $conn = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Test de connexion
    $conn->query("SELECT 1")->execute();

} catch (PDOException $e) {
    die(json_encode([
        'success' => false,
        'error' => 'Erreur de connexion BDD: ' . $e->getMessage(),
        'config' => $config
    ]));
}

try {
    $conn->beginTransaction();
    
    $stmt = $conn->prepare("INSERT INTO commandes 
        (product_id,id_res, product_name, quantity, total_price, created_at) 
        VALUES 
        (:product_id,$id_res, :product_name, :quantity, :total_price, NOW())");

    foreach ($data as $index => $item) {
        // Validation approfondie
        $errors = [];
        if (!isset($item['id'])) $errors[] = 'id manquant';
        if (!isset($item['nom'])) $errors[] = 'nom manquant';
        if (!isset($item['prix'])) $errors[] = 'prix manquant';
        if (!isset($item['quantity'])) $errors[] = 'quantity manquant';
        
        if (!empty($errors)) {
            throw new Exception("Item #$index invalide: " . implode(', ', $errors));
        }

        $total_price = $item['prix'] * $item['quantity'];
        
        $stmt->execute([
            ':product_id' => (int)$item['id'],
            ':product_name' => substr($item['nom'], 0, 255),
            ':quantity' => (int)$item['quantity'],
            ':total_price' => number_format($total_price, 2, '.', '')
        ]);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'inserted_id' => $conn->lastInsertId(),
        'items_count' => count($data)
    ]);

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString() // À retirer en production
    ]);
}
?>