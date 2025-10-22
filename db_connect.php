<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "produits_db";
try {
    $conn = new PDO(
        "mysql:host=$servername;dbname=$dbname",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>