<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée.']);
    exit();
}

$partie_id = $_SESSION['partie_id'] ?? null;
if (!$partie_id) {
    http_response_code(400);
    echo json_encode(['erreur' => 'ID de partie non trouvé dans la session.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'] ?? null;

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Message de narration manquant.']);
    exit();
}

$link = connexionDB();

$sql = "INSERT INTO narration_events (partie_id, message) VALUES (?, ?)";
$stmt = mysqli_prepare($link, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $partie_id, $message);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['succes' => 'Événement de narration ajouté.']);
    } else {
        http_response_code(500);
        echo json_encode(['erreur' => 'Erreur lors de l\'enregistrement de l\'événement.']);
    }
    mysqli_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur de préparation de la requête.']);
}

mysqli_close($link);
?>
