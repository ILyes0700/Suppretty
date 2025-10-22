<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['id_res'])) {
    echo json_encode([
        'id_res' => $_SESSION['id_res'],
        'responsable_nom' => $_SESSION['responsable_nom'],
        'nom_ent' => $_SESSION['nom_ent']
    ]);
} else {
    echo json_encode(['error' => 'Non connecté']);
}
?>