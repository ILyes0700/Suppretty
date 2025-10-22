<?php
session_start();

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['id_res']) && isset($_SESSION['responsable_nom'])) {
    header('Location: test.html');
    exit();
}

// Connexion à la base de données
$host = 'localhost';
$dbname = 'produits_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_res = $_POST['id_res'] ?? '';
    
    if (!empty($id_res)) {
        // Vérifier si l'ID existe dans la table responsable
        $stmt = $pdo->prepare("SELECT * FROM responsable WHERE id_res = ?");
        $stmt->execute([$id_res]);
        $responsable = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($responsable) {
            // ID valide, créer la session
            $_SESSION['id_res'] = $responsable['id_res'];
            $_SESSION['responsable_nom'] = $responsable['nom'] . ' ' . $responsable['prenom'];
            $_SESSION['nom_ent'] = $responsable['nom_ent'];
            
            // Rediriger vers la page principale
            header('Location: test.html');
            exit();
        } else {
            $error = "ID responsable incorrect !";
        }
    } else {
        $error = "Veuillez entrer un ID responsable !";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Panier Intelligent</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #4f46e5;
            --accent: #f43f5e;
            --background: #f8fafc;
            --text: #1e293b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f3f9ffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1rem;
        }

        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
        }

        .logo {
            margin-bottom: 2rem;
        }

        .logo i {
            font-size: 4rem;
            color: #f43f5e;
            margin-bottom: 1rem;
        }

        .logo h1 {
            color: var(--text);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .logo p {
            color: #64748b;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 600;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
        }

        input[type="number"] {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.3s;
            background: #f8fafc;
        }

        input[type="number"]:focus {
            outline: none;
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
            background: #f43f5e;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
        }

        .btn:hover {
            background: #f5294bff;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #fecaca;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }

        .responsables-list {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 1rem;
            text-align: left;
        }

        .responsables-list h3 {
            color: var(--text);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .responsable-item {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border-radius: 0.5rem;
            border-left: 4px solid var(--primary);
        }

        .responsable-id {
            font-weight: 600;
            color: var(--primary);
        }

        .responsable-info {
            color: #64748b;
            font-size: 0.9rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem;
            }
            
            .logo i {
                font-size: 3rem;
            }
            
            .logo h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-shopping-cart"></i>
            <h1>Panier Intelligent</h1>
            <p>Connectez-vous avec votre ID responsable</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="id_res">
                    <i class="fas fa-id-card"></i>
                    ID Responsable
                </label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="number" 
                           id="id_res" 
                           name="id_res" 
                           placeholder="Entrez votre ID responsable" 
                           required 
                           min="1"
                           value="<?php echo htmlspecialchars($_POST['id_res'] ?? ''); ?>">
                </div>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i>
                Se Connecter
            </button>
        </form>

        
    </div>

    <script>
        // Empêcher l'entrée de valeurs négatives
        document.getElementById('id_res').addEventListener('input', function() {
            if (this.value < 0) this.value = '';
        });

        // Focus automatique sur le champ ID
        document.getElementById('id_res').focus();
    </script>
</body>
</html>