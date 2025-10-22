<?php  
// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "produits_db";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
session_start();
if (!isset($_SESSION['id_res'])) {
    header('Location: index.php');
    exit();
}
$id_res=$_SESSION['id_res'];

// Récupérer le terme de recherche s'il existe
$search_term = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = trim($_GET['search']);
}

require 'vendor/autoload.php';  // Autoload de Composer pour PHPMailer

try {
    // Connexion à la base de données
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Traitement de l'ajout d'un client
    if (isset($_POST['ajouter_client'])) {
        $nom_client = trim($_POST['nom_client']);
        
        if (!empty($nom_client)) {
            $stmt = $conn->prepare("INSERT INTO clients (nom, id_res, created_at) VALUES (:nom, :id_res, NOW())");
            $stmt->execute([
                ':nom' => $nom_client,
                ':id_res' => $id_res
            ]);
            header("Location: karni.php");
            exit;
        }
    }

    // Vérifier si la requête est un envoi d'email
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') { 
        $client_id = intval($_POST['client_id']);
        $recipient_email = filter_var($_POST['recipient_email'], FILTER_SANITIZE_EMAIL);

        // Récupérer les données du panier pour ce client (en utilisant client_id)
        $stmt = $conn->prepare("SELECT * FROM panier WHERE client_id = :client_id AND id_res = :id_res");
        $stmt->execute([
            ':client_id' => $client_id,
            ':id_res' => $id_res
        ]);
        $panier = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Vérifier s'il y a un panier
        if (empty($panier)) {
            echo "Aucun panier trouvé pour ce client.";
            exit;
        }

        // Initialiser le total du panier
        $total = 0;

        // Construire le contenu HTML de l'email avec les informations du panier
        $emailContent = "<h2 style='font-weight: normal; color: #333;'>Détails de votre panier</h2>";
        $emailContent .= "<table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>";
        $emailContent .= "<thead style='background-color: #f5f5f5;'><tr><th>Date</th><th>Montant</th></tr></thead><tbody>";

        // Parcourir les produits du panier et ajouter à l'email
        foreach ($panier as $item) {
            $date = htmlspecialchars($item['date']);
            $montant = number_format($item['montant'], 2, '.', ',');
            $emailContent .= "<tr><td>{$date}</td><td>{$montant} د.ت</td></tr>";
            $total += $item['montant'];
        }

        // Afficher le total du panier
        $emailContent .= "</tbody></table>";
        $emailContent .= "<h3 style='margin-top: 20px; color: #333;'>Total : <span style='color: #e74c3c;'>" . number_format($total, 2) . " د.ت</span></h3>";

        // Configuration de PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'pharfind@gmail.com';
            $mail->Password = 'stag hgcx gvxm irwd';  // Assurez-vous de ne jamais exposer vos mots de passe en production !
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('pharfind@gmail.com', 'Hamrouni');
            $mail->addAddress($recipient_email);
            $mail->isHTML(true);
            $mail->Subject = 'Détails de votre panier';
            $mail->Body = "
                <html>
                <head>
                    <style>
                        body { font-family: 'Arial', sans-serif; background-color: #f9f9f9; color: #333; margin: 0; padding: 20px; }
                        .container { background-color: #ffffff; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 8px; }
                        h2, h3 { font-weight: normal; }
                        table { width: 100%; margin-top: 20px; }
                        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #f5f5f5; }
                        th { background-color: #f5f5f5; }
                        .total { color: #e74c3c; font-size: 1.2em; }
                        p { font-size: 1em; color: #777; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        $emailContent
                        <center><p style='color: #4CAF50;'>Cordialement<br>L'équipe Hamrouni</p></center>
                    </div>
                </body>
                </html>
            ";

            // Envoi de l'email
            $mail->send();
            header("Location: " . $_SERVER['REQUEST_URI']);
        } catch (Exception $e) {
            echo "Le message n'a pas pu être envoyé. Erreur de PHPMailer: {$mail->ErrorInfo}";
        }

        exit;
    }

    // Traitement de l'ajout au panier
    if (isset($_POST['ajouter_montant'])) {
        $client_id = $_POST['client_id'];
        $montant = $_POST['montant'];
        
        $stmt = $conn->prepare("INSERT INTO panier (client_id, montant, id_res) VALUES (:client_id, :montant, :id_res)");
        $stmt->execute([
            ':client_id' => $client_id,
            ':montant' => $montant,
            ':id_res' => $id_res
        ]);
        header("Location: karni.php");
        exit;
    }

    // Traitement de la suppression du panier
    if (isset($_POST['vider_panier'])) {
        $client_id = $_POST['client_id'];
        
        // CORRECTION : Ajout du paramètre id_res manquant
        $stmt = $conn->prepare("DELETE FROM panier WHERE client_id = :client_id AND id_res = :id_res");
        $stmt->execute([
            ':client_id' => $client_id,
            ':id_res' => $id_res
        ]);
        header("Location: karni.php");
        exit;
    }

    // Récupérer tous les clients triés par nom (avec recherche si applicable)
    if (!empty($search_term)) {
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id_res = :id_res AND nom LIKE :search_term ORDER BY nom ASC");
        $stmt->execute([
            ':id_res' => $id_res, 
            ':search_term' => '%' . $search_term . '%'
        ]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id_res = :id_res ORDER BY nom ASC");
        $stmt->execute([
            ':id_res' => $id_res
        ]);
    }
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organiser les clients par lettre initiale
    $groupedClients = [];
    foreach ($clients as $client) {
        $firstLetter = strtoupper($client['nom'][0]);
        if (!isset($groupedClients[$firstLetter])) {
            $groupedClients[$firstLetter] = [];
        }
        $groupedClients[$firstLetter][] = $client;
    }

    // Récupérer les paniers pour chaque client
    $stmt_panier = $conn->prepare("SELECT * FROM panier WHERE id_res = :id_res");
    $stmt_panier->execute([
        ':id_res' => $id_res
    ]);
    $paniers = $stmt_panier->fetchAll(PDO::FETCH_ASSOC);

    // Ajouter une note
    if (isset($_POST['ajouter_note'])) {
        $client_id = $_POST['client_id'];
        $note = $_POST['note'];
        $stmt = $conn->prepare("INSERT INTO notes (client_id, note) VALUES (:client_id, :note)");
        $stmt->execute([
            ':client_id' => $client_id,
            ':note' => $note
        ]);
        header("Location: karni.php");
        exit;
    }

    // Supprimer une note
    if (isset($_POST['supprimer_note'])) {
        $note_id = $_POST['note_id'];
        $stmt = $conn->prepare("DELETE FROM notes WHERE id = :note_id");
        $stmt->execute([
            ':note_id' => $note_id
        ]);
        header("Location: karni.php");
        exit;
    }

    // Récupérer les notes pour chaque client
    $stmt_notes = $conn->query("SELECT * FROM notes");
    $notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Clients - Grotte</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #4f46e5;
            --accent: #f43f5e;
            --success: #10b981;
            --background: #f8fafc;
            --text: #1e293b;
            --card-bg: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
            padding: 1rem;
            padding-bottom: 80px; /* Space for mobile sidebar */
        }

        /* Sidebar mobile en bas */
        .sidebar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 70px;
            background: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 0 10px;
            z-index: 1000;
        }

        .nav-icon {
            padding: 8px;
            border-radius: 50%;
            transition: all 0.3s;
            color: #6366f1;
            text-decoration: none;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.7rem;
        }

        .nav-icon:hover {
            background: #6366f110;
            transform: scale(1.1);
        }

        .nav-icon.active {
            color: white;
            background: #6366f1;
        }

        .nav-icon i {
            font-size: 1.2rem;
            margin-bottom: 3px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        header.page-header {
            margin-bottom: 1.5rem;
        }

        h1 {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        h1 i {
            font-size: 1.6rem;
        }

        .client-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.2rem;
        }

        .client-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.2rem;
            box-shadow: var(--shadow);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .client-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .client-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .client-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex: 1;
        }

        .client-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .client-name {
            font-size: 1.1rem;
            color: var(--text);
            font-weight: 600;
            word-break: break-word;
        }

        .client-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .action-icon {
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .note-icon { color: var(--primary); }
        .history-icon { color: var(--success); }
        .email-icon { color: var(--accent); }

        .action-icon:hover {
            background: rgba(99, 102, 241, 0.1);
            transform: scale(1.1);
        }

        .total {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            margin: 0.8rem 0;
            padding: 0.6rem;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 6px;
            color: var(--primary);
        }

        .input-group {
            position: relative;
            margin: 0.8rem 0;
        }

        .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
        }

        input[type="number"] {
            width: 100%;
            padding: 10px 40px 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        input[type="number"]:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            outline: none;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
        }
        /* Styles pour les modales de notes */
.note-textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    font-family: inherit;
    resize: vertical;
    min-height: 100px;
    transition: all 0.3s ease;
    background: #fafafa;
}

.note-textarea:focus {
    border-color: var(--primary);
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-group {
    margin-bottom: 1.5rem;
}

.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

.modal-actions .btn {
    min-width: 120px;
    justify-content: center;
}

/* Styles pour les boutons dans les modales */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--secondary);
    transform: translateY(-1px);
}

.btn-secondary {
    background: #64748b;
    color: white;
}

.btn-secondary:hover {
    background: #475569;
    transform: translateY(-1px);
}

.btn-danger {
    background: var(--accent);
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
}

/* Responsive pour les modales */
@media (max-width: 768px) {
    .modal-actions {
        flex-direction: column;
    }
    
    .modal-actions .btn {
        width: 100%;
    }
}

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-1px);
        }

        .btn-danger {
            background: #f43f5e1a;
            color: var(--accent);
            border: 2px solid #f43f5e33;
        }

        .btn-danger:hover {
            background: #f43f5e26;
        }

        .section-letter {
            font-size: 1.5rem;
            color: var(--accent);
            margin: 1.5rem 0 0.8rem;
            padding-left: 0.8rem;
            border-left: 3px solid var(--accent);
        }

        /* Modales */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            padding: 1rem;
        }

        .modal-content, .notes-modal-content {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalSlide 0.3s ease-out;
        }
        /* Styles pour la modal de confirmation */
.modal-body {
    padding: 1rem 0;
}

.confirmation-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.btn-secondary {
    background: #64748b;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #475569;
    transform: translateY(-1px);
}

.confirmation-buttons .btn {
    flex: 1;
    min-width: 120px;
    justify-content: center;
}

        @keyframes modalSlide {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .modal-close {
            cursor: pointer;
            font-size: 1.3rem;
            color: #64748b;
        }

        /* Formulaire email */
        .email-form {
            display: none;
            margin-top: 1rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.8rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .email-form.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .price-input {
            flex: 1;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            background: #fafafa;
            transition: border-color 0.3s ease;
        }

        .price-input:focus {
            border-color: var(--primary);
            outline: none;
        }

        .btn-submit {
            background: var(--success);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
        }

        /* Recherche */
        .search-container {
            margin: 1.5rem 0;
        }

        .search-box {
            display: flex;
            gap: 0.8rem;
            align-items: center;
            background: white;
            padding: 0.8rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            flex-wrap: wrap;
        }

        .search-box:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .search-input {
            flex: 1;
            min-width: 200px;
            padding: 10px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .search-btn, .clear-search {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .search-btn {
            background: var(--primary);
            color: white;
        }

        .search-btn:hover {
            background: var(--secondary);
        }

        .clear-search {
            background: #64748b;
            color: white;
        }

        .clear-search:hover {
            background: #475569;
        }

        .search-results-info {
            margin: 1rem 0;
            padding: 0.8rem;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 6px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }

        .no-results i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        /* Styles pour les écrans plus larges */
        @media (min-width: 768px) {
            body {
                padding: 2rem;
                padding-right: 80px; /* Space for desktop sidebar */
                padding-bottom: 2rem;
            }

            .sidebar {
                position: fixed;
                right: 0;
                top: 0;
                left: auto;
                width: 70px;
                height: 100vh;
                flex-direction: column;
                justify-content: flex-start;
                padding-top: 2rem;
                box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
                bottom: auto;
            }

            .nav-icon {
                margin: 15px 0;
                font-size: 0.8rem;
            }

            .nav-icon i {
                font-size: 1.4rem;
                margin-bottom: 5px;
            }

            h1 {
                font-size: 2.2rem;
            }

            h1 i {
                font-size: 2rem;
            }

            .client-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 1.5rem;
            }

            .client-card {
                padding: 1.5rem;
            }

            .client-name {
                font-size: 1.2rem;
            }

            .action-icon {
                font-size: 1rem;
            }

            .button-group {
                flex-direction: row;
            }

            .btn {
                flex: 1;
            }
        }

        @media (min-width: 1024px) {
            .client-grid {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }

        @media (max-width: 767px) {
            .search-box {
                flex-direction: column;
            }
            
            .search-input {
                min-width: 100%;
            }
            
            .search-btn, .clear-search {
                width: 100%;
                justify-content: center;
            }
            
            .client-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
            }
            
            .client-actions {
                align-self: flex-end;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 0.8rem;
                padding-bottom: 70px;
            }
            
            .client-card {
                padding: 1rem;
            }
            
            .client-icon {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            
            .client-name {
                font-size: 1rem;
            }
            
            .section-letter {
                font-size: 1.3rem;
            }
            
            .modal-content, .notes-modal-content {
                padding: 1rem;
            }
        }

        /* Tableaux dans les modales */
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .history-table th, 
        .history-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .history-table th {
            background-color: #f8fafc;
            color: #64748b;
        }

        .total-badge {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        /* Notes */
        .note-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.8rem;
            background: #f8fafc;
            border-radius: 6px;
            margin: 0.4rem 0;
            transition: all 0.2s ease;
        }

        .note-item:hover {
            transform: translateX(3px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .note-actions {
            display: flex;
            gap: 0.6rem;
        }

        .note-action-icon {
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.2s ease;
            font-size: 0.8rem;
        }

        .note-edit {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
        }

        .note-edit:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .note-delete {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }

        .note-delete:hover {
            background: rgba(239, 68, 68, 0.2);
        }

        .note-input-group {
            display: flex;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .note-input {
            flex: 1;
            padding: 0.6rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .note-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .btn-add-note {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .btn-add-note:hover {
            background: var(--secondary);
        }

        /* Styles pour le formulaire d'ajout de client */
        .add-client-form {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            border: 2px dashed #e2e8f0;
            transition: all 0.3s ease;
        }

        .add-client-form:hover {
            border-color: var(--primary);
        }

        .add-client-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .add-client-icon {
            width: 40px;
            height: 40px;
            background: var(--success);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .add-client-title {
            font-size: 1.3rem;
            color: var(--text);
            font-weight: 600;
        }

        .add-client-input-group {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .client-name-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .client-name-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .btn-add-client {
            background: var(--success);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .btn-add-client:hover {
            background: #0da271;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .add-client-input-group {
                flex-direction: column;
                gap: 0.8rem;
            }
            
            .btn-add-client {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- Barre de navigation responsive -->
<div class="sidebar">
        <a href="calcul.html" class="nav-icon" title="Calculatrice" onclick="showCalculator(event)">
            <i class="fas fa-calculator"></i>
        </a>
        <a href="prod.php" class="nav-icon " title="Panier">
            <i class="fas fa-shopping-cart"></i>
        </a>
        
        <a href="historique.php" class="nav-icon" title="Historique">
            <i class="fas fa-history"></i>
        </a>
        
        <a href="ajouter_produit.html" class="nav-icon" title="Ajouter produit">
            <i class="fas fa-plus"></i>
        </a>
        <a href="test.html" class="nav-icon" title="Home">
            <i class="fas fa-home"></i>
        </a>
        
        
        <a href="karni.php" class="nav-icon active" title="Utilisateurs"> 
            <i class="fas fa-users"></i>
        </a>
        
        <a href="note.php" class="nav-icon" title="Note">
            <i class="fas fa-edit"></i>
        </a>
        <a href="factures.php" class="nav-icon" title="facteur" >
            <i class="fas fa-file-invoice"></i>
        </a>
        <a href="a4ya.php" class="nav-icon" title="Stock">
            <i class="fas fa-boxes"></i> 
        </a>
         <a href="sbasty.php" class="nav-icon" title="sbasa">
            <i class="fas fa-users"></i> 
        </a>
        <a href="gen.php" class="nav-icon" title="Générer QR code" onclick="showqr(event)">
            <i class="fas fa-qrcode"></i>
        </a>
    </div>
<div class="container">
    <header class="page-header">
        <h1><i class="fas fa-users-cog"></i> KARNY</h1>
    </header>

    <!-- Formulaire d'ajout de client -->
    <div class="add-client-form">
        <div class="add-client-header">
            <div class="add-client-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h3 class="add-client-title">Ajouter un nouveau client</h3>
        </div>
        <form method="POST" class="add-client-input-group">
            <input type="text" 
                   name="nom_client" 
                   class="client-name-input" 
                   placeholder="Nom du client..." 
                   required
                   autocomplete="off">
            <button type="submit" name="ajouter_client" class="btn-add-client">
                <i class="fas fa-plus"></i>
                Ajouter le client
            </button>
        </form>
    </div>

    <!-- Zone de recherche -->
    <div class="search-container">
        <form method="GET" action="" class="search-box">
            <input type="text" 
                   name="search" 
                   class="search-input" 
                   placeholder="Rechercher un client par nom..." 
                   value="<?= htmlspecialchars($search_term) ?>"
                   autocomplete="off">
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
                Rechercher
            </button>
            <?php if (!empty($search_term)): ?>
                <a href="karni.php" class="clear-search">
                    <i class="fas fa-times"></i>
                    Effacer
                </a>
            <?php endif; ?>
        </form>
        
        <?php if (!empty($search_term)): ?>
            <div class="search-results-info">
                <i class="fas fa-info-circle"></i>
                Résultats pour : "<strong><?= htmlspecialchars($search_term) ?></strong>"
                (<?= count($clients) ?> client(s) trouvé(s))
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($clients) && !empty($search_term)): ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>Aucun client trouvé</h3>
            <p>Aucun client ne correspond à votre recherche "<?= htmlspecialchars($search_term) ?>"</p>
            <a href="karni.php" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-arrow-left"></i>
                Voir tous les clients
            </a>
        </div>
    <?php else: ?>
        <?php foreach (range('A', 'Z') as $letter): ?>
            <?php if (isset($groupedClients[$letter])): ?>
                <div class="section-letter"><?= $letter ?></div>
                <div class="client-grid">
                    <?php foreach ($groupedClients[$letter] as $client): ?>
                        <div class="client-card">
                            <div class="client-header">
                                <div class="client-info">
                                    <div class="client-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <h3 class="client-name"><?= $client['nom'] ?></h3>
                                </div>
                                <div class="client-actions">
                                    <i class="fas fa-sticky-note action-icon note-icon" 
                                       onclick="toggleNote(<?= $client['id'] ?>, '<?= $client['nom'] ?>')"></i>
                                    <i class="fas fa-history action-icon history-icon" 
                                       onclick="showHistory(<?= $client['id'] ?>, '<?= $client['nom'] ?>')"></i>
                                    <i class="fas fa-paper-plane action-icon email-icon" 
                                       onclick="toggleEmailForm(<?= $client['id'] ?>)"></i>
                                </div>
                            </div>

                            <!-- Formulaire email -->
                            <form method="POST" class="email-form" id="email-form-<?= $client['id'] ?>">
                                <input type="hidden" name="action" value="send_email">
                                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                <div class="input-group">
                                    <input type="email" name="recipient_email" class="price-input" 
                                           placeholder="Entrez l'adresse email" required>
                                    <button type="submit" class="btn-submit">
                                        <i class="fas fa-paper-plane"></i>
                                        Envoyer
                                    </button>
                                </div>
                            </form>

                            <?php
                            $client_id = $client['id'];
                            $total = 0;
                            foreach ($paniers as $panier) {
                                if ($panier['client_id'] == $client_id) {
                                    $total += $panier['montant'];
                                }
                            }
                            ?>

                            <?php if ($total > 0): ?>
                                <div class="total">
                                    <i class="fas fa-coins"></i>
                                    <span>Total : <?= $total ?> د.ت</span>
                                </div>
                            <?php endif; ?>

                            <form method="post">
                                <div class="input-group">
                                    <input type="number" name="montant" placeholder="Montant" step="0.01" >
                                    <span class="input-icon">د.ت</span>
                                </div>

                                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                <div class="button-group">
                                    <button type="submit" name="ajouter_montant" class="btn btn-primary">
                                        <i class="fas fa-cart-plus"></i>
                                        Ajouter
                                    </button>
                                    <button type="button" onclick="confirmViderPanier(<?= $client['id'] ?>)" class="btn btn-danger">
    <i class="fas fa-trash-alt"></i>
    Vider
</button>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modales -->
<div class="modal-overlay" id="notesModal">
    <div class="notes-modal-content">
        <div class="modal-header">
            <h3 id="notesModalTitle"></h3>
            <span class="modal-close" onclick="closeNotesModal()">&times;</span>
        </div>
        <div id="notesModalContent"></div>
    </div>
</div>

<div class="modal-overlay" id="historyModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"></h3>
            <span class="modal-close" onclick="closeModal()">&times;</span>
        </div>
        <div id="modalContent"></div>
    </div>
</div>

<script>
    // Fonction pour afficher la confirmation de vider le panier
function confirmViderPanier(clientId) {
    document.getElementById('confirmClientId').value = clientId;
    document.getElementById('confirmViderModal').style.display = 'flex';
}

// Fonction pour fermer la modal de confirmation
function closeConfirmModal() {
    document.getElementById('confirmViderModal').style.display = 'none';
}

// Fermer la modal en cliquant à l'extérieur
window.onclick = function(event) {
    const historyModal = document.getElementById('historyModal');
    const notesModal = document.getElementById('notesModal');
    const confirmModal = document.getElementById('confirmViderModal');
    
    if (event.target === historyModal) closeModal();
    if (event.target === notesModal) closeNotesModal();
    if (event.target === confirmModal) closeConfirmModal();
}
    // Fonctions pour les modales
    async function showHistory(clientId, clientName) {
        try {
            const response = await fetch(`get_panier.php?client_id=${clientId}&ajax=1`);
            const history = await response.json();
            
            let html = `<table class="history-table">
                            <thead>
                                <tr>
                                    <th>Montant (TND)</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>`;
            
            let total = 0;
            history.forEach(entry => {
                html += `<tr>
                            <td>${parseFloat(entry.montant).toFixed(2)}</td>
                            <td>${new Date(entry.date).toLocaleDateString()}</td>
                        </tr>`;
                total += parseFloat(entry.montant);
            });

            html += `</tbody></table>
                    <div class="total-badge">
                        <i class="fas fa-coins"></i>
                        Total: ${total.toFixed(2)} TND
                    </div>`;

            document.getElementById('modalTitle').textContent = `Historique de ${clientName}`;
            document.getElementById('modalContent').innerHTML = html;
            document.getElementById('historyModal').style.display = 'flex';
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    function closeModal() {
        document.getElementById('historyModal').style.display = 'none';
    }

    async function showNotes(clientId, clientName) {
    try {
        const response = await fetch(`get_notes.php?client_id=${clientId}`);
        const notes = await response.json();

        let html = `
            <div class="note-input-group">
                <input type="text" class="note-input" id="newNoteInput" placeholder="Écrire une nouvelle note...">
                <button class="btn-add-note" onclick="addNewNote(${clientId})">
                    <i class="fas fa-plus"></i> Ajouter
                </button>
            </div>`;

        notes.forEach(note => {
            html += `
                <div class="note-item">
                    <div class="note-text">${note.note}</div>
                    <div class="note-actions">
                        <i class="fas fa-edit note-action-icon note-edit" 
                           onclick="openEditNoteModal(${note.id}, '${note.note.replace(/'/g, "\\'")}')"></i>
                        <i class="fas fa-trash-alt note-action-icon note-delete" 
                           onclick="openDeleteNoteModal(${note.id}, '${note.note.replace(/'/g, "\\'")}')"></i>
                    </div>
                </div>`;
        });

        document.getElementById('notesModalTitle').textContent = `Notes de ${clientName}`;
        document.getElementById('notesModalContent').innerHTML = html;
        document.getElementById('notesModal').style.display = 'flex';
    } catch (error) {
        console.error('Erreur:', error);
    }
}

    function closeNotesModal() {
        document.getElementById('notesModal').style.display = 'none';
    }

    async function addNewNote(clientId) {
        const noteInput = document.getElementById('newNoteInput');
        const formData = new FormData();
        formData.append('client_id', clientId);
        formData.append('note', noteInput.value);
        formData.append('ajouter_note', true);

        try {
            await fetch('karni.php', {
                method: 'POST',
                body: formData
            });
            location.reload();
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    function editNote(noteId, currentNote) {
        const newNote = prompt('Modifier la note :', currentNote);
        if (newNote !== null) {
            const formData = new FormData();
            formData.append('note_id', noteId);
            formData.append('nouvelle_note', newNote);
            formData.append('modifier_note', true);

            fetch('karni.php', {
                method: 'POST',
                body: formData
            }).then(() => location.reload());
        }
    }

    function toggleNote(clientId, clientName) {
        showNotes(clientId, clientName);
    }

    function toggleEmailForm(clientId) {
        const form = document.getElementById(`email-form-${clientId}`);
        form.classList.toggle('active');
    }

    // Fermer les modales en cliquant à l'extérieur
    window.onclick = function(event) {
        const historyModal = document.getElementById('historyModal');
        const notesModal = document.getElementById('notesModal');
        
        if (event.target === historyModal) closeModal();
        if (event.target === notesModal) closeNotesModal();
    }
// Variables globales pour les notes
let currentEditNoteId = null;
let currentDeleteNoteId = null;

// Fonction pour ouvrir la modal d'édition de note
function openEditNoteModal(noteId, currentContent) {
    currentEditNoteId = noteId;
    
    // Remplir le formulaire avec le contenu actuel
    document.getElementById('editNoteContent').value = currentContent;
    
    // Afficher la modal
    document.getElementById('editNoteModal').style.display = 'flex';
}

// Fonction pour fermer la modal d'édition de note
function closeEditNoteModal() {
    document.getElementById('editNoteModal').style.display = 'none';
    currentEditNoteId = null;
}

// Fonction pour ouvrir la modal de suppression de note
function openDeleteNoteModal(noteId, noteContent) {
    currentDeleteNoteId = noteId;
    
    // Préparer le message de confirmation
    const truncatedContent = noteContent.length > 50 ? noteContent.substring(0, 50) + '...' : noteContent;
    document.getElementById('deleteNoteMessage').textContent = 
        `Êtes-vous sûr de vouloir supprimer cette note ?\n\n"${truncatedContent}"`;
    
    // Afficher la modal
    document.getElementById('deleteNoteModal').style.display = 'flex';
}

// Fonction pour fermer la modal de suppression de note
function closeDeleteNoteModal() {
    document.getElementById('deleteNoteModal').style.display = 'none';
    currentDeleteNoteId = null;
}

// Gestion du formulaire d'édition de note
document.addEventListener('DOMContentLoaded', function() {
    // Formulaire d'édition de note
    const editNoteForm = document.getElementById('editNoteForm');
    if (editNoteForm) {
        editNoteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!currentEditNoteId) return;
            
            const newContent = document.getElementById('editNoteContent').value.trim();
            
            if (newContent) {
                // Créer un formulaire dynamique pour soumettre la modification
                const formData = new FormData();
                formData.append('note_id', currentEditNoteId);
                formData.append('nouvelle_note', newContent);
                formData.append('modifier_note', true);

                // Soumettre le formulaire
                fetch('karni.php', {
                    method: 'POST',
                    body: formData
                }).then(response => {
                    if (response.ok) {
                        // Fermer la modal et recharger la page
                        closeEditNoteModal();
                        location.reload();
                    } else {
                        alert('Erreur lors de la modification de la note');
                    }
                }).catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur de connexion');
                });
            }
        });
    }

    // Gestion de la suppression de note
    const confirmDeleteNoteBtn = document.getElementById('confirmDeleteNoteBtn');
    if (confirmDeleteNoteBtn) {
        confirmDeleteNoteBtn.addEventListener('click', function() {
            if (!currentDeleteNoteId) return;
            
            // Créer un formulaire dynamique pour soumettre la suppression
            const formData = new FormData();
            formData.append('note_id', currentDeleteNoteId);
            formData.append('supprimer_note', true);

            // Soumettre le formulaire
            fetch('karni.php', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    // Fermer la modal et recharger la page
                    closeDeleteNoteModal();
                    location.reload();
                } else {
                    alert('Erreur lors de la suppression de la note');
                }
            }).catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            });
        });
    }

    // Fermer les modales en cliquant à l'extérieur
    document.addEventListener('click', function(event) {
        if (event.target === document.getElementById('editNoteModal')) {
            closeEditNoteModal();
        }
        if (event.target === document.getElementById('deleteNoteModal')) {
            closeDeleteNoteModal();
        }
    });

    // Fermer les modales avec la touche Echap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (document.getElementById('editNoteModal').style.display === 'flex') {
                closeEditNoteModal();
            }
            if (document.getElementById('deleteNoteModal').style.display === 'flex') {
                closeDeleteNoteModal();
            }
            if (document.getElementById('notesModal').style.display === 'flex') {
                closeNotesModal();
            }
        }
    });
});

// Fonction pour ajouter une nouvelle note (garder l'existant)
async function addNewNote(clientId) {
    const noteInput = document.getElementById('newNoteInput');
    const noteContent = noteInput.value.trim();
    
    if (noteContent) {
        const formData = new FormData();
        formData.append('client_id', clientId);
        formData.append('note', noteContent);
        formData.append('ajouter_note', true);

        try {
            await fetch('karni.php', {
                method: 'POST',
                body: formData
            });
            location.reload();
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'ajout de la note');
        }
    } else {
        alert('Veuillez écrire une note avant de l\'ajouter');
    }
}

// Fonction pour fermer la modal des notes
function closeNotesModal() {
    document.getElementById('notesModal').style.display = 'none';
}

// Fonction pour afficher les notes (alias pour toggleNote)
function toggleNote(clientId, clientName) {
    showNotes(clientId, clientName);
}
    // Recherche en temps réel (optionnel)
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 2 || this.value.length === 0) {
                        this.form.submit();
                    }
                }, 500);
            });
        }
    });
</script>
<!-- Modal de confirmation pour vider le panier -->
<div class="modal-overlay" id="confirmViderModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation</h3>
            <span class="modal-close" onclick="closeConfirmModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Êtes-vous sûr de vouloir vider le panier de ce client ?</p>
            <div class="confirmation-buttons">
                <form method="post" id="confirmViderForm" style="display: inline;">
                    <input type="hidden" name="client_id" id="confirmClientId">
                    <button type="submit" name="vider_panier" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Oui, vider
                    </button>
                </form>
                <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Modal d'édition de note -->
<div class="modal-overlay" id="editNoteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Modifier la note</h3>
            <span class="modal-close" onclick="closeEditNoteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editNoteForm">
                <div class="form-group">
                    <textarea id="editNoteContent" class="note-textarea" placeholder="Contenu de la note..." rows="4" required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditNoteModal()">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression de note -->
<div class="modal-overlay" id="deleteNoteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation</h3>
            <span class="modal-close" onclick="closeDeleteNoteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p id="deleteNoteMessage">Êtes-vous sûr de vouloir supprimer cette note ?</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteNoteModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteNoteBtn">
                    <i class="fas fa-trash-alt"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>
</body>

</html>