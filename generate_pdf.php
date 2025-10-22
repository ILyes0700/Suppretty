<?php
require_once(__DIR__ . '/vendor/autoload.php');

// Connexion à la base de données
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
    die("Connexion échouée : " . $e->getMessage());
}

if (!isset($_GET['card_id'])) {
    die("ID de la carte manquant !");
}

$card_id = intval($_GET['card_id']);

$stmt = $pdo->prepare("SELECT * FROM cards WHERE id = :id");
$stmt->execute(['id' => $card_id]);
$card = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM responsable WHERE id_res = :id_res");
$stmt->execute(['id_res' => $id_res]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$card) {
    die("Carte introuvable !");
}

$stmt = $pdo->prepare("SELECT * FROM items WHERE card_id = :card_id");
$stmt->execute(['card_id' => $card_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Création du PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('Sbasty System');
$pdf->SetAuthor('Sbasty');
$pdf->SetTitle($card['title']);
$pdf->SetSubject('Devis / Facture');

$pdf->SetMargins(15, 25, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// Palette noir et blanc
$colors = [
    'primary'   => [0, 0, 0],
    'secondary' => [100, 100, 100],
    'accent'    => [50, 50, 50],
    'success'   => [0, 0, 0],
    'light'     => [255, 255, 255],
    'border'    => [200, 200, 200],
    'darkText'  => [0, 0, 0],
    'lightText' => [100, 100, 100],
];

$dinar_symbol = " DT";

// En-tête
$pdf->SetFillColor(...$colors['light']);
$pdf->SetTextColor(...$colors['primary']);
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 12, strtoupper($card['title']), 0, 1, 'C', 0);
$pdf->SetFont('dejavusans', '', 10);
$pdf->Cell(0, 6, 'Sbasty Détailé', 0, 1, 'C', 0);
$pdf->Ln(5);

// Ligne séparatrice
$pdf->SetDrawColor(...$colors['border']);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(8);

// Infos de base
$pdf->SetTextColor(...$colors['darkText']);
$pdf->SetFont('dejavusans', '', 9);
$pdf->SetFillColor(248, 248, 248);
$pdf->SetDrawColor(...$colors['border']);
$pdf->SetLineWidth(0.3);
$pdf->Cell(0, 20, '', 1, 1, 'C', true);
$pdf->SetY($pdf->GetY() - 20);
$pdf->Cell(65, 6, 'Date: ' . date('d/m/Y'), 0, 0, 'L');
$pdf->Cell(65, 6, 'Heure: ' . date('H:i'), 0, 0, 'C');
$pdf->Cell(0, 6, 'Sbasty System', 0, 1, 'C');
$pdf->Ln(10);

// En-tête du tableau
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->SetFillColor(230, 230, 230);
$pdf->SetTextColor(...$colors['primary']);
$pdf->SetDrawColor(...$colors['border']);

$widths = [85, 25, 35, 40];
$headers = ['PRODUIT ', 'QTE', 'PRIX UNIT.', 'TOTAL'];
foreach ($headers as $i => $header) {
    $pdf->Cell($widths[$i], 9, $header, 1, 0, 'C', true);
}
$pdf->Ln();

// Lignes d'items
$pdf->SetFont('dejavusans', '', 9);
$total = 0;
$row = 0;

foreach ($items as $item) {
    $fill = ($row++ % 2 == 0) ? [255, 255, 255] : [245, 245, 245];
    $pdf->SetFillColor(...$fill);
    $pdf->SetTextColor(...$colors['darkText']);

    $pdf->Cell($widths[0], 8, htmlspecialchars($item['text']), 'LR', 0, 'L', true);
    $pdf->Cell($widths[1], 8, $item['quantity'], 'LR', 0, 'C', true);
    $pdf->Cell($widths[2], 8, number_format($item['unit_price'], 2) . $dinar_symbol, 'LR', 0, 'R', true);

    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell($widths[3], 8, number_format($item['price_total'], 2) . $dinar_symbol, 'LR', 1, 'R', true);
    $pdf->SetFont('dejavusans', '', 9);

    $total += $item['price_total'];
}

$pdf->SetDrawColor(...$colors['border']);
$pdf->Cell(array_sum($widths), 0, '', 'T');
$pdf->Ln(12);

// Total général
$pdf->SetFont('dejavusans', 'B', 11);
$pdf->SetTextColor(...$colors['primary']);
$pdf->Cell(145, 8, 'TOTAL GÉNÉRAL:', 0, 0, 'R');

$pdf->SetFont('dejavusans', 'B', 12);
$pdf->SetTextColor(...$colors['accent']);
$pdf->Cell(0, 8, number_format($total, 2) . $dinar_symbol, 0, 1, 'R');

// Remarques
if (!empty($card['description']) || !empty($card['notes'])) {
    $pdf->Ln(10);
    $pdf->SetTextColor(...$colors['darkText']);
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(0, 6, 'Remarques:', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->SetTextColor(...$colors['lightText']);
    $notes = $card['description'] ?: $card['notes'];
    $pdf->MultiCell(0, 5, $notes, 0, 'L');
}

// Pied de page
$pdf->Ln(15);
$pdf->SetDrawColor(...$colors['border']);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
$pdf->Ln(3);

$pdf->SetTextColor(...$colors['lightText']);
$pdf->SetFont('dejavusans', '', 8);
$pdf->Cell(95, 4, $user['nom_ent'], 0, 0, 'L');
$pdf->Cell(95, 4, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 1, 'R');
$pdf->Cell(95, 4, 'Tel: +216 XX XXX XXX', 0, 0, 'L');
$pdf->Cell(95, 4, date('d/m/Y H:i'), 0, 1, 'R');

// Générer le nom du fichier
$filename = 'Sbasit_' . preg_replace('/[^a-zA-Z0-9]/', '_', $card['title']) . '_' . date('Y-m-d_H-i-s') . '.pdf';
$file_path = __DIR__ . '/uploads/sbasty/' . $filename;

// Créer le dossier s'il n'existe pas
if (!is_dir(__DIR__ . '/uploads/sbasty')) {
    mkdir(__DIR__ . '/uploads/sbasty', 0777, true);
}

// Sauvegarder le PDF sur le serveur
$pdf->Output($file_path, 'F');

// Stocker les informations dans la base de données
try {
    // Récupérer l'ID du client depuis la carte (supposant que card a un champ id_client)
    $id_client = $card['id'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO sbasty_files (id_res, id_client, sabsty, date_ajoute) VALUES (:id_res, :id_client, :sabsty, NOW())");
    $stmt->execute([
        'id_res' => $id_res,
        'id_client' => $id_client,
        'sabsty' => $filename
    ]);
    
    // Télécharger le fichier après l'avoir sauvegardé
    $pdf->Output($filename, 'D');
    
} catch (PDOException $e) {
    // En cas d'erreur, télécharger quand même le fichier
    $pdf->Output($filename, 'D');
    // Vous pouvez logger l'erreur si nécessaire
    error_log("Erreur lors de l'enregistrement dans la base de données: " . $e->getMessage());
}
?>