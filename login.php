<?php
// Mot de passe par défaut (vous pouvez le stocker dans une variable d'environnement ou dans une base de données)
$correct_password = "ilyes098";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Vérification du mot de passe
    $password = $_POST['password'];
    if ($password === $correct_password) {
        // Si le mot de passe est correct, rediriger vers la page historique
        header("Location: historique.php");
        exit();
    } else {
        $error_message = "Mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page de connexion</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f4f7fc;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .btn {
            background-color: #6366f1;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #4f46e5;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Connexion</h2>
    <form method="POST">
        <input type="password" name="password" placeholder="Entrez le mot de passe" required>
        <button type="submit" class="btn">Se connecter</button>
    </form>
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
</div>

</body>
</html>
