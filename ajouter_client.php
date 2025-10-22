<?php
// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "produits_db";
session_start();
if(!isset($_SESSION['id_res'])){
    header('Location: index.php');
    exit();
}
$id_res=$_SESSION['id_res'];
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ajouter un client
    if (isset($_POST['ajouter_client'])) {
        $nom_client = $_POST['nom_client'];
        $stmt = $conn->prepare("INSERT INTO clients (nom,id_res) VALUES (:nom,$id_res)");
        $stmt->bindParam(':nom', $nom_client);
        $stmt->execute();
        header("Location: karni.php");
        exit;
    }

} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Client</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #4f46e5;
            --accent: #f43f5e;
            --background: #f8fafc;
            --text: #1e293b;
            --sidebar-bg: rgba(255, 255, 255, 0.95);
            --sidebar-border: rgba(209, 213, 219, 0.3);
            --sidebar-icon: #4f46e5;
            --sidebar-icon-hover: #6366f1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: var(--background);
            color: var(--text);
            min-height: 100vh;
            padding-right: 120px;
        }

        /* Style de la sidebar */
        .sidebar {
            position: fixed;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            width: 90px;
            background: var(--sidebar-bg);
            backdrop-filter: blur(12px);
            border-radius: 24px;
            padding: 1.5rem 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.2rem;
            box-shadow: 
                0 8px 32px rgba(0, 0, 0, 0.05),
                0 4px 12px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--sidebar-border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
        }

        .sidebar:hover {
            box-shadow: 
                0 12px 48px rgba(0, 0, 0, 0.08),
                0 6px 24px rgba(0, 0, 0, 0.05);
            transform: translateY(-50%) scale(1.02);
        }

        .sidebar a {
            color: var(--sidebar-icon);
            font-size: 1.6rem;
            padding: 18px;
            border-radius: 16px;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        

        .sidebar a::after {
            content: attr(data-tooltip);
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: var(--sidebar-icon);
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .sidebar a:hover::after {
            opacity: 1;
        }

        .sidebar a:hover {
            color: var(--sidebar-icon-hover);
            background: rgba(99, 102, 241, 0.08);
            transform: scale(1.15);
        }

        .sidebar a i {
            transition: transform 0.3s ease;
        }

        .sidebar a:hover i {
            transform: rotate(-10deg);
        }

        /* Contenu principal */
        .main-content {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
        }

        /* Formulaire moderne */
        .form-container {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 480px;
            transform: translateY(-5%);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h1 {
            font-size: 2rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        .form-header i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }

        input[type="text"] {
            width: 100%;
            padding: 14px 14px 14px 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            outline: none;
        }

        button[type="submit"] {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.2);
        }

        button[type="submit"] i {
            transition: transform 0.3s ease;
        }

        button[type="submit"]:hover i {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="calcul.html" data-tooltip="Calculatrice">
            <i class="fas fa-calculator"></i>
        </a>
        <a href="index.html" data-tooltip="Accueil">
            <i class="fas fa-home"></i>
        </a>
        <a href="ajouter_client.php" data-tooltip="Nouveau client">
            <i class="fas fa-plus"></i>
        </a>
        <a href="karni.php" data-tooltip="Panier">
            <i class="fas fa-shopping-cart"></i>
        </a>
    </div>

    <div class="main-content">
        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-user-plus"></i>
                <h1>Nouveau Client</h1>
            </div>
            
            <form method="POST">
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="nom_client" placeholder="Nom complet du client" required>
                </div>
                
                <button type="submit" name="ajouter_client">
                    <i class="fas fa-user-check"></i>
                    Ajouter Client
                </button>
            </form>
        </div>
    </div>
</body>
</html>