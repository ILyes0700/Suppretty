<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "produits_db";

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Connection failed: ' . $conn->connect_error]));
}

// Créer la table si elle n'existe pas
$conn->query("CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    product_name VARCHAR(255),
    quantity INT,
    total_price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$data = json_decode(file_get_contents('php://input'), true);
$panier = $data['panier'];

try {
    $conn->begin_transaction();
    
    foreach($panier as $item) {
        $stmt = $conn->prepare("INSERT INTO commandes 
            (product_id, product_name, quantity, total_price) 
            VALUES (?, ?, ?, ?)");
        
        $total = $item['prix'] * $item['quantity'];
        $stmt->bind_param("isid", 
            $item['id'], 
            $item['nom'], 
            $item['quantity'], 
            $total
        );
        
        if(!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $stmt->close();
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>