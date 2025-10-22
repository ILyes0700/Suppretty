<?php
session_start();        
if(!isset($_SESSION['id_res'])){
    header('Location: index.php');
    exit();
}   
$id_res=$_SESSION['id_res'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Sabasty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Votre CSS reste identique */
        :root {
            --primary: #6366f1;
            --secondary: #4f46e5;
            --accent: #f43f5e;
            --background: #f8fafc;
            --text: #1e293b;
            --success: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: var(--text);
            padding: 1rem;
            padding-bottom: 80px;
        }

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
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .search-container, .sabasty-container {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
        }

        h2 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.3rem;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text);
            font-size: 0.95rem;
        }

        input, select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        button {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #f43f5e;
            color: white;
            font-size: 0.9rem;
            min-width: 140px;
            justify-content: center;
            align-self: flex-end;
            height: fit-content;
            margin-top: 1.5rem;
        }

        button.secondary {
            background: #6366f1;
        }

        button:hover {
            filter: brightness(1.1);
            transform: translateY(-1px);
        }

        button:active {
            transform: translateY(0);
        }

        .date-group {
            margin-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1rem;
        }

        .date-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem 1rem;
            background: #f1f5f9;
            border-radius: 0.75rem;
        }

        .date-title {
            font-weight: 700;
            color: #f43f5e;
            font-size: 1.1rem;
        }

        .sabasty-count {
            background: #f43f5e;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .sabasty-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }

        .sabasty-card {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.25rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .sabasty-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .sabasty-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .sabasty-info h3 {
            color: var(--text);
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }

        .sabasty-id {
            color: #64748b;
            font-size: 0.85rem;
        }

        .sabasty-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.5rem;
            border-radius: 0.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn.delete {
            color: #f43f5e;
            border-color: #f43f5e;
        }

        .action-btn.delete:hover {
            background: #f43f5e;
            color: white;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
        }

        .sabasty-details {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .detail-label {
            color: #64748b;
            font-weight: 500;
        }

        .detail-value {
            color: var(--text);
            font-weight: 600;
        }

        .no-results {
            text-align: center;
            padding: 2rem;
            color: #64748b;
            font-style: italic;
        }

        .pdf-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-top: 0.5rem;
            transition: all 0.2s;
        }

        .pdf-link:hover {
            color: var(--secondary);
        }

        /* Styles pour la confirmation de suppression */
        .delete-confirmation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }

        .delete-confirmation.active {
            display: flex;
        }

        .confirmation-box {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .confirmation-icon {
            font-size: 3rem;
            color: #f43f5e;
            margin-bottom: 1rem;
        }

        .confirmation-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .confirmation-message {
            color: #64748b;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }

        .confirmation-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn-confirm {
            background: #f43f5e;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            min-width: 120px;
        }

        .btn-cancel {
            background: #e2e8f0;
            color: var(--text);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            min-width: 120px;
        }

        .btn-confirm:hover {
            background: #e11d48;
            transform: translateY(-1px);
        }

        .btn-cancel:hover {
            background: #cbd5e1;
            transform: translateY(-1px);
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

            .search-form {
                flex-wrap: nowrap;
            }

            .sabasty-list {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }

        @media (max-width: 767px) {
            .search-form {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            button {
                width: 100%;
            }
            
            h2 {
                font-size: 1.2rem;
            }
            
            .search-container, .sabasty-container {
                padding: 1.2rem;
            }
            
            .sabasty-list {
                grid-template-columns: 1fr;
            }

            .confirmation-buttons {
                flex-direction: column;
            }
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .status-message {
            padding: 1rem;
            border-radius: 0.75rem;
            margin: 1rem 0;
            text-align: center;
            font-weight: 600;
        }

        .status-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>

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
        <a href="factures.php" class="nav-icon" title="facteur" >
            <i class="fas fa-file-invoice"></i>
        </a>
        <a href="a4ya.php" class="nav-icon" title="Stock">
            <i class="fas fa-boxes"></i> 
        </a>
         <a href="sbasty.php" class="nav-icon active" title="sbasa">
            <i class="fas fa-users"></i> 
        </a>
        <a href="gen.php" class="nav-icon" title="Générer QR code" onclick="showqr(event)">
            <i class="fas fa-qrcode"></i>
        </a>
    </div>

<!-- Overlay de confirmation de suppression -->
<div class="delete-confirmation" id="deleteConfirmation">
    <div class="confirmation-box">
        <div class="confirmation-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="confirmation-title">Confirmation de suppression</div>
        <div class="confirmation-message" id="confirmationMessage">
            Êtes-vous sûr de vouloir supprimer cette sabasty ?
        </div>
        <div class="confirmation-buttons">
            <button class="btn-cancel" id="cancelDelete">Annuler</button>
            <button class="btn-confirm" id="confirmDelete">Oui, supprimer</button>
        </div>
    </div>
</div>

<div class="container">
    <!-- Zone de recherche -->
    <div class="search-container">
        <h2><i class="fas fa-search"></i> Rechercher des Sabasty</h2>
        <form class="search-form" method="GET" action="">
            <div class="form-group">
                <label for="search-name">Nom du client</label>
                <input type="text" id="search-name" name="nom" placeholder="Entrez le nom du client" value="<?php echo isset($_GET['nom']) ? htmlspecialchars($_GET['nom']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="search-date">Date</label>
                <input type="date" id="search-date" name="date" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
            </div>
            <button type="submit">
                <i class="fas fa-search"></i> Rechercher
            </button>
        </form>
    </div>

    <!-- Affichage des sabasty -->
    <div class="sabasty-container">
        <h2><i class="fas fa-file-pdf"></i> Liste des Sabasty</h2>
        
        <?php
        // Connexion à la base de données
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "produits_db";
    
      
        
        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Construction de la requête SQL de base
            $sql = "SELECT sf.*, c.title as client_nom 
                    FROM sbasty_files sf 
                    LEFT JOIN cards c ON sf.id_client = c.id 
                    WHERE sf.id_res = :id_res";
            
            $params = [':id_res' => $id_res];
            
            // Filtre par nom de client
            if (isset($_GET['nom']) && !empty(trim($_GET['nom']))) {
                $sql .= " AND c.title LIKE :nom";
                $params[':nom'] = '%' . trim($_GET['nom']) . '%';
            }
            
            // Filtre par date
            if (isset($_GET['date']) && !empty($_GET['date'])) {
                $sql .= " AND DATE(sf.date_ajoute) = :date";
                $params[':date'] = $_GET['date'];
            }
            
            $sql .= " ORDER BY sf.date_ajoute DESC, c.title ASC";
            
            // Debug: Afficher la requête
            echo "<!-- Debug SQL: " . htmlspecialchars($sql) . " -->";
            echo "<!-- Debug Params: " . print_r($params, true) . " -->";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $sabastyFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<!-- Debug: " . count($sabastyFiles) . " résultats trouvés -->";
            
            // Grouper les sabasty par date
            $sabastyByDate = [];
            foreach ($sabastyFiles as $file) {
                $date = date('Y-m-d', strtotime($file['date_ajoute']));
                $sabastyByDate[$date][] = $file;
            }
            
            // Afficher les résultats
            if (empty($sabastyByDate)) {
                echo '<div class="no-results">';
                if (isset($_GET['nom']) || isset($_GET['date'])) {
                    echo 'Aucun sabasty trouvé pour les critères de recherche spécifiés.';
                    
                } else {
                    echo 'Aucun sabasty trouvé dans la base de données.';
                }
                echo '</div>';
            } else {
                echo "<!-- " . count($sabastyFiles) . " sabasty(s) trouvé(s) -->";
                foreach ($sabastyByDate as $date => $files) {
                    $formattedDate = date('d/m/Y', strtotime($date));
                    echo '<div class="date-group">';
                    echo '<div class="date-header">';
                    echo '<div class="date-title">' . $formattedDate . '</div>';
                    echo '<div class="sabasty-count">' . count($files) . ' sabasty</div>';
                    echo '</div>';
                    
                    echo '<div class="sabasty-list">';
                    foreach ($files as $file) {
                        // Correction du nom de colonne : 'sabsty' au lieu de 'sabasty'
                        $filePath = 'uploads/sbasty/' . $file['sabsty'];
                        $fileExists = file_exists($filePath);
                        
                        echo '<div class="sabasty-card">';
                        echo '<div class="sabasty-header">';
                        echo '<div class="sabasty-info">';
                        echo '<h3>' . htmlspecialchars($file['client_nom'] ?? 'Client inconnu') . '</h3>';
                        echo '<div class="sabasty-id">ID: ' . $file['id'] . '</div>';
                        echo '</div>';
                        echo '<div class="sabasty-actions">';
                        if ($fileExists) {
                            echo '<a href="' . $filePath . '" target="_blank" class="action-btn" title="Voir le PDF">';
                            echo '<i class="fas fa-eye"></i>';
                            echo '</a>';
                            echo '<a href="' . $filePath . '" download class="action-btn" title="Télécharger">';
                            echo '<i class="fas fa-download"></i>';
                            echo '</a>';
                        } else {
                            echo '<span class="action-btn" title="Fichier non trouvé" style="color: #f43f5e; cursor: not-allowed;">';
                            echo '<i class="fas fa-exclamation-triangle"></i>';
                            echo '</span>';
                        }
                        // Bouton de suppression
                        echo '<button class="action-btn delete" title="Supprimer" onclick="showDeleteConfirmation(' . $file['id'] . ', \'' . htmlspecialchars(addslashes($file['client_nom'] ?? 'Client inconnu')) . '\')">';
                        echo '<i class="fas fa-trash"></i>';
                        echo '</button>';
                        echo '</div>';
                        echo '</div>';
                        
                        echo '<div class="sabasty-details">';
                        echo '<div class="detail-row">';
                        echo '<span class="detail-label">Client:</span>';
                        echo '<span class="detail-value">' . htmlspecialchars($file['client_nom'] ?? 'N/A') . '</span>';
                        echo '</div>';
                        echo '<div class="detail-row">';
                        echo '<span class="detail-label">Date d\'ajout:</span>';
                        echo '<span class="detail-value">' . date('H:i', strtotime($file['date_ajoute'])) . '</span>';
                        echo '</div>';
                        if ($fileExists) {
                            echo '<a href="' . $filePath . '" target="_blank" class="pdf-link">';
                            echo '<i class="fas fa-file-pdf"></i> Voir le document PDF';
                            echo '</a>';
                        } else {
                            echo '<span style="color: #f43f5e; font-weight: 600;">';
                            echo '<i class="fas fa-exclamation-triangle"></i> Fichier PDF non trouvé';
                            echo '</span>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
            }
            
        } catch(PDOException $e) {
            echo '<div class="status-message status-error">Erreur de connexion: ' . $e->getMessage() . '</div>';
            echo "<!-- Erreur PDO: " . htmlspecialchars($e->getMessage()) . " -->";
        }
        ?>
    </div>
</div>

<script>
    let currentDeleteId = null;
    let currentDeleteName = null;

    // Fonction pour afficher la confirmation de suppression
    function showDeleteConfirmation(id, clientName) {
        currentDeleteId = id;
        currentDeleteName = clientName;
        
        // Mettre à jour le message de confirmation
        document.getElementById('confirmationMessage').innerHTML = 
            'Êtes-vous sûr de vouloir supprimer cette sabasty de <strong>' + clientName + '</strong> ?';
        
        // Afficher la boîte de confirmation
        document.getElementById('deleteConfirmation').classList.add('active');
    }

    // Fonction pour cacher la confirmation de suppression
    function hideDeleteConfirmation() {
        document.getElementById('deleteConfirmation').classList.remove('active');
        currentDeleteId = null;
        currentDeleteName = null;
    }

    // Fonction pour effectuer la suppression
    function performDelete() {
        if (!currentDeleteId) return;
        
        // Afficher un indicateur de chargement
        const confirmBtn = document.getElementById('confirmDelete');
        const originalText = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<div class="loading"></div> Suppression...';
        confirmBtn.disabled = true;
        
        // Envoyer la requête de suppression via AJAX
        const formData = new FormData();
        formData.append('id', currentDeleteId);
        formData.append('action', 'delete_sabasty');
        
        fetch('delete_sabasty.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Afficher un message de succès et recharger la page
                
                location.reload();
            } else {
                // Afficher un message d'erreur
                alert('Erreur lors de la suppression : ' + (data.message || 'Erreur inconnue'));
                confirmBtn.innerHTML = originalText;
                confirmBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression');
            confirmBtn.innerHTML = originalText;
            confirmBtn.disabled = false;
        });
    }

    // Gestion de la soumission du formulaire
    document.addEventListener("DOMContentLoaded", function () {
        const searchForm = document.querySelector('.search-form');
        
        searchForm.addEventListener('submit', function(e) {
            // Afficher un indicateur de chargement
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading"></div> Recherche...';
            submitBtn.disabled = true;
            
            // Réactiver le bouton après 3 secondes (au cas où la soumission échoue)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });
        
        // Événements pour la confirmation de suppression
        document.getElementById('cancelDelete').addEventListener('click', hideDeleteConfirmation);
        document.getElementById('confirmDelete').addEventListener('click', performDelete);
        
        // Fermer la confirmation en cliquant à l'extérieur
        document.getElementById('deleteConfirmation').addEventListener('click', function(e) {
            if (e.target === this) {
                hideDeleteConfirmation();
            }
        });
        
        // Debug des paramètres URL
        console.log('URL actuelle:', window.location.href);
        console.log('Paramètres GET:', {
            nom: '<?php echo isset($_GET['nom']) ? $_GET['nom'] : ''; ?>',
            date: '<?php echo isset($_GET['date']) ? $_GET['date'] : ''; ?>'
        });
    });
</script>

</body>
</html>