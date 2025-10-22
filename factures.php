<?php
// ---------------------------
// CONNEXION À LA BASE DE DONNÉES
// ---------------------------
$host   = 'localhost';
$dbname = 'produits_db';
$user   = 'root';
$pass   = '';

session_start();        
if(!isset($_SESSION['id_res'])){
    header('Location: index.php');
    exit();
}   
$id_res = $_SESSION['id_res'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connexion à la base de données échouée : " . $e->getMessage());
}

// Déterminer le type d'affichage
$display_type = $_GET['display'] ?? 'week'; // week, month, all
$view_mode = $_GET['view'] ?? 'grid'; // grid, list
$sort_by = $_GET['sort'] ?? 'newest'; // newest, oldest, name

// Récupérer le terme de recherche s'il existe
$search_term = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = trim($_GET['search']);
}

// -------------------------------
// TRAITEMENT DES ACTIONS
// -------------------------------

// Ajout d'une nouvelle facture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_facture') {
    $titre = trim($_POST['titre']);
    
    // Gestion de l'upload d'image
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/factures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '_' . time() . '.' . $file_extension;
        $image_path = $upload_dir . $file_name;
        
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }
    
    if (!empty($titre)) {
        $date_creation = date('Y-m-d');
        $heure_creation = date('H:i:s');
        
        $stmt = $pdo->prepare("INSERT INTO factures (titre, image, date_creation, heure_creation, id_res) 
                              VALUES (:titre, :image, :date_creation, :heure_creation, :id_res)");
        $stmt->execute([
            'titre' => $titre,
            'image' => $image_path,
            'date_creation' => $date_creation,
            'heure_creation' => $heure_creation,
            'id_res' => $id_res
        ]);
        
        // Message de succès
        $_SESSION['success_message'] = 'Facture ajoutée avec succès!';
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Suppression d'une facture
if (isset($_GET['delete_facture'])) {
    $facture_id = intval($_GET['delete_facture']);
    
    // Récupérer le chemin de l'image pour la supprimer du serveur
    $stmt = $pdo->prepare("SELECT image FROM factures WHERE id = :id AND id_res = :id_res");
    $stmt->execute(['id' => $facture_id, 'id_res' => $id_res]);
    $facture = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($facture && $facture['image'] && file_exists($facture['image'])) {
        unlink($facture['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM factures WHERE id = :id AND id_res = :id_res");
    $stmt->execute(['id' => $facture_id, 'id_res' => $id_res]);
    
    $_SESSION['success_message'] = 'Facture supprimée avec succès!';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------------------
// RÉCUPÉRATION DES DONNÉES
// ----------------------------------

// Compter le total des factures
$stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM factures WHERE id_res = :id_res");
$stmt_count->execute(['id_res' => $id_res]);
$total_factures = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

// Compter les factures de la semaine
$stmt_week = $pdo->prepare("SELECT COUNT(*) as week_count FROM factures 
                           WHERE id_res = :id_res 
                           AND date_creation >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stmt_week->execute(['id_res' => $id_res]);
$week_factures = $stmt_week->fetch(PDO::FETCH_ASSOC)['week_count'];

// Compter les factures du mois
$stmt_month = $pdo->prepare("SELECT COUNT(*) as month_count FROM factures 
                            WHERE id_res = :id_res 
                            AND date_creation >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$stmt_month->execute(['id_res' => $id_res]);
$month_factures = $stmt_month->fetch(PDO::FETCH_ASSOC)['month_count'];

// Récupérer les factures selon le type d'affichage
$where_conditions = ["id_res = :id_res"];
$params = ['id_res' => $id_res];

if (!empty($search_term)) {
    $where_conditions[] = "(titre LIKE :search_term OR date_creation LIKE :search_term)";
    $params['search_term'] = '%' . $search_term . '%';
} else {
    switch ($display_type) {
        case 'week':
            $where_conditions[] = "date_creation >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "date_creation >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'all':
            // Pas de condition de date
            break;
    }
}

$where_clause = implode(" AND ", $where_conditions);
$sql = "SELECT * FROM factures WHERE $where_clause ORDER BY ";

// Ajouter le tri
switch ($sort_by) {
    case 'oldest':
        $sql .= "date_creation ASC, heure_creation ASC";
        break;
    case 'name':
        $sql .= "titre ASC";
        break;
    case 'newest':
    default:
        $sql .= "date_creation DESC, heure_creation DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$factures = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Message de succès
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Factures - SBASA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #4f46e5;
            --accent: #f43f5e;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --radius: 1rem;
            --radius-sm: 0.75rem;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 1rem;
            padding-bottom: 80px;
            line-height: 1.6;
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
            color: var(--primary);
            text-decoration: none;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .nav-icon:hover {
            background: rgba(99, 102, 241, 0.1);
            transform: scale(1.1);
        }

        .nav-icon.active {
            color: white;
            background: var(--primary);
        }

        .nav-icon i {
            font-size: 1.2rem;
            margin-bottom: 3px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: var(--radius);
            color: white;
            font-weight: 500;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-lg);
            transform: translateX(150%);
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: linear-gradient(135deg, var(--success), #059669);
        }

        /* Header */
        .page-header {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            width: 100%;
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin: 0;
        }

        .page-title i {
            font-size: 2rem;
            color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
            padding: 12px;
            border-radius: 12px;
        }

        /* Filtres rapides */
        .quick-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 12px 24px;
            border: 2px solid var(--border);
            background: var(--surface);
            color: var(--text-secondary);
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: var(--shadow-sm);
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-1px);
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        /* Statistiques */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-card.active {
            background: var(--primary);
            color: white;
        }

        .stat-card.active .stat-number,
        .stat-card.active .stat-label,
        .stat-card.active .stat-icon {
            color: white;
        }

        .stat-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-text {
            flex: 1;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-icon {
            font-size: 2rem;
            color: var(--primary);
            opacity: 0.7;
        }

        /* Section de recherche */
        .search-section {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
        }

        .search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .search-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-box {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input-container {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 16px 12px 40px;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--background);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .search-btn, .clear-search {
            padding: 12px 20px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .search-btn {
            background: var(--primary);
            color: white;
        }

        .search-btn:hover {
            background: var(--secondary);
            transform: translateY(-1px);
        }

        .clear-search {
            background: var(--background);
            color: var(--text-secondary);
            border: 2px solid var(--border);
        }

        .clear-search:hover {
            background: var(--surface);
            border-color: var(--text-secondary);
        }

        .search-results-info {
            margin-top: 1rem;
            padding: 0.75rem;
            background: rgba(99, 102, 241, 0.08);
            border-radius: var(--radius-sm);
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            border-left: 3px solid var(--primary);
        }

        /* Formulaire nouvelle facture */
        .form-section {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .form-header i {
            font-size: 1.3rem;
            color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
            padding: 8px;
            border-radius: 8px;
        }

        .form-header h2 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--background);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .file-upload-container {
            margin-bottom: 1rem;
        }

        .file-upload-label {
            display: block;
            width: 100%;
            padding: 2rem;
            border: 2px dashed var(--border);
            border-radius: var(--radius-sm);
            background: var(--background);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .file-upload-label:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .file-upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
        }

        .file-upload-content i {
            font-size: 2rem;
            color: var(--primary);
        }

        .file-upload-text {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .file-upload-hint {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .file-preview {
            margin-top: 1rem;
            display: none;
        }

        .file-preview.active {
            display: block;
        }

        .file-preview-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--background);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }

        .file-preview-image {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid var(--border);
        }

        .file-preview-info {
            flex: 1;
        }

        .file-preview-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .file-preview-size {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .file-preview-remove {
            background: none;
            border: none;
            color: var(--accent);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .file-preview-remove:hover {
            background: rgba(244, 63, 94, 0.1);
        }

        .btn-submit {
            background: var(--accent);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background: #e11d48;
            transform: translateY(-1px);
        }

        /* Section des factures */
        .factures-section {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-title-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
        }

        .factures-count {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .view-options {
            display: flex;
            background: var(--background);
            border-radius: var(--radius-sm);
            padding: 0.25rem;
            border: 1px solid var(--border);
        }

        .view-option {
            padding: 0.5rem 0.75rem;
            border: none;
            background: none;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .view-option.active {
            background: var(--primary);
            color: white;
        }

        .view-option:hover:not(.active) {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }

        .sort-options {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sort-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            background: var(--surface);
            color: var(--text-primary);
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sort-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        /* Conteneur des factures */
        .factures-container {
            transition: all 0.3s ease;
        }

        .factures-container.view-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .factures-container.view-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .factures-container.view-list .facture-card {
            display: flex;
            align-items: center;
            padding: 1.25rem;
        }

        .factures-container.view-list .facture-image-container {
            width: 80px;
            height: 80px;
            margin-right: 1.5rem;
            margin-bottom: 0;
        }

        .factures-container.view-list .facture-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .factures-container.view-list .facture-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 0;
        }

        .factures-container.view-list .facture-actions {
            margin-top: 0;
            border-top: none;
            padding-top: 0;
        }

        /* Carte de facture */
        .facture-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.25rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            position: relative;
        }

        .facture-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .facture-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .facture-badge {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .facture-menu {
            position: relative;
        }

        .menu-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .menu-btn:hover {
            background: var(--background);
            color: var(--text-primary);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            padding: 0.5rem;
            min-width: 160px;
            z-index: 10;
            display: none;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-menu button {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: none;
            background: none;
            text-align: left;
            cursor: pointer;
            border-radius: 4px;
            color: var(--text-primary);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .dropdown-menu button:hover {
            background: var(--background);
        }

        .dropdown-menu .delete-option {
            color: var(--accent);
        }

        .dropdown-menu .delete-option:hover {
            background: rgba(244, 63, 94, 0.1);
        }

        /* Conteneur d'image */
        .facture-image-container {
            width: 100%;
            height: 160px;
            border-radius: var(--radius-sm);
            margin-bottom: 1rem;
            background: var(--background);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            cursor: pointer;
            overflow: hidden;
            position: relative;
            border: 1px solid var(--border);
        }

        .facture-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: var(--radius-sm);
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
            color: white;
            font-size: 1.5rem;
        }

        .facture-image-container:hover .image-overlay {
            opacity: 1;
        }

        .no-image {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            opacity: 0.6;
        }

        .no-image i {
            font-size: 2rem;
        }

        .no-image span {
            font-size: 0.8rem;
        }

        /* Contenu de la carte */
        .facture-content {
            padding: 0.5rem 0;
        }

        .facture-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .facture-meta {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .meta-item i {
            width: 14px;
            color: var(--primary);
        }

        .facture-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        .action-btn {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .view-btn {
            background: var(--primary);
            color: white;
        }

        .view-btn:hover {
            background: var(--secondary);
        }

        .delete-btn {
            background: rgba(244, 63, 94, 0.1);
            color: var(--accent);
            border: 1px solid rgba(244, 63, 94, 0.2);
        }

        .delete-btn:hover {
            background: var(--accent);
            color: white;
        }

        /* État vide */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
            grid-column: 1 / -1;
        }

        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--border);
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .empty-state p {
            font-size: 0.95rem;
            max-width: 400px;
            margin: 0 auto 1.5rem;
            line-height: 1.6;
        }

        .btn-primary, .btn-secondary {
            padding: 10px 20px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
        }

        .btn-secondary {
            background: var(--background);
            color: var(--text-secondary);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--surface);
            border-color: var(--text-secondary);
        }

        /* Modal */
        .image-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 3000;
            padding: 1rem;
        }

        .image-modal-content {
            background: white;
            border-radius: var(--radius);
            max-width: 90vw;
            max-height: 90vh;
            position: relative;
        }

        .image-modal-img {
            width: 100%;
            height: auto;
            max-height: 80vh;
            object-fit: contain;
            border-radius: var(--radius);
        }

        .image-modal-actions {
            position: absolute;
            bottom: 1rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.75rem;
            border-radius: var(--radius-sm);
        }

        .image-modal-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 0.85rem;
        }

        .image-modal-btn:hover {
            background: var(--secondary);
        }

        .image-modal-close {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .image-modal-close:hover {
            background: white;
            transform: scale(1.1);
        }

        /* Modal de confirmation */
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
            border-radius: var(--radius);
            width: 100%;
            max-width: 400px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border);
        }

        .modal-header h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-primary);
            font-size: 1.1rem;
        }

        .modal-close {
            cursor: pointer;
            font-size: 1.3rem;
            color: var(--text-secondary);
            background: none;
            border: none;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--accent);
        }

        .modal-body p {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .confirmation-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .btn-danger {
            background: var(--accent);
            color: white;
        }

        .btn-danger:hover {
            background: #e11d48;
        }

        .btn-secondary {
            background: var(--background);
            color: var(--text-secondary);
            border: 2px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--surface);
            border-color: var(--text-secondary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .factures-container.view-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .search-box {
                flex-direction: column;
            }
            
            .search-input-container {
                min-width: 100%;
            }
            
            .search-btn, .clear-search {
                width: 100%;
                justify-content: center;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .section-title-container {
                justify-content: space-between;
            }
            
            .sort-options {
                justify-content: flex-end;
            }
        }

        @media (max-width: 480px) {
            .factures-container.view-grid {
                grid-template-columns: 1fr;
            }
            
            .factures-container.view-list .facture-card {
                flex-direction: column;
                align-items: stretch;
            }
            
            .factures-container.view-list .facture-image-container {
                width: 100%;
                height: 120px;
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .factures-container.view-list .facture-content {
                flex-direction: column;
                align-items: stretch;
            }
            
            .factures-container.view-list .facture-meta {
                justify-content: space-between;
            }
            
            .quick-filters {
                justify-content: center;
            }
            
            .filter-btn {
                flex: 1;
                min-width: 120px;
                justify-content: center;
            }
        }

        @media (min-width: 768px) {
            body {
                padding: 2rem;
                padding-right: 80px;
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
        }
    </style>
</head>
<body>

<!-- Notification -->
<?php if ($success_message): ?>
    <div class="notification success" id="successNotification">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($success_message) ?></span>
    </div>
<?php endif; ?>

<!-- Barre de navigation responsive -->
<div class="sidebar">
        <a href="calcul.html" class="nav-icon" title="Calculatrice" onclick="showCalculator(event)">
            <i class="fas fa-calculator"></i>
        </a>
        <a href="prod.php" class="nav-icon" title="Panier">
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
        <a href="factures.php" class="nav-icon active" title="facteur" >
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
    <!-- Header -->
    <div class="page-header">
        <div class="page-title">
            <i class="fas fa-file-invoice-dollar"></i>
            <h1>Gestion des Factures</h1>
        </div>
    </div>
    
    <!-- Filtres rapides -->
    <div class="quick-filters">
        <button class="filter-btn <?= $display_type === 'week' ? 'active' : '' ?>" onclick="changeDisplay('week')">
            <i class="fas fa-calendar-week"></i>
            Cette Semaine
        </button>
        <button class="filter-btn <?= $display_type === 'month' ? 'active' : '' ?>" onclick="changeDisplay('month')">
            <i class="fas fa-calendar-alt"></i>
            Ce Mois
        </button>
        <button class="filter-btn <?= $display_type === 'all' ? 'active' : '' ?>" onclick="changeDisplay('all')">
            <i class="fas fa-chart-bar"></i>
            Toutes
        </button>
    </div>
    
    <!-- Section des statistiques -->
    <div class="stats-container">
        <div class="stat-card <?= $display_type === 'week' ? 'active' : '' ?>" onclick="changeDisplay('week')">
            <div class="stat-content">
                <div class="stat-text">
                    <div class="stat-number"><?= $week_factures ?></div>
                    <div class="stat-label">Cette Semaine</div>
                </div>
                <i class="fas fa-calendar-week stat-icon"></i>
            </div>
        </div>
        <div class="stat-card <?= $display_type === 'month' ? 'active' : '' ?>" onclick="changeDisplay('month')">
            <div class="stat-content">
                <div class="stat-text">
                    <div class="stat-number"><?= $month_factures ?></div>
                    <div class="stat-label">Ce Mois</div>
                </div>
                <i class="fas fa-calendar-alt stat-icon"></i>
            </div>
        </div>
        <div class="stat-card <?= $display_type === 'all' ? 'active' : '' ?>" onclick="changeDisplay('all')">
            <div class="stat-content">
                <div class="stat-text">
                    <div class="stat-number"><?= $total_factures ?></div>
                    <div class="stat-label">Total Factures</div>
                </div>
                <i class="fas fa-chart-bar stat-icon"></i>
            </div>
        </div>
    </div>
    
    <!-- Section de recherche -->
    <div class="search-section">
        <div class="search-header">
            <div class="search-title">
                <i class="fas fa-search"></i>
                Rechercher des factures
            </div>
        </div>
        <form method="GET" action="" class="search-box">
            <input type="hidden" name="display" value="<?= $display_type ?>">
            <input type="hidden" name="view" value="<?= $view_mode ?>">
            <input type="hidden" name="sort" value="<?= $sort_by ?>">
            <div class="search-input-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Rechercher par titre, date..." 
                       value="<?= htmlspecialchars($search_term) ?>"
                       autocomplete="off">
            </div>
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
                Rechercher
            </button>
            <?php if (!empty($search_term) || $display_type !== 'week'): ?>
                <a href="factures.php?view=<?= $view_mode ?>&sort=<?= $sort_by ?>" class="clear-search">
                    <i class="fas fa-times"></i>
                    Effacer
                </a>
            <?php endif; ?>
        </form>
        
        <?php if (!empty($search_term)): ?>
            <div class="search-results-info">
                <i class="fas fa-info-circle"></i>
                Résultats pour : "<strong><?= htmlspecialchars($search_term) ?></strong>"
                (<?= count($factures) ?> facture(s) trouvée(s))
            </div>
        <?php elseif ($display_type !== 'week'): ?>
            <div class="search-results-info">
                <i class="fas fa-filter"></i>
                Filtre actif : 
                <strong>
                    <?= $display_type === 'month' ? 'Ce mois' : ($display_type === 'all' ? 'Toutes les factures' : 'Cette semaine') ?>
                </strong>
                (<?= count($factures) ?> facture(s))
            </div>
        <?php endif; ?>
    </div>

    <!-- Formulaire nouvelle facture -->
    <div class="form-section">
        <div class="form-header">
            <i class="fas fa-plus-circle"></i>
            <h2>Nouvelle Facture</h2>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_facture">
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Titre de la facture *</label>
                    <input type="text" name="titre" class="form-input" placeholder="Entrez le titre de la facture" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Image de la facture</label>
                    <div class="file-upload-container">
                        <label class="file-upload-label">
                            <div class="file-upload-content">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span class="file-upload-text">Choisir une image</span>
                                <span class="file-upload-hint">PNG, JPG, JPEG jusqu'à 5MB</span>
                            </div>
                            <input type="file" name="image" class="form-file" accept="image/*" hidden>
                        </label>
                        <div class="file-preview" id="filePreview"></div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-plus"></i> Ajouter Facture
            </button>
        </form>
    </div>

    <!-- Section des factures -->
    <div class="factures-section">
        <div class="section-header">
            <div class="section-title-container">
                <h2 class="section-title">
                    <i class="fas fa-receipt"></i>
                    Mes Factures
                    <span class="factures-count">(<?= count($factures) ?>)</span>
                </h2>
                <div class="view-options">
                    <button class="view-option <?= $view_mode === 'grid' ? 'active' : '' ?>" data-view="grid">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-option <?= $view_mode === 'list' ? 'active' : '' ?>" data-view="list">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
            
            <div class="sort-options">
                <select class="sort-select" onchange="changeSort(this.value)">
                    <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Plus récentes</option>
                    <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>>Plus anciennes</option>
                    <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Par nom</option>
                </select>
            </div>
        </div>

        <div class="factures-container view-<?= $view_mode ?>">
            <?php if (empty($factures)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3>Aucune facture trouvée</h3>
                    <p><?= empty($search_term) ? 'Commencez par ajouter votre première facture !' : 'Aucune facture ne correspond à votre recherche' ?></p>
                    <?php if (empty($search_term)): ?>
                        <button class="btn-primary" onclick="document.querySelector('.form-section').scrollIntoView({behavior: 'smooth'})">
                            <i class="fas fa-plus"></i> Ajouter une facture
                        </button>
                    <?php else: ?>
                        <button class="btn-secondary" onclick="window.location.href='factures.php?view=<?= $view_mode ?>&sort=<?= $sort_by ?>'">
                            <i class="fas fa-times"></i> Effacer la recherche
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($factures as $index => $facture): ?>
                    <div class="facture-card">
                        <div class="facture-card-header">
                            <div class="facture-badge">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <div class="facture-menu">
                                <button class="menu-btn" onclick="toggleMenu(this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <?php if ($facture['image'] && file_exists($facture['image'])): ?>
                                        <button onclick="openImageModal('<?= htmlspecialchars($facture['image']) ?>', '<?= htmlspecialchars($facture['titre']) ?>')">
                                            <i class="fas fa-eye"></i> Voir l'image
                                        </button>
                                        <button onclick="downloadFactureImage('<?= htmlspecialchars($facture['image']) ?>', '<?= htmlspecialchars($facture['titre']) ?>')">
                                            <i class="fas fa-download"></i> Télécharger
                                        </button>
                                    <?php endif; ?>
                                    <button class="delete-option" onclick="confirmDelete(<?= $facture['id'] ?>, '<?= addslashes($facture['titre']) ?>')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="facture-image-container" onclick="openImageModal('<?= htmlspecialchars($facture['image']) ?>', '<?= htmlspecialchars($facture['titre']) ?>')">
                            <?php if ($facture['image'] && file_exists($facture['image'])): ?>
                                <img src="<?= htmlspecialchars($facture['image']) ?>" 
                                     alt="Facture <?= htmlspecialchars($facture['titre']) ?>" 
                                     class="facture-image">
                                <div class="image-overlay">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-file-invoice"></i>
                                    <span>Aucune image</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="facture-content">
                            <h3 class="facture-title" title="<?= htmlspecialchars($facture['titre']) ?>">
                                <?= htmlspecialchars($facture['titre']) ?>
                            </h3>
                            
                            <div class="facture-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    <span><?= date('d/m/Y', strtotime($facture['date_creation'])) ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?= date('H:i', strtotime($facture['heure_creation'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="facture-actions">
                                <?php if ($facture['image'] && file_exists($facture['image'])): ?>
                                    <button class="action-btn view-btn" onclick="openImageModal('<?= htmlspecialchars($facture['image']) ?>', '<?= htmlspecialchars($facture['titre']) ?>')">
                                        <i class="fas fa-eye"></i> Voir
                                    </button>
                                <?php endif; ?>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?= $facture['id'] ?>, '<?= addslashes($facture['titre']) ?>')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de visualisation d'image -->
<div class="image-modal-overlay" id="imageModal">
    <div class="image-modal-content">
        <button class="image-modal-close" onclick="closeImageModal()">
            <i class="fas fa-times"></i>
        </button>
        <img id="modalImage" class="image-modal-img" src="" alt="">
        <div class="image-modal-actions">
            <button class="image-modal-btn" onclick="downloadImage()">
                <i class="fas fa-download"></i> Télécharger
            </button>
            <button class="image-modal-btn" onclick="printImage()">
                <i class="fas fa-print"></i> Imprimer
            </button>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal-overlay" id="confirmDeleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation</h3>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="deleteMessage">Êtes-vous sûr de vouloir supprimer cette facture ?</p>
            <div class="confirmation-buttons">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <a href="#" id="confirmDeleteLink" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Supprimer
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    let currentImageUrl = '';
    let currentImageTitle = '';

    // Afficher la notification
    document.addEventListener('DOMContentLoaded', function() {
        const notification = document.getElementById('successNotification');
        if (notification) {
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 400);
            }, 4000);
        }

        // Gestion de l'upload de fichier
        const fileInput = document.querySelector('.form-file');
        const filePreview = document.getElementById('filePreview');
        
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    filePreview.innerHTML = `
                        <div class="file-preview-item">
                            <img src="${e.target.result}" alt="Aperçu" class="file-preview-image">
                            <div class="file-preview-info">
                                <div class="file-preview-name">${file.name}</div>
                                <div class="file-preview-size">${formatFileSize(file.size)}</div>
                            </div>
                            <button type="button" class="file-preview-remove" onclick="clearFileInput()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    filePreview.classList.add('active');
                };
                reader.readAsDataURL(file);
            }
        });

        // Animation au chargement
        const cards = document.querySelectorAll('.facture-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function clearFileInput() {
        document.querySelector('.form-file').value = '';
        document.getElementById('filePreview').classList.remove('active');
        document.getElementById('filePreview').innerHTML = '';
    }

    function changeDisplay(type) {
        const url = new URL(window.location.href);
        url.searchParams.set('display', type);
        window.location.href = url.toString();
    }

    function changeView(view) {
        const url = new URL(window.location.href);
        url.searchParams.set('view', view);
        window.location.href = url.toString();
    }

    function changeSort(sort) {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sort);
        window.location.href = url.toString();
    }

    function toggleMenu(button) {
        const menu = button.nextElementSibling;
        const isShowing = menu.classList.contains('show');
        
        // Fermer tous les autres menus
        document.querySelectorAll('.dropdown-menu.show').forEach(otherMenu => {
            if (otherMenu !== menu) {
                otherMenu.classList.remove('show');
            }
        });
        
        // Basculer le menu actuel
        menu.classList.toggle('show', !isShowing);
    }

    // Fermer les menus en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.facture-menu')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });

    // Changement de vue (grille/liste)
    document.querySelectorAll('.view-option').forEach(option => {
        option.addEventListener('click', function() {
            const view = this.dataset.view;
            changeView(view);
        });
    });

    // Téléchargement d'image
    function downloadFactureImage(imageUrl, title) {
        const link = document.createElement('a');
        link.href = imageUrl;
        link.download = 'facture_' + title.replace(/[^a-z0-9]/gi, '_') + '.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function openImageModal(imageUrl, title) {
        if (!imageUrl) return;
        
        currentImageUrl = imageUrl;
        currentImageTitle = title;
        
        document.getElementById('modalImage').src = imageUrl;
        document.getElementById('modalImage').alt = 'Facture ' + title;
        document.getElementById('imageModal').style.display = 'flex';
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
    }

    function closeImageModal() {
        document.getElementById('imageModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function downloadImage() {
        if (!currentImageUrl) return;
        
        const link = document.createElement('a');
        link.href = currentImageUrl;
        link.download = 'facture_' + currentImageTitle.replace(/[^a-z0-9]/gi, '_') + '.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function printImage() {
        if (!currentImageUrl) return;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Imprimer Facture - ${currentImageTitle}</title>
                    <style>
                        body { margin: 0; padding: 20px; text-align: center; background: white; }
                        img { max-width: 100%; height: auto; max-height: 90vh; }
                        .print-title { margin-bottom: 20px; font-family: Arial, sans-serif; color: #333; }
                        @media print {
                            body { padding: 0; }
                            .print-title { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-title">
                        <h2>Facture: ${currentImageTitle}</h2>
                        <p>Date d'impression: ${new Date().toLocaleDateString()}</p>
                    </div>
                    <img src="${currentImageUrl}" alt="Facture ${currentImageTitle}">
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() {
                                window.close();
                            }, 1000);
                        }
                    <\/script>
                </body>
            </html>
        `);
        printWindow.document.close();
    }

    function confirmDelete(factureId, factureTitle) {
        document.getElementById('deleteMessage').textContent = 
            `Êtes-vous sûr de vouloir supprimer la facture "${factureTitle}" ? Cette action est irréversible.`;
        document.getElementById('confirmDeleteLink').href = `?delete_facture=${factureId}&display=<?= $display_type ?>&view=<?= $view_mode ?>&sort=<?= $sort_by ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?>`;
        document.getElementById('confirmDeleteModal').style.display = 'flex';
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        document.getElementById('confirmDeleteModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Fermer les modales en cliquant à l'extérieur
    document.addEventListener('click', function(event) {
        if (event.target === document.getElementById('confirmDeleteModal')) {
            closeDeleteModal();
        }
        if (event.target === document.getElementById('imageModal')) {
            closeImageModal();
        }
    });

    // Fermer avec Echap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeImageModal();
            closeDeleteModal();
        }
    });
</script>

</body>
</html>