<?php
// Configuration de la base de données
session_start();
$id_res=$_SESSION['id_res'];
header('Content-Type: application/json');
if (!isset($_SESSION['id_res'])) {
    header('Location: index.php');
    exit();
}
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "produits_db";

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    // Récupérer les informations du produit basé sur le code QR (id)
    $sql = "SELECT * FROM produits WHERE codeqr = ? and id_res=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $id, $id_res);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Récupérer les données du produit
        $product = $result->fetch_assoc();
        echo json_encode($product);
    } else {
        echo json_encode(['error' => 'Produit non trouvé']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'ID du produit manquant ou invalide']);
}

$conn->close();
?>
