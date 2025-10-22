<?php
// ---------------------------
// CONNEXION À LA BASE DE DONNÉES
// ---------------------------
$host   = 'localhost';
$dbname = 'produits_db';
$user   = 'root';
$pass   = '';
// Inclure PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
session_start();        
if(!isset($_SESSION['id_res'])){
    header('Location: index.php');
    exit();
}   
$id_res=$_SESSION['id_res'];

// Récupérer le terme de recherche s'il existe
$search_term = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = trim($_GET['search']);
}

require 'vendor/autoload.php';  // Autoload de Composer pour PHPMaile
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connexion à la base de données échouée : " . $e->getMessage());
}

// Envoi d'email avec la carte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') {
    $card_id = intval($_POST['card_id']);
    $recipient_email = filter_var($_POST['recipient_email'], FILTER_SANITIZE_EMAIL);

    // Récupérer les données de la carte
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE id = :id and id_res=$id_res");
    $stmt->execute(['id' => $card_id]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les items de la carte
    $stmt = $pdo->prepare("SELECT * FROM items WHERE card_id = :card_id and id_res=$id_res");
    $stmt->execute(['card_id' => $card_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Construire le contenu HTML de l'email
    $emailContent = "<h4 style='color: #4CAF50;'>bonjour, ".htmlspecialchars($card['title'])."</h4>";
$emailContent .= "<table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>";
$emailContent .= "<thead style='background-color: #f5f5f5;'>";
$emailContent .= "<tr><th style='padding: 10px; text-align: left; border-bottom: 2px solid #ddd;'>Produit</th><th style='padding: 10px; text-align: left; border-bottom: 2px solid #ddd;'>Quantité</th><th style='padding: 10px; text-align: left; border-bottom: 2px solid #ddd;'>Prix unitaire</th><th style='padding: 10px; text-align: left; border-bottom: 2px solid #ddd;'>Total</th></tr>";
$emailContent .= "</thead>";
$emailContent .= "<tbody>";

foreach ($items as $item) {
    $emailContent .= sprintf(
        "<tr>
            <td style='padding: 10px; border-bottom: 1px solid #f5f5f5;'>%s</td>
            <td style='padding: 10px; border-bottom: 1px solid #f5f5f5;'>%d</td>
            <td style='padding: 10px; border-bottom: 1px solid #f5f5f5;'>%.2f د.ت</td>
            <td style='padding: 10px; border-bottom: 1px solid #f5f5f5;'>%.2f د.ت</td>
        </tr>",
        htmlspecialchars($item['text']),
        $item['quantity'],
        $item['unit_price'],
        $item['price_total']
    );
}

$emailContent .= "</tbody>";
$emailContent .= "</table>";

$total = array_sum(array_column($items, 'price_total'));
$emailContent .= "<h3 style='margin-top: 20px; color: #333;'>Total : <span style='color: #e74c3c;'>".number_format($total, 2)."د.ت</span></h3>";

// Configuration de PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = '';
    $mail->Password = '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('', '');
    $mail->addAddress($recipient_email);
    $mail->isHTML(true);
    $mail->Subject = 'Détails de la card : '.$card['title'];
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

    $mail->send();
    header("Location: " . $_SERVER['REQUEST_URI']);
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}

    exit;
}

// -------------------------------
// TRAITEMENT DES ACTIONS (GET)
// -------------------------------

// Suppression d'un article
if (isset($_GET['delete_item'])) {
    $item_id = intval($_GET['delete_item']);
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id");
    $stmt->execute(['id' => $item_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Modification d'un article (texte, quantité et prix)
if (
    isset($_GET['edit_item']) &&
    isset($_GET['new_text']) &&
    isset($_GET['new_quantity']) &&
    isset($_GET['new_price'])
) {
    $item_id      = intval($_GET['edit_item']);
    $new_text     = trim($_GET['new_text']);
    $new_quantity = intval($_GET['new_quantity']);
    $new_price    = floatval($_GET['new_price']);

    if ($new_text !== '' && $new_quantity > 0 && $new_price > 0) {
        $new_total = $new_quantity * $new_price;
        $stmt = $pdo->prepare("UPDATE items 
                               SET text = :text, quantity = :quantity, unit_price = :price, price_total = :price_total 
                               WHERE id = :id");
        $stmt->execute([
            'text'        => $new_text,
            'quantity'    => $new_quantity,
            'price'       => $new_price,
            'price_total' => $new_total,
            'id'          => $item_id
        ]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Suppression d'une carte et de ses items associés
if (isset($_GET['delete_card'])) {
    $card_id = intval($_GET['delete_card']);
    // Supprimer les items liés à cette carte
    $stmt = $pdo->prepare("DELETE FROM items WHERE card_id = :card_id");
    $stmt->execute(['card_id' => $card_id]);
    // Supprimer la carte
    $stmt = $pdo->prepare("DELETE FROM cards WHERE id = :id");
    $stmt->execute(['id' => $card_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// -----------------------------
// TRAITEMENT DES FORMULAIRES (POST)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Ajout d'une nouvelle carte
        if ($_POST['action'] === 'add_card') {
            $card_title = trim($_POST['card_title']);
            if (!empty($card_title)) {
                $stmt = $pdo->prepare("INSERT INTO cards (title,id_res,created_at) VALUES (:title,$id_res, NOW())");
                $stmt->execute(['title' => $card_title]);
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        // Ajout d'un item
        elseif ($_POST['action'] === 'add_item') {
            $card_id   = intval($_POST['card_id']);
            $quantity  = intval($_POST['quantity']);
            $price     = floatval($_POST['price']);
            $item_text = trim($_POST['item_text']);
            $total_price = $quantity * $price;

            if ($card_id && $quantity && $price && !empty($item_text)) {
                $stmt = $pdo->prepare("INSERT INTO items (card_id, quantity, unit_price, price_total, text,id_res, created_at) 
                                     VALUES (:card_id, :quantity, :unit_price, :price_total, :text,$id_res, NOW())");
                $stmt->execute([
                    'card_id'     => $card_id,
                    'quantity'    => $quantity,
                    'unit_price'  => $price,
                    'price_total' => $total_price,
                    'text'        => $item_text
                ]);
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// ----------------------------------
// RÉCUPÉRATION DES DONNÉES
// ----------------------------------
// Récupérer les cartes avec recherche si applicable
if (!empty($search_term)) {
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE id_res = :id_res AND title LIKE :search_term ORDER BY created_at DESC");
    $stmt->execute(['id_res' => $id_res, 'search_term' => '%' . $search_term . '%']);
} else {
    $stmt = $pdo->prepare("SELECT * FROM cards WHERE id_res = :id_res ORDER BY created_at DESC");
    $stmt->execute(['id_res' => $id_res]);
}
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cards_items = [];
$cards_totals = [];
foreach ($cards as $card) {
    $stmt2 = $pdo->prepare("SELECT *, (quantity * unit_price) as price_total FROM items WHERE card_id = :card_id and id_res=$id_res ORDER BY created_at ASC");
    $stmt2->execute(['card_id' => $card['id']]);
    $cards_items[$card['id']] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcul du total de la carte
    $total = 0;
    foreach ($cards_items[$card['id']] as $item) {
        $total += $item['price_total'];
    }
    $cards_totals[$card['id']] = $total;
}

// -----------------------------
// GROUPER LES CARTES PAR LA PREMIÈRE LETTRE DU TITRE
// -----------------------------
$grouped_cards = [];
foreach ($cards as $card) {
    $firstLetter = strtoupper(substr($card['title'], 0, 1));
    if (!isset($grouped_cards[$firstLetter])) {
        $grouped_cards[$firstLetter] = [];
    }
    $grouped_cards[$firstLetter][] = $card;
}
ksort($grouped_cards);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Stocks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Importation de Font Awesome et Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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

        h1 {
            font-size: 1.8rem;
            color: var(--accent);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        h1 i {
            font-size: 1.6rem;
        }

        /* Animations */
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Notifications */
        .notification {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            margin: 1rem 0;
            border-radius: 8px;
            color: white;
            font-size: 0.9rem;
            opacity: 0;
            animation: fadeInOut 5s forwards;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .success {
            background-color: #4CAF50;
        }

        .error {
            background-color: #f44336;
        }

        .notification i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .notification .message {
            flex-grow: 1;
        }

        .notification button {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.1rem;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .notification button:hover {
            opacity: 0.7;
        }

        @keyframes fadeInOut {
            0% { opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { opacity: 0; }
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
        /* Styles pour les modales */
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

.modal-content {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalSlide 0.3s ease-out;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

@keyframes modalSlide {
    from { transform: translateY(-30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
    color: var(--primary);
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.modal-close {
    cursor: pointer;
    font-size: 1.5rem;
    color: #64748b;
    transition: color 0.3s;
    background: none;
    border: none;
    padding: 0;
}

.modal-close:hover {
    color: var(--accent);
}

.modal-body {
    padding: 0;
}

/* Formulaires dans les modales */
.form-group {
    margin-bottom: 1.2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text);
    font-size: 0.9rem;
}

.form-input {
    width: 100%;
    padding: 10px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: #fafafa;
}

.form-input:focus {
    border-color: var(--primary);
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

/* Contrôle de quantité dans modal */
.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-control .quantity-input {
    width: 80px;
    text-align: center;
    font-weight: 600;
}

/* Boutons des modales */
.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

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
    .modal-content {
        margin: 1rem;
        padding: 1.2rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 0.8rem;
    }
    
    .modal-actions {
        flex-direction: column;
    }
    
    .modal-actions .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .modal-content {
        padding: 1rem;
    }
    
    .modal-header h3 {
        font-size: 1.1rem;
    }
    
    .btn {
        padding: 8px 16px;
        font-size: 0.85rem;
    }
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

        /* Formulaire nouvelle carte */
        .new-card-form {
            margin: 1.5rem 0;
        }

        .input-group {
            display: flex;
            gap: 0.8rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .price-input {
            flex: 1;
            min-width: 200px;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
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
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
        }

        /* Cartes */
        .section-letter {
            font-size: 1.5rem;
            color: var(--accent);
            margin: 1.5rem 0 0.8rem;
            padding-left: 0.8rem;
            border-left: 3px solid var(--accent);
        }

        .cards-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.2rem;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.2rem;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid rgba(0,0,0,0.05);
            gap: 0.8rem;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }

        .card-actions {
            display: flex;
            gap: 0.4rem;
            flex-shrink: 0;
        }

        .quantity-btn {
            background: var(--primary);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .quantity-btn:hover {
            background: var(--secondary);
            transform: scale(1.05);
        }

        /* Formulaires */
        .item-form, .email-form {
            display: none;
            margin-top: 1rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.8rem;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .item-form.active, .email-form.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .quantity-input {
            width: 50px;
            padding: 6px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .item-text-input {
            flex: 1;
            padding: 8px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 0.85rem;
            background: #fafafa;
            transition: border-color 0.3s ease;
            min-width: 150px;
        }

        .item-text-input:focus {
            border-color: var(--primary);
            outline: none;
        }

        /* Liste des items */
        .items-list {
            list-style: none;
            margin: 1rem 0;
        }

        .item {
            padding: 0.8rem;
            background: rgba(241, 245, 249, 0.5);
            border-radius: 6px;
            margin-bottom: 0.6rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
            gap: 0.8rem;
        }

        .item:hover {
            transform: translateX(3px);
            background: #fff;
        }

        .item-info {
            flex: 1;
        }

        .item-title {
            font-weight: 600;
            margin-bottom: 0.2rem;
            font-size: 0.9rem;
        }

        .item-details {
            display: flex;
            gap: 0.8rem;
            color: #64748b;
            font-size: 0.8rem;
            flex-wrap: wrap;
        }

        .item-actions {
            display: flex;
            gap: 0.4rem;
            flex-shrink: 0;
        }

        .action-btn {
            background: none;
            border: none;
            padding: 4px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .action-btn.edit {
            color: var(--primary);
        }

        .action-btn.delete {
            color: var(--accent);
        }

        .action-btn:hover {
            background: rgba(0,0,0,0.05);
        }

        /* Total de la carte */
        .card-total {
            margin-top: 1rem;
            padding-top: 0.8rem;
            border-top: 2px solid rgba(0,0,0,0.05);
            font-size: 1rem;
            font-weight: 700;
            color: var(--success);
            display: flex;
            justify-content: space-between;
        }

        /* État vide */
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

            .cards-container {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 1.5rem;
            }

            .card {
                padding: 1.5rem;
            }

            .card-title {
                font-size: 1.3rem;
            }

            .quantity-btn {
                width: 36px;
                height: 36px;
                font-size: 0.9rem;
            }

            .item-form, .email-form {
                padding: 1rem;
            }

            .quantity-input {
                width: 60px;
                padding: 8px;
            }

            .item-text-input {
                padding: 10px;
                font-size: 0.9rem;
            }

            .item {
                padding: 1rem;
            }

            .item-title {
                font-size: 1rem;
            }
        }

        @media (min-width: 1024px) {
            .cards-container {
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
            
            .input-group {
                flex-direction: column;
            }
            
            .price-input {
                min-width: 100%;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.8rem;
            }
            
            .card-actions {
                align-self: flex-end;
            }
            
            .item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.6rem;
            }
            
            .item-actions {
                align-self: flex-end;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 0.8rem;
                padding-bottom: 70px;
            }
            
            .card {
                padding: 1rem;
            }
            
            .card-title {
                font-size: 1.1rem;
            }
            
            .section-letter {
                font-size: 1.3rem;
            }
            
            .item-form, .email-form {
                padding: 0.6rem;
            }
            
            .quantity-control {
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
        
        
        <a href="karni.php" class="nav-icon" title="Utilisateurs"> 
            <i class="fas fa-users"></i>
        </a>
        
        <a href="note.php" class="nav-icon" title="Note">
            <i class="fas fa-edit"></i>
        </a>
        <a href="factures.php" class="nav-icon" title="facteur" >
            <i class="fas fa-file-invoice"></i>
        </a>
        <a href="a4ya.php" class="nav-icon active" title="Stock">
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
    <h1><i class="fas fa-boxes"></i> SBASA</h1>
    
    <!-- Zone de recherche -->
    <div class="search-container">
        <form method="GET" action="" class="search-box">
            <input type="text" 
                   name="search" 
                   class="search-input" 
                   placeholder="Rechercher une catégorie par nom..." 
                   value="<?= htmlspecialchars($search_term) ?>"
                   autocomplete="off">
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
                Rechercher
            </button>
            <?php if (!empty($search_term)): ?>
                <a href="a4ya.php" class="clear-search">
                    <i class="fas fa-times"></i>
                    Effacer
                </a>
            <?php endif; ?>
        </form>
        
        <?php if (!empty($search_term)): ?>
            <div class="search-results-info">
                <i class="fas fa-info-circle"></i>
                Résultats pour : "<strong><?= htmlspecialchars($search_term) ?></strong>"
                (<?= count($cards) ?> catégorie(s) trouvée(s))
            </div>
        <?php endif; ?>
    </div>

    <!-- Notifications -->
    <?php if (isset($_GET['success'])): ?>
    <div class="notification success" id="successNotification">
        <i class="fas fa-check-circle"></i>
        <div class="message">Email envoyé avec succès !</div>
        <button onclick="this.parentElement.style.display='none';">×</button>
    </div>
    <script>
        setTimeout(function() {
            const notification = document.getElementById('successNotification');
            if (notification) notification.style.display = 'none';
        }, 5000);
    </script>
    <?php elseif (isset($_GET['error'])): ?>
    <div class="notification error" id="errorNotification">
        <i class="fas fa-times-circle"></i>
        <div class="message">Erreur lors de l'envoi de l'email</div>
        <button onclick="this.parentElement.style.display='none';">×</button>
    </div>
    <script>
        setTimeout(function() {
            const notification = document.getElementById('errorNotification');
            if (notification) notification.style.display = 'none';
        }, 5000);
    </script>
    <?php endif; ?>
    
    <!-- Formulaire nouvelle carte -->
    <form method="POST" class="new-card-form">
        <input type="hidden" name="action" value="add_card">
        <div class="input-group">
            <input type="text" name="card_title" class="price-input" placeholder="Nom de client" required>
            <button type="submit" class="btn-submit">
                <i class="fas fa-plus"></i> Créer
            </button>
        </div>
    </form>

    <?php if (empty($cards) && !empty($search_term)): ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>Aucune catégorie trouvée</h3>
            <p>Aucune catégorie ne correspond à votre recherche "<?= htmlspecialchars($search_term) ?>"</p>
            <a href="a4ya.php" class="btn-submit" style="margin-top: 1rem; text-decoration: none;">
                <i class="fas fa-arrow-left"></i>
                Voir toutes les catégories
            </a>
        </div>
    <?php else: ?>
        <!-- Affichage des cartes groupées par lettre -->
        <?php foreach ($grouped_cards as $letter => $cardsForLetter): ?>
        <div class="card-group">
            <div class="section-letter"><?= $letter ?></div>
            <div class="cards-container">
                <?php foreach ($cardsForLetter as $card): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user"></i>
                            <?= htmlspecialchars($card['title']) ?>
                        </h3>
                        <div class="card-actions">
                            <a href="generate_pdf.php?card_id=<?= $card['id'] ?>" class="quantity-btn" title="Télécharger en PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>                                                
                            <button onclick="toggleEmailForm(<?= $card['id'] ?>)" class="quantity-btn" title="Envoyer par email">
                                <i class="fas fa-envelope"></i>
                            </button>
                            <button onclick="toggleItemForm(<?= $card['id'] ?>)" class="quantity-btn" title="Ajouter un article">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button onclick="deleteCard(<?= $card['id'] ?>)" class="quantity-btn" title="Supprimer">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Formulaire email -->
                    <form method="POST" class="email-form" id="email-form-<?= $card['id'] ?>">
                        <input type="hidden" name="action" value="send_email">
                        <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                        <div class="input-group">
                            <input type="email" name="recipient_email" class="price-input" 
                                   placeholder="Entrez l'adresse email" required>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane"></i> Envoyer
                            </button>
                        </div>
                    </form>

                    <!-- Formulaire article -->
                    <form method="POST" class="item-form" id="form-<?= $card['id'] ?>">
                        <input type="hidden" name="action" value="add_item">
                        <input type="hidden" name="card_id" value="<?= $card['id'] ?>">
                        
                        <div class="input-group">
                            <div class="quantity-control">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(<?= $card['id'] ?>, -1)">-</button>
                                <input type="number" name="quantity" class="quantity-input" value="1" min="1" required>
                                <button type="button" class="quantity-btn" onclick="changeQuantity(<?= $card['id'] ?>, 1)">+</button>
                            </div>
                            <input type="number" step="0.01" name="price" class="price-input" placeholder="Prix" required>
                        </div>
                        
                        <input type="text" name="item_text" class="item-text-input" placeholder="Nom de l'article" required>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Enregistrer
                        </button>
                    </form>

                    <!-- Liste des articles -->
                    <ul class="items-list">
                        <?php foreach ($cards_items[$card['id']] as $item): ?>
                        <li class="item">
                            <div class="item-info">
                                <div class="item-title"><?= htmlspecialchars($item['text']) ?></div>
                                <div class="item-details">
                                    <span><?= $item['quantity'] ?> x <?= number_format($item['unit_price'], 2) ?> د.ت</span>
                                    <span>Total: <?= number_format($item['price_total'], 2) ?> د.ت</span>
                                </div>
                            </div>
                            <div class="item-actions">
    <button class="action-btn edit" onclick='editItem(<?= $item["id"] ?>, <?= $item["quantity"] ?>, <?= $item["unit_price"] ?>, "<?= addslashes($item["text"]) ?>")'>
        <i class="fas fa-edit"></i>
    </button>
    <button class="action-btn delete" onclick="deleteItem(<?= $item['id'] ?>)">
        <i class="fas fa-trash"></i>
    </button>
</div>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <!-- Total de la carte -->
                    <div class="card-total">
                        <span>Total:</span>
                        <span><?= number_format($cards_totals[$card['id']], 2) ?> د.ت</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    // Fonctions pour la gestion des formulaires et actions
   // Variables globales
let currentEditItemId = null;
let currentDeleteItemId = null;

// Fonctions pour les modales
function editItem(itemId, currentQuantity, currentPrice, currentText) {
    currentEditItemId = itemId;
    
    // Remplir le formulaire avec les valeurs actuelles
    document.getElementById('editText').value = currentText;
    document.getElementById('editQuantity').value = currentQuantity;
    document.getElementById('editPrice').value = currentPrice;
    
    // Afficher la modal
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    currentEditItemId = null;
}

function changeEditQuantity(delta) {
    const input = document.getElementById('editQuantity');
    let value = parseInt(input.value) + delta;
    input.value = value < 1 ? 1 : value;
}

function deleteItem(itemId) {
    currentDeleteItemId = itemId;
    
    // Récupérer le nom de l'article pour l'afficher dans le message
    const itemElement = document.querySelector(`[onclick="deleteItem(${itemId})"]`).closest('.item');
    const itemName = itemElement.querySelector('.item-title').textContent;
    
    document.getElementById('deleteMessage').textContent = 
        `Êtes-vous sûr de vouloir supprimer l'article "${itemName}" ?`;
    
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    currentDeleteItemId = null;
}

// Événements
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire d'édition
    document.getElementById('editForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!currentEditItemId) return;
        
        const newText = document.getElementById('editText').value.trim();
        const newQuantity = parseInt(document.getElementById('editQuantity').value);
        const newPrice = parseFloat(document.getElementById('editPrice').value);
        
        if (newText && newQuantity > 0 && newPrice > 0) {
            // Redirection vers l'URL de modification
            window.location.href = "?edit_item=" + currentEditItemId 
                + "&new_quantity=" + encodeURIComponent(newQuantity)
                + "&new_price=" + encodeURIComponent(newPrice)
                + "&new_text=" + encodeURIComponent(newText);
        }
    });
    
    // Gestion de la confirmation de suppression
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (currentDeleteItemId) {
            window.location.href = "?delete_item=" + currentDeleteItemId;
        }
    });
    
    // Fermer les modales en cliquant à l'extérieur
    document.addEventListener('click', function(event) {
        if (event.target === document.getElementById('editModal')) {
            closeEditModal();
        }
        if (event.target === document.getElementById('deleteModal')) {
            closeDeleteModal();
        }
    });
    
    // Fermer les modales avec la touche Echap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            if (document.getElementById('editModal').style.display === 'flex') {
                closeEditModal();
            }
            if (document.getElementById('deleteModal').style.display === 'flex') {
                closeDeleteModal();
            }
        }
    });
    
    // Recherche en temps réel
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

// Fonctions existantes pour la gestion des formulaires
function toggleItemForm(cardId) {
    const form = document.getElementById(`form-${cardId}`);
    form.classList.toggle('active');
}

function toggleEmailForm(cardId) {
    const form = document.getElementById(`email-form-${cardId}`);
    form.classList.toggle('active');
}

function changeQuantity(cardId, delta) {
    const input = document.querySelector(`#form-${cardId} .quantity-input`);
    let value = parseInt(input.value) + delta;
    input.value = value < 1 ? 1 : value;
}

function deleteCard(cardId) {
    if (confirm("Supprimer cette catégorie et tous ses articles ?")) {
        window.location.href = "?delete_card=" + cardId;
    }
}
</script>
<!-- Modal d'édition d'article -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Modifier l'article</h3>
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="editForm">
                <div class="form-group">
                    <label>Nom de l'article</label>
                    <input type="text" id="editText" class="form-input" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Quantité</label>
                        <div class="quantity-control">
                            <button type="button" class="quantity-btn" onclick="changeEditQuantity(-1)">-</button>
                            <input type="number" id="editQuantity" class="quantity-input" min="1" required>
                            <button type="button" class="quantity-btn" onclick="changeEditQuantity(1)">+</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Prix unitaire (د.ت)</label>
                        <input type="number" step="0.01" id="editPrice" class="form-input" min="0" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
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

<!-- Modal de confirmation de suppression -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation</h3>
            <span class="modal-close" onclick="closeDeleteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p id="deleteMessage">Êtes-vous sûr de vouloir supprimer cet article ?</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash-alt"></i> Supprimer
                </button>
            </div>
        </div>
    </div>
</div>
</body>
</html>
