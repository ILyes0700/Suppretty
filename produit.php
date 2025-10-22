<?php
// Configuration de la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "produits_db"; // Remplacez avec le nom de votre base de données

// Créer la connexion à la base de données
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Vérifier si l'ID du produit est passé dans l'URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Sélectionner les informations du produit avec cet ID
    $sql = "SELECT * FROM produits WHERE codeqr = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Vérifier si le produit existe
    if ($result->num_rows > 0) {
        // Récupérer les données du produit
        $produit = $result->fetch_assoc();
        $nom = $produit['nom'];
        $prix = $produit['prix'];
        $image = $produit['image'];
        $codeqr = $produit['codeqr'];
    } else {
        echo "Produit non trouvé.";
    }
} else {
    echo "Aucun ID de produit spécifié.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Produit</title>
</head>
<body>
    <nav>
        <ul>
            <li><a href="index.html">Accueil</a></li>
            <li><a href="ajouter_produit.html">Ajouter un produit</a></li>
            <li><a href="produit.php?id=12345">Voir un produit</a></li>
        </ul>
    </nav>

    <header>
        <h1>Détails du Produit</h1>
    </header>

    <main>
        <?php if (isset($produit)): ?>
            <h2>Nom : <?php echo htmlspecialchars($nom); ?></h2>
            <p><strong>Prix : </strong><?php echo htmlspecialchars($prix); ?> €</p>
            <p><strong>Code QR : </strong><?php echo htmlspecialchars($codeqr); ?></p>
            <img src="<?php echo htmlspecialchars($image); ?>" alt="Image du produit" style="max-width: 300px;">
        <?php endif; ?>
    </main>

</body>
</html>
