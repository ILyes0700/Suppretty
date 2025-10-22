<?php
header("Content-Type: application/json");
require 'db_connect.php';

$noteId = $_GET['id'] ?? '';

try {
    $stmt = $conn->prepare("DELETE FROM notess WHERE id = ?");
    $stmt->execute([$noteId]);
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}