<?php
// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "produits_db";



session_start();
if (!isset($_SESSION['id_res'])) {
    header('Location: index.php');
    exit();
}
$id_res=$_SESSION['id_res'];
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
    
    // Récupération des notes groupées par date
    $stmt = $conn->query("
        SELECT 
            id, 
            content, 
            DATE(created_at) as date,
            created_at
        FROM notess 
        where id_res=$id_res
        ORDER BY created_at DESC
    ");
    $notes = $stmt->fetchAll();
    
    // Groupement par date
    $groupedNotes = [];
    foreach($notes as $note) {
        $date = date('d F Y', strtotime($note['date']));
        $groupedNotes[$date][] = $note;
    }

} catch(PDOException $e) {
    die("<div class='error'>Erreur : " . htmlspecialchars($e->getMessage()) . "</div>");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes Perso - Smart Notes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --accent: #f43f5e;
            --text: #1e293b;
            --background: #f8fafc;
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

        /* Éditeur de Notes */
        .note-editor {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
        }

        .note-editor textarea {
            width: 100%;
            height: 120px;
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            resize: none;
            font-size: 1rem;
            line-height: 1.6;
            transition: all 0.3s;
        }

        .note-editor textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }
/* Styles pour les modales de confirmation */
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
    max-width: 400px;
    animation: modalSlide 0.3s ease-out;
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
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
    color: var(--accent);
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close {
    cursor: pointer;
    font-size: 1.5rem;
    color: #64748b;
    transition: color 0.3s;
}

.modal-close:hover {
    color: var(--accent);
}

.modal-body {
    padding: 1rem 0;
}

.modal-body p {
    color: var(--text);
    font-size: 1rem;
    line-height: 1.5;
    margin-bottom: 1.5rem;
}

.confirmation-buttons {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.confirmation-buttons .btn {
    flex: 1;
    min-width: 120px;
    justify-content: center;
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

.btn-danger {
    background: var(--accent);
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

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
}
        /* Bouton Ajouter Note */
        .add-note-btn {
            margin-top: 1rem;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 0.9rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
        }

        .add-note-btn:hover {
            background-color: var(--accent);
            transform: translateY(-1px);
        }

        /* Liste des Notes */
        .notes-grid {
            display: grid;
            gap: 1.5rem;
        }

        .note-day-group {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .note-date {
            color: var(--primary);
            font-size: 1.2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .note-card {
            background: var(--background);
            border-radius: 8px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            position: relative;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #e2e8f0;
        }

        .note-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.08);
        }

        .note-content {
            color: var(--text);
            font-size: 1rem;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .note-time {
            color: #64748b;
            font-size: 0.8rem;
            margin-top: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .note-actions {
            position: absolute;
            top: 0.8rem;
            right: 0.8rem;
            display: flex;
            gap: 0.5rem;
            opacity: 1;
            transition: opacity 0.3s;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.8rem;
        }

        .edit-btn {
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }

        .delete-btn {
            background: rgba(244, 63, 94, 0.1);
            color: var(--accent);
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        /* Bouton Flottant */
        .floating-btn {
            position: fixed;
            bottom: 5rem;
            right: 1rem;
            background: var(--primary);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 500;
            font-size: 1.2rem;
        }

        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0,0,0,0.3);
        }

        /* État vide */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
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

            .note-editor {
                padding: 2rem;
                border-radius: 16px;
                margin-bottom: 3rem;
            }

            .note-editor textarea {
                height: 150px;
                font-size: 1.1rem;
            }

            .add-note-btn {
                width: auto;
                padding: 12px 24px;
                font-size: 1rem;
            }

            .note-day-group {
                padding: 2rem;
                border-radius: 16px;
            }

            .note-date {
                font-size: 1.4rem;
            }

            .note-card {
                padding: 1.5rem;
                border-radius: 12px;
            }

            .note-content {
                font-size: 1.1rem;
            }

            .note-actions {
                opacity: 0;
            }

            .note-card:hover .note-actions {
                opacity: 1;
            }

            .floating-btn {
                width: 60px;
                height: 60px;
                bottom: 2rem;
                right: calc(70px + 2rem);
                font-size: 1.5rem;
            }
        }

        @media (min-width: 1024px) {
            .notes-grid {
                gap: 2rem;
            }
            
            .note-editor {
                margin-bottom: 3rem;
            }
        }

        @media (max-width: 767px) {
            .note-card {
                padding: 1rem;
            }
            
            .note-content {
                font-size: 0.95rem;
                padding-right: 3rem;
            }
            
            .note-actions {
                top: 0.5rem;
                right: 0.5rem;
            }
            
            .action-btn {
                width: 28px;
                height: 28px;
                font-size: 0.7rem;
            }
            
            .note-day-group {
                padding: 1.2rem;
            }
            
            .note-date {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 0.8rem;
                padding-bottom: 70px;
            }
            
            .note-editor {
                padding: 1rem;
            }
            
            .note-editor textarea {
                height: 100px;
                font-size: 0.9rem;
            }
            
            .add-note-btn {
                padding: 8px 16px;
                font-size: 0.85rem;
            }
            
            .note-day-group {
                padding: 1rem;
            }
            
            .note-card {
                padding: 0.8rem;
            }
            
            .note-content {
                font-size: 0.9rem;
            }
            
            .floating-btn {
                width: 45px;
                height: 45px;
                bottom: 4rem;
                font-size: 1rem;
            }
        }

        /* Animation pour les nouvelles notes */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .note-card {
            animation: slideIn 0.3s ease-out;
        }

        /* Mode édition */
        .editing {
            border: 2px solid var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        /* Indicateur de chargement */
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
        
        <a href="note.php" class="nav-icon active" title="Note">
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
    <!-- Éditeur de notes -->
    <div class="note-editor">
        <form id="noteForm">
            <textarea 
                placeholder="Commencez à écrire votre note..."
                rows="4"
                id="noteContent"
            ></textarea>
            <button type="button" id="addNoteBtn" class="add-note-btn">
                <i class="fas fa-plus"></i>
                Ajouter Note
            </button>
        </form>
    </div>

    <!-- Liste des notes -->
    <div class="notes-grid">
        <?php if(!empty($groupedNotes)): ?>
            <?php foreach($groupedNotes as $date => $notes): ?>
                <div class="note-day-group">
                    <div class="note-date">
                        <i class="fas fa-calendar-day"></i>
                        <?= $date ?>
                    </div>
                    <?php foreach($notes as $note): ?>
                        <div class="note-card" data-note-id="<?= $note['id'] ?>">
                            <div class="note-actions">
                                <div class="action-btn edit-btn" data-id="<?= $note['id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="action-btn delete-btn" data-id="<?= $note['id'] ?>">
                                    <i class="fas fa-trash"></i>
                                </div>
                            </div>
                            <div class="note-content"><?= htmlspecialchars($note['content']) ?></div>
                            <div class="note-time">
                                <i class="fas fa-clock"></i>
                                <?= date('H:i', strtotime($note['created_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-sticky-note"></i>
                <h3>Aucune note pour le moment</h3>
                <p>Commencez par créer votre première note !</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bouton flottant pour sauvegarder -->
<div class="floating-btn" id="saveNote" style="display: none;">
    <i class="fas fa-save"></i>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const saveBtn = document.getElementById('saveNote');
    const noteContent = document.getElementById('noteContent');
    const addNoteBtn = document.getElementById('addNoteBtn');
    let currentEditId = null;

    // Fonction pour ajouter une note
    addNoteBtn.addEventListener('click', async () => {
        const content = noteContent.value.trim();
        if(content) {
            addNoteBtn.disabled = true;
            addNoteBtn.innerHTML = '<div class="loading"></div> Ajout...';
            
            try {
                const response = await fetch('save_note.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ content })
                });

                if(response.ok) {
                    location.reload();
                } else {
                    alert('Erreur lors de l\'ajout de la note');
                }
            } catch(error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            } finally {
                addNoteBtn.disabled = false;
                addNoteBtn.innerHTML = '<i class="fas fa-plus"></i> Ajouter Note';
            }
        } else {
            alert('Veuillez écrire une note avant de l\'ajouter');
        }
    });

    // Édition de note
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const noteId = this.dataset.id;
            const noteCard = this.closest('.note-card');
            const content = noteCard.querySelector('.note-content').textContent;
            
            noteContent.value = content;
            noteContent.focus();
            currentEditId = noteId;
            
            // Afficher le bouton de sauvegarde
            saveBtn.style.display = 'flex';
            saveBtn.innerHTML = '<i class="fas fa-save"></i>';
            
            // Marquer la note en cours d'édition
            document.querySelectorAll('.note-card').forEach(card => {
                card.classList.remove('editing');
            });
            noteCard.classList.add('editing');
            
            // Scroll vers l'éditeur
            document.querySelector('.note-editor').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        });
    });

    // Sauvegarde de l'édition
    // Sauvegarde de l'édition
saveBtn.addEventListener('click', async () => {
    if (!currentEditId) return;
    
    const content = noteContent.value.trim();
    if(content) {
        saveBtn.innerHTML = '<div class="loading"></div>';
        
        try {
            const response = await fetch('update_note.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    id: currentEditId,
                    content: content
                })
            });

            const result = await response.json();
            
            if(result.success) {
                location.reload();
            } else {
                alert('Erreur: ' + (result.error || 'Erreur inconnue'));
                saveBtn.innerHTML = '<i class="fas fa-save"></i>';
            }
        } catch(error) {
            console.error('Erreur:', error);
            alert('Erreur de connexion');
            saveBtn.innerHTML = '<i class="fas fa-save"></i>';
        }
    } else {
        alert('La note ne peut pas être vide');
        saveBtn.innerHTML = '<i class="fas fa-save"></i>';
    }
});

    // Suppression de note
   // Variables globales pour la suppression
let currentDeleteId = null;
let currentDeleteContent = null;

// Suppression de note avec confirmation
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const noteId = this.dataset.id;
        const noteCard = this.closest('.note-card');
        const noteContent = noteCard.querySelector('.note-content').textContent;
        
        // Stocker les informations pour la suppression
        currentDeleteId = noteId;
        currentDeleteContent = noteContent;
        
        // Afficher la modal de confirmation
        showDeleteModal(noteContent);
    });
});

// Fonction pour afficher la modal de suppression
function showDeleteModal(content) {
    const message = `Êtes-vous sûr de vouloir supprimer cette note ?\n\n"${content.substring(0, 80)}${content.length > 80 ? '...' : ''}"`;
    document.getElementById('deleteMessage').textContent = message;
    document.getElementById('confirmDeleteModal').style.display = 'flex';
}

// Fonction pour fermer la modal de suppression
function closeDeleteModal() {
    document.getElementById('confirmDeleteModal').style.display = 'none';
    currentDeleteId = null;
    currentDeleteContent = null;
}

// Confirmation de suppression
document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    if (!currentDeleteId) return;
    
    const deleteBtn = this;
    const originalContent = deleteBtn.innerHTML;
    
    // Afficher le loading
    deleteBtn.innerHTML = '<div class="loading"></div> Suppression...';
    deleteBtn.disabled = true;
    
    try {
        const response = await fetch(`delete_note.php?id=${currentDeleteId}`, { 
            method: 'DELETE' 
        });
        
        const result = await response.json();
        
        if(result.success) {
            // Fermer la modal et recharger la page
            closeDeleteModal();
            location.reload();
        } else {
            alert('Erreur lors de la suppression: ' + (result.error || 'Erreur inconnue'));
            deleteBtn.innerHTML = originalContent;
            deleteBtn.disabled = false;
        }
    } catch(error) {
        console.error('Erreur:', error);
        alert('Erreur de connexion lors de la suppression');
        deleteBtn.innerHTML = originalContent;
        deleteBtn.disabled = false;
    }
});

// Fermer la modal en cliquant à l'extérieur
document.addEventListener('click', function(event) {
    if (event.target === document.getElementById('confirmDeleteModal')) {
        closeDeleteModal();
    }
});

// Fermer la modal avec la touche Echap
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape' && document.getElementById('confirmDeleteModal').style.display === 'flex') {
        closeDeleteModal();
    }
});
    // Annuler l'édition en cliquant ailleurs
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.note-card') && !e.target.closest('.note-editor')) {
            currentEditId = null;
            saveBtn.style.display = 'none';
            document.querySelectorAll('.note-card').forEach(card => {
                card.classList.remove('editing');
            });
        }
    });

    // Raccourci clavier Ctrl+Enter pour sauvegarder
    noteContent.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.key === 'Enter') {
            if (currentEditId) {
                saveBtn.click();
            } else {
                addNoteBtn.click();
            }
        }
    });
});

</script>
<!-- Modal de confirmation pour supprimer une note -->
<div class="modal-overlay" id="confirmDeleteModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-exclamation-triangle"></i> Confirmation</h3>
            <span class="modal-close" onclick="closeDeleteModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p id="deleteMessage">Êtes-vous sûr de vouloir supprimer cette note ?</p>
            <div class="confirmation-buttons">
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash-alt"></i> Oui, supprimer
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </div>
    </div>
</div>
</body>
</html>