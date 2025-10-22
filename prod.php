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
$display_type = $_GET['display'] ?? 'all';
$view_mode = $_GET['view'] ?? 'grid';
$sort_by = $_GET['sort'] ?? 'name';

// Récupérer le terme de recherche s'il existe
$search_term = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = trim($_GET['search']);
}

// -------------------------------
// TRAITEMENT DES ACTIONS
// -------------------------------

// Modification d'un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_product') {
    $product_id = intval($_POST['product_id']);
    $nom = trim($_POST['nom']);
    $prix = floatval($_POST['prix']);
    
    // Gestion de l'upload d'image
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/produits/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '_' . time() . '.' . $file_extension;
        $image_path = $upload_dir . $file_name;
        
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
        
        // Supprimer l'ancienne image si elle existe
        $stmt = $pdo->prepare("SELECT image FROM produits WHERE id = :id AND id_res = :id_res");
        $stmt->execute(['id' => $product_id, 'id_res' => $id_res]);
        $old_product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($old_product && $old_product['image'] && file_exists($old_product['image'])) {
            unlink($old_product['image']);
        }
    }
    
    if (!empty($nom) && $prix > 0) {
        if ($image_path) {
            $stmt = $pdo->prepare("UPDATE produits SET nom = :nom, prix = :prix, image = :image WHERE id = :id AND id_res = :id_res");
            $stmt->execute([
                'nom' => $nom,
                'prix' => $prix,
                'image' => $image_path,
                'id' => $product_id,
                'id_res' => $id_res
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE produits SET nom = :nom, prix = :prix WHERE id = :id AND id_res = :id_res");
            $stmt->execute([
                'nom' => $nom,
                'prix' => $prix,
                'id' => $product_id,
                'id_res' => $id_res
            ]);
        }
        
        $_SESSION['success_message'] = 'Produit modifié avec succès!';
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Suppression d'un produit
if (isset($_GET['delete_product'])) {
    $product_id = intval($_GET['delete_product']);
    
    // Récupérer le chemin de l'image pour la supprimer du serveur
    $stmt = $pdo->prepare("SELECT image FROM produits WHERE id = :id AND id_res = :id_res");
    $stmt->execute(['id' => $product_id, 'id_res' => $id_res]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product && $product['image'] && file_exists($product['image'])) {
        unlink($product['image']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM produits WHERE id = :id AND id_res = :id_res");
    $stmt->execute(['id' => $product_id, 'id_res' => $id_res]);
    
    $_SESSION['success_message'] = 'Produit supprimé avec succès!';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ----------------------------------
// RÉCUPÉRATION DES DONNÉES
// ----------------------------------

// Compter le total des produits
$stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM produits WHERE id_res = :id_res");
$stmt_count->execute(['id_res' => $id_res]);
$total_products = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

// Récupérer les produits selon le type d'affichage
$where_conditions = ["id_res = :id_res"];
$params = ['id_res' => $id_res];

if (!empty($search_term)) {
    $where_conditions[] = "(nom LIKE :search_term OR prix LIKE :search_term)";
    $params['search_term'] = '%' . $search_term . '%';
}

$where_clause = implode(" AND ", $where_conditions);
$sql = "SELECT * FROM produits WHERE $where_clause ORDER BY ";

// Ajouter le tri
switch ($sort_by) {
    case 'price_asc':
        $sql .= "prix ASC";
        break;
    case 'price_desc':
        $sql .= "prix DESC";
        break;
    case 'newest':
        $sql .= "id DESC";
        break;
    case 'name':
    default:
        $sql .= "nom ASC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Message de succès
$success_message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des Produits - SBASA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #4f46e5;
            --accent: #f43f5e;
            --success: #10b981;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border: #e2e8f0;
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
            text-align: center;
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
        .stat-card.active .stat-label {
            color: white;
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

        /* Section des produits */
        .products-section {
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

        .products-count {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
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

        /* Conteneur des produits */
        .products-container {
            transition: all 0.3s ease;
        }

        .products-container.view-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .products-container.view-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .products-container.view-list .product-card {
            display: flex;
            align-items: center;
            padding: 1.25rem;
        }

        .products-container.view-list .product-image-container {
            width: 80px;
            height: 80px;
            margin-right: 1.5rem;
            margin-bottom: 0;
        }

        .products-container.view-list .product-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .products-container.view-list .product-actions {
            margin-top: 0;
            border-top: none;
            padding-top: 0;
        }

        /* Carte de produit */
        .product-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.25rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .product-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .product-badge {
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

        .product-menu {
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
        .product-image-container {
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

        .product-image {
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

        .product-image-container:hover .image-overlay {
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

        /* QR Code */
        .qr-code-container {
            width: 100%;
            height: 120px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: var(--radius-sm);
            border: 2px solid var(--border);
            padding: 0.5rem;
        }

        .qr-code {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        /* Contenu de la carte */
        .product-content {
            padding: 0.5rem 0;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .product-price {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--success);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-price::before {
            content: 'د.ت';
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-right: 0.25rem;
        }

        .product-meta {
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

        .product-actions {
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

        .edit-btn {
            background: var(--primary);
            color: white;
        }

        .edit-btn:hover {
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

        /* Modal de modification */
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
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
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
            transition: var(--transition);
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

        .modal-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
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
            flex: 1;
            justify-content: center;
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

        /* Responsive */
        @media (max-width: 768px) {
            .products-container.view-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .section-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .section-title-container {
                justify-content: space-between;
            }
            
            .controls {
                justify-content: space-between;
                width: 100%;
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

            .modal-content {
                padding: 1.25rem;
                margin: 1rem;
            }

            .modal-actions {
                flex-direction: column;
            }

            .products-container.view-list .product-card {
                flex-direction: column;
                align-items: stretch;
            }
            
            .products-container.view-list .product-image-container {
                width: 100%;
                height: 120px;
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .products-container.view-list .product-content {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (max-width: 480px) {
            .products-container.view-grid {
                grid-template-columns: 1fr;
            }
            
            .product-card {
                padding: 1rem;
            }
            
            .product-image-container {
                height: 140px;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .action-btn {
                padding: 10px;
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
        <a href="prod.php" class="nav-icon active" title="Panier">
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
            <i class="fas fa-box-open"></i>
            <h1>Gestion des Produits</h1>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="stats-container">
        <div class="stat-card active">
            <div class="stat-number"><?= $total_products ?></div>
            <div class="stat-label">Total Produits</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($produits) ?></div>
            <div class="stat-label">Produits Affichés</div>
        </div>
    </div>
    
    <!-- Section de recherche -->
    <div class="search-section">
        <form method="GET" action="" class="search-box">
            <input type="hidden" name="view" value="<?= $view_mode ?>">
            <input type="hidden" name="sort" value="<?= $sort_by ?>">
            <div class="search-input-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Rechercher par nom, prix..." 
                       value="<?= htmlspecialchars($search_term) ?>"
                       autocomplete="off">
            </div>
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i>
                Rechercher
            </button>
            
        </form>
        
        <?php if (!empty($search_term)): ?>
            <div class="search-results-info">
                <i class="fas fa-info-circle"></i>
                Résultats pour : "<strong><?= htmlspecialchars($search_term) ?></strong>"
                (<?= count($produits) ?> produit(s) trouvé(s))
            </div>
        <?php endif; ?>
    </div>

    <!-- Section des produits -->
    <div class="products-section">
        <div class="section-header">
            <div class="section-title-container">
                <h2 class="section-title">
                    <i class="fas fa-boxes"></i>
                    Mes Produits
                    <span class="products-count">(<?= count($produits) ?>)</span>
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
                    <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>>Par nom</option>
                    <option value="price_asc" <?= $sort_by === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                    <option value="price_desc" <?= $sort_by === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                    <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Plus récents</option>
                </select>
            </div>
        </div>

        <div class="products-container view-<?= $view_mode ?>">
            <?php if (empty($produits)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <h3>Aucun produit trouvé</h3>
                    <p><?= empty($search_term) ? 'Aucun produit n\'a été ajouté pour le moment.' : 'Aucun produit ne correspond à votre recherche' ?></p>
                    
                </div>
            <?php else: ?>
                <?php foreach ($produits as $index => $produit): ?>
                    <div class="product-card">
                        <div class="product-card-header">
                            <div class="product-badge">
                                <i class="fas fa-tag"></i>
                            </div>
                            <div class="product-menu">
                                <button class="menu-btn" onclick="toggleMenu(this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <button onclick="openEditModal(<?= $produit['id'] ?>, '<?= addslashes($produit['nom']) ?>', <?= $produit['prix'] ?>, '<?= addslashes($produit['image']) ?>')">
                                        <i class="fas fa-edit"></i> Modifier
                                    </button>
                                    <button class="delete-option" onclick="confirmDelete(<?= $produit['id'] ?>, '<?= addslashes($produit['nom']) ?>')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="product-image-container" onclick="openImageModal('<?= htmlspecialchars($produit['image']) ?>', '<?= htmlspecialchars($produit['nom']) ?>')">
                            <?php if ($produit['image'] && file_exists($produit['image'])): ?>
                                <img src="<?= htmlspecialchars($produit['image']) ?>" 
                                     alt="Produit <?= htmlspecialchars($produit['nom']) ?>" 
                                     class="product-image">
                                <div class="image-overlay">
                                    <i class="fas fa-search-plus"></i>
                                </div>
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image"></i>
                                    <span>Aucune image</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($produit['codeqr'] && file_exists($produit['codeqr'])): ?>
                            <div class="qr-code-container">
                                <img src="<?= htmlspecialchars($produit['codeqr']) ?>" 
                                     alt="QR Code <?= htmlspecialchars($produit['nom']) ?>" 
                                     class="qr-code">
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-content">
                            <h3 class="product-title" title="<?= htmlspecialchars($produit['nom']) ?>">
                                <?= htmlspecialchars($produit['nom']) ?>
                            </h3>
                            
                            <div class="product-price">
                                <?= number_format($produit['prix'], 2, ',', ' ') ?>
                            </div>
                            
                            <div class="product-actions">
                                <button class="action-btn edit-btn" onclick="openEditModal(<?= $produit['id'] ?>, '<?= addslashes($produit['nom']) ?>', <?= $produit['prix'] ?>, '<?= addslashes($produit['image']) ?>')">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <button class="action-btn delete-btn" onclick="confirmDelete(<?= $produit['id'] ?>, '<?= addslashes($produit['nom']) ?>')">
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

<!-- Modal de modification -->
<div class="modal-overlay" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Modifier le produit</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="editForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" id="editProductId">
            
            <div class="form-group">
                <label class="form-label">Nom du produit *</label>
                <input type="text" name="nom" id="editNom" class="form-input" placeholder="Entrez le nom du produit" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Prix (د.ت) *</label>
                <input type="number" name="prix" id="editPrix" class="form-input" placeholder="0.00" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Image du produit</label>
                <div class="file-upload-container">
                    <label class="file-upload-label" for="editImage">
                        <div class="file-upload-content">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span class="file-upload-text" id="fileUploadText">Changer l'image</span>
                            <span class="file-upload-hint">PNG, JPG, JPEG jusqu'à 5MB</span>
                        </div>
                        <input type="file" name="image" id="editImage" class="form-file" accept="image/*" hidden>
                    </label>
                    <div class="file-preview" id="editFilePreview"></div>
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

<!-- Modal de confirmation de suppression -->
<div class="modal-overlay" id="confirmDeleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation</h3>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p id="deleteMessage">Êtes-vous sûr de vouloir supprimer ce produit ?</p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <a href="#" id="confirmDeleteLink" class="btn btn-primary" style="background: var(--accent);">
                    <i class="fas fa-trash-alt"></i> Supprimer
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de visualisation d'image -->
<div class="modal-overlay" id="imageModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-image"></i> Visualisation</h3>
            <button class="modal-close" onclick="closeImageModal()">&times;</button>
        </div>
        <div class="modal-body">
            <img id="modalImage" class="product-image" src="" alt="" style="width: 100%; border-radius: var(--radius-sm);">
        </div>
    </div>
</div>

<script>
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

        // Gestion de l'upload d'image dans le modal
        const editImageInput = document.getElementById('editImage');
        if (editImageInput) {
            editImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('editFilePreview');
                        preview.innerHTML = `
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; background: var(--background); border-radius: var(--radius-sm); margin-top: 1rem;">
                                <img src="${e.target.result}" alt="Aperçu" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                <div>
                                    <div style="font-weight: 600; color: var(--text-primary);">${file.name}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">${formatFileSize(file.size)}</div>
                                </div>
                            </div>
                        `;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Animation au chargement
        const cards = document.querySelectorAll('.product-card');
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
        if (!e.target.closest('.product-menu')) {
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

    function openEditModal(productId, nom, prix, image) {
        document.getElementById('editProductId').value = productId;
        document.getElementById('editNom').value = nom;
        document.getElementById('editPrix').value = prix;
        
        // Réinitialiser la preview
        document.getElementById('editFilePreview').innerHTML = '';
        document.getElementById('editImage').value = '';
        
        document.getElementById('editModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function openImageModal(imageUrl, title) {
        if (!imageUrl) return;
        
        document.getElementById('modalImage').src = imageUrl;
        document.getElementById('modalImage').alt = 'Produit ' + title;
        document.getElementById('imageModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeImageModal() {
        document.getElementById('imageModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function confirmDelete(productId, productName) {
        document.getElementById('deleteMessage').textContent = 
            `Êtes-vous sûr de vouloir supprimer le produit "${productName}" ? Cette action est irréversible.`;
        document.getElementById('confirmDeleteLink').href = `?delete_product=${productId}&view=<?= $view_mode ?>&sort=<?= $sort_by ?><?= !empty($search_term) ? '&search=' . urlencode($search_term) : '' ?>`;
        document.getElementById('confirmDeleteModal').style.display = 'flex';
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
        if (event.target === document.getElementById('editModal')) {
            closeEditModal();
        }
        if (event.target === document.getElementById('imageModal')) {
            closeImageModal();
        }
    });

    // Fermer avec Echap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEditModal();
            closeDeleteModal();
            closeImageModal();
        }
    });
</script>

</body>
</html>