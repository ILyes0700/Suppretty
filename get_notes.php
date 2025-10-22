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
        $stmt = $conn->prepare("SELECT * FROM notes WHERE client_id = :client_id ORDER BY id DESC");
        $stmt->bindParam(':client_id', $client_id);
        $stmt->execute();
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($notes);
    }
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>