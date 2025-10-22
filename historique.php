<?php  
session_start();
if (!isset($_SESSION['id_res'])) {
    header('Location: index.php');
    exit();
}
$id_res = $_SESSION['id_res'];
// Connexion sécurisée à la base de données
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "produits_db";

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
    
    // Pour le tableau, on récupère toutes les commandes (triées par date décroissante pour l'affichage)
    $stmt = $conn->query("SELECT * FROM commandes  where id_res=$id_res ORDER BY created_at DESC ");
    $commandes = $stmt->fetchAll();

    // Calcul du total général (pour l'ensemble des commandes)
    $totalGeneral = array_sum(array_column($commandes, 'total_price'));

    /*
      Pour les graphiques par produit, on regroupe les commandes par produit.
      Pour chaque produit, on regroupe ensuite par date (format 'Y-m-d') en sommant la quantité et le total.
      Ainsi, chaque graphique affichera l'évolution (selon la date) de la quantité commandée et du montant total.
    */
    $produitsGrouped = [];
    foreach ($commandes as $commande) {
        $product = $commande['product_name'];
        // Format de la date (exemple : 2025-02-07)
        $date = date('Y-m-d', strtotime($commande['created_at']));
        
        if (!isset($produitsGrouped[$product])) {
            $produitsGrouped[$product] = [];
        }
        if (!isset($produitsGrouped[$product][$date])) {
            $produitsGrouped[$product][$date] = ['quantity' => 0, 'total' => 0];
        }
        $produitsGrouped[$product][$date]['quantity'] += $commande['quantity'];
        $produitsGrouped[$product][$date]['total']    += $commande['total_price'];
    }
    
    // Préparation des données à transmettre à JavaScript pour la génération des graphiques
    $chartData = [];
    foreach ($produitsGrouped as $product => $dataByDate) {
        // Tri par date ascendante pour une courbe chronologique
        ksort($dataByDate);
        $dates = [];
        $quantities = [];
        $totals = [];
        foreach ($dataByDate as $date => $values) {
            $dates[] = $date;
            $quantities[] = $values['quantity'];
            $totals[] = $values['total'];
        }
        $chartData[] = [
            'product'      => $product,
            'dates'        => $dates,
            'quantities'   => $quantities,
            'totals'       => $totals,
            'overallTotal' => array_sum($totals)
        ];
    }

} catch(PDOException $e) {
    die("<div class='error'>Erreur de base de données : " . htmlspecialchars($e->getMessage()) . "</div>");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historique des Commandes</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Inclusion de Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root {
      --primary: #6366f1;
      --accent: #f43f5e;
      --text: #1e293b;
      --background: #f8fafc;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      --sidebar-bg: rgba(255, 255, 255, 0.95);
      --sidebar-border: rgba(209, 213, 219, 0.3);
      --sidebar-icon: #4f46e5;
      --gradient: linear-gradient(135deg, #6366f1 0%, #f43f5e 100%);
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
      margin-bottom: 1.5rem;
      color: var(--primary);
      display: flex;
      align-items: center;
      gap: 1rem;
      font-size: 1.8rem;
    }

    .commande-table {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      overflow-x: auto;
      margin-bottom: 2rem;
    }

    table {
      width: 100%;
      min-width: 600px;
      border-collapse: collapse;
    }

    th, td {
      padding: 1rem;
      text-align: left;
      border-bottom: 1px solid #f1f5f9;
    }

    th {
      background: var(--background);
      font-weight: 600;
      color: var(--primary);
      position: sticky;
      top: 0;
      font-size: 0.9rem;
    }

    tr:nth-child(even) {
      background-color: #f8fafc;
    }

    tr:hover {
      background-color: #f1f5f9;
    }

    .price {
      font-weight: 600;
      color: var(--primary);
    }

    .date {
      color: #64748b;
      font-size: 0.9em;
    }

    .total-box {
      background: var(--primary);
      color: white;
      padding: 1.2rem 1.5rem;
      border-radius: 12px;
      margin-top: 1rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      width: 100%;
      box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
      justify-content: space-between;
    }

    .total-box i {
      font-size: 1.3rem;
    }

    /* Styles pour la section des graphiques par produit */
    .charts-container {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.5rem;
      margin: 2rem 0;
    }

    .chart-card {
      background: white;
      padding: 1.2rem;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      width: 100%;
    }

    .chart-card h3 {
      text-align: center;
      margin-bottom: 1rem;
      color: var(--primary);
      font-size: 1.1rem;
    }

    .chart-container {
      position: relative;
      height: 250px;
      width: 100%;
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
        margin-bottom: 2.5rem;
      }

      .charts-container {
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 2rem;
      }

      .chart-card {
        padding: 1.5rem;
      }

      .chart-card h3 {
        font-size: 1.3rem;
      }

      .chart-container {
        height: 300px;
      }

      .total-box {
        width: auto;
        float: right;
        display: inline-flex;
      }
    }

    @media (min-width: 1024px) {
      .charts-container {
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
      }
      
      .chart-container {
        height: 320px;
      }
    }

    @media (max-width: 767px) {
      th, td {
        padding: 0.8rem 0.6rem;
        font-size: 0.85rem;
      }
      
      h1 {
        font-size: 1.5rem;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
      }
      
      .total-box {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
      }
      
      .total-box > div {
        display: flex;
        flex-direction: column;
        align-items: center;
      }
    }

    @media (max-width: 480px) {
      body {
        padding: 0.8rem;
        padding-bottom: 70px;
      }
      
      th, td {
        padding: 0.6rem 0.4rem;
        font-size: 0.8rem;
      }
      
      h1 {
        font-size: 1.3rem;
      }
      
      .chart-card {
        padding: 1rem;
      }
      
      .chart-card h3 {
        font-size: 1rem;
      }
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .error {
      background: #fee2e2;
      color: #dc2626;
      padding: 1rem;
      border-radius: 8px;
      margin: 2rem;
    }

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
  </style>
</head>
<body>
<div class="sidebar">
        <a href="calcul.html" class="nav-icon" title="Calculatrice" onclick="showCalculator(event)">
            <i class="fas fa-calculator"></i>
        </a>
        <a href="prod.php" class="nav-icon" title="Panier">
            <i class="fas fa-shopping-cart"></i>
        </a>
        
        <a href="historique.php" class="nav-icon active" title="Historique">
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
    <h1><i class="fas fa-receipt"></i> Historique des Commandes</h1>
    
    <div class="commande-table">
      <table>
        <thead>
          <tr>
            <th><i class="fas fa-hashtag"></i> ID</th>
            <th><i class="fas fa-cube"></i> Produit</th>
            <th><i class="fas fa-sort-amount-up"></i> Quantité</th>
            <th><i class="fas fa-tag"></i> Prix Unitaire</th>
            <th><i class="fas fa-coins"></i> Total</th>
            <th><i class="fas fa-calendar-alt"></i> Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if(empty($commandes)): ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <i class="fas fa-inbox"></i>
                  <h3>Aucune commande trouvée</h3>
                  <p>Votre historique de commandes apparaîtra ici</p>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach($commandes as $commande): ?>
              <tr>
                <td>#<?= htmlspecialchars($commande['id']) ?></td>
                <td><?= htmlspecialchars($commande['product_name']) ?></td>
                <td><?= $commande['quantity'] ?></td>
                <td class="price">
                  <?= number_format($commande['total_price'] / $commande['quantity'], 2) ?> د.ت
                </td>
                <td class="price">
                  <?= number_format($commande['total_price'], 2) ?> د.ت
                </td>
                <td class="date">
                  <?= date('d/m/Y H:i', strtotime($commande['created_at'])) ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    
    <?php if(!empty($commandes)): ?>
      <div class="total-box">
        <i class="fas fa-wallet"></i>
        <div>
          <div style="font-size: 0.9em; opacity: 0.9;">Total Général</div>
          <div style="font-size: 1.4em; font-weight: 600;">
            <?= number_format($totalGeneral, 2) ?> د.ت
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Section des graphiques par produit -->
    <?php if(!empty($chartData)): ?>
      <h2 style="margin:2rem 0; color: var(--primary);">
        <i class="fas fa-chart-line"></i> Graphiques par Produit
      </h2>
      <div class="charts-container">
        <?php foreach($chartData as $index => $data): ?>
          <div class="chart-card">
            <h3><?= htmlspecialchars($data['product']) ?></h3>
            <div class="chart-container">
              <canvas id="chart<?= $index ?>"></canvas>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <script>
    // Transfert des données PHP vers JavaScript
    const produitsData = <?php echo json_encode($chartData); ?>;
    
    produitsData.forEach(function(productData, index) {
      const canvas = document.getElementById('chart' + index);
      if (!canvas) return;
      
      const ctx = canvas.getContext('2d');
      
      // Création d'un dégradé pour le dataset "Quantité"
      const gradientQuantity = ctx.createLinearGradient(0, 0, 0, 400);
      gradientQuantity.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
      gradientQuantity.addColorStop(1, 'rgba(99, 102, 241, 0.1)');

      new Chart(ctx, {
        type: 'line',
        data: {
          labels: productData.dates,
          datasets: [
            {
              label: 'Quantité',
              data: productData.quantities,
              borderColor: '#6366f1',
              backgroundColor: gradientQuantity,
              fill: true,
              tension: 0.4,
              pointRadius: 4,
              pointStyle: 'circle'
            },
            {
              label: 'Total',
              data: productData.totals,
              borderColor: '#f43f5e',
              backgroundColor: 'rgba(244, 67, 54, 0.1)',
              fill: true,
              // Design alternatif : ligne en pointillés et style de point différent
              tension: 0,
              borderDash: [8, 4],
              pointRadius: 6,
              pointStyle: 'triangle'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            // Affichage du total général du produit en titre du graphique
            title: {
              display: true,
              text: 'Total: ' + parseFloat(productData.overallTotal).toFixed(2) + ' د.ت',
              font: {
                size: 16,
                weight: 'bold'
              },
              padding: {
                top: 10,
                bottom: 20
              }
            },
            legend: {
              position: 'top'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  let label = context.dataset.label || '';
                  if (label) {
                    label += ': ';
                  }
                  label += context.parsed.y;
                  if(context.dataset.label === 'Total'){
                    label += ' د.ت';
                  }
                  return label;
                }
              }
            }
          },
          scales: {
            x: {
              title: {
                display: true,
                text: 'Date'
              },
              ticks: {
                autoSkip: true,
                maxTicksLimit: 10
              }
            },
            y: {
              title: {
                display: true,
                text: 'Valeur'
              },
              beginAtZero: true
            }
          }
        }
      });
    });
  </script>
  
</body>
</html>