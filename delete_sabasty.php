<?php
session_start();

if (!isset($_SESSION['id_res'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

if (!isset($_POST['id']) || !isset($_POST['action']) || $_POST['action'] !== 'delete_sabasty') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

$id_res = $_SESSION['id_res'];
$sabasty_id = intval($_POST['id']);

// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "produits_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier que la sabasty appartient à l'utilisateur connecté
    $checkStmt = $conn->prepare("SELECT sabsty FROM sbasty_files WHERE id = :id AND id_res = :id_res");
    $checkStmt->execute([':id' => $sabasty_id, ':id_res' => $id_res]);
    $sabasty = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$sabasty) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Sabasty non trouvée ou non autorisée']);
        exit();
    }

    // Supprimer le fichier physique
    $filePath = 'uploads/sbasty/' . $sabasty['sabsty'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Supprimer l'entrée de la base de données
    $deleteStmt = $conn->prepare("DELETE FROM sbasty_files WHERE id = :id AND id_res = :id_res");
    $deleteStmt->execute([':id' => $sabasty_id, ':id_res' => $id_res]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Sabasty supprimée avec succès']);

} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?>