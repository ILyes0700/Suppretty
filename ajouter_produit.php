<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration de la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "produits_db";
session_start();
if (!isset($_SESSION['id_res'])) {
    header("Location: index.php");
    exit();
}
$id_res = $_SESSION['id_res'];
// Créer la connexion à la base de données
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupérer les données du formulaire
    $nom = htmlspecialchars($_POST['nom']);
    $prix = filter_var($_POST['prix'], FILTER_VALIDATE_FLOAT);
    $codeqr = $_POST['codeqr'];

    if ($prix === false) {
        die("Erreur : Le prix doit être un nombre valide.");
    }

    // Validation de l'image
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $allowed_types = array("jpg", "jpeg", "png", "gif", "webp");

        if (!in_array($imageFileType, $allowed_types)) {
            die("Erreur : seul les fichiers JPG, JPEG, PNG, GIF et WebP sont autorisés.");
        }

        if ($_FILES["image"]["size"] > 5000000) { // 5MB
            die("Erreur : La taille du fichier est trop grande.");
        }

        $target_file = $target_dir . uniqid() . "." . $imageFileType;

        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            die("Erreur : Le fichier n'a pas pu être téléchargé.");
        }
    } else {
        die("Erreur : Aucune image sélectionnée.");
    }

    // Préparer l'insertion dans la base de données
    $stmt = $conn->prepare("INSERT INTO produits (nom, prix, image, codeqr,id_res) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        die("Erreur de préparation de la requête : " . $conn->error);
    }

    $stmt->bind_param("sdssi", $nom, $prix, $target_file, $codeqr, $id_res);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: test.html");
        exit();
    } else {
        error_log("Erreur SQL: " . $stmt->error);
        die("Une erreur s'est produite lors de l'ajout du produit.");
    }
}

$conn->close();
?>