<?php
session_start();
require_once '../database/db.php';

header('Content-Type: application/json');

$partie_id = $_SESSION['partie_id'] ?? $_GET['partie_id'] ?? null;

if (!$partie_id) {
    http_response_code(400);
    echo json_encode(['erreur' => 'ID de partie manquant.']);
    exit();
}

$link = connexionDB();

$sql = "SELECT event_id, message, DATE_FORMAT(timestamp, '%H:%i:%s') as time FROM narration_events WHERE partie_id = ? ORDER BY timestamp ASC";
$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $partie_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
    echo json_encode($events);
} else {
    http_response_code(500);
    echo json_encode(['erreur' => 'Impossible de récupérer les événements de narration.']);
}

mysqli_close($link);
?>
