<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "produits_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (isset($_GET['client_id'])) {
        $client_id = $_GET['client_id'];
        $stmt = $conn->prepare("SELECT * FROM panier WHERE client_id = :client_id ORDER BY date DESC");
        $stmt->bindParam(':client_id', $client_id);
        $stmt->execute();
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($history);
    }
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>