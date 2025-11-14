<?php
require_once 'db.php';

$link = connexionDB();

$sql = "
CREATE TABLE IF NOT EXISTS game_state (
    partie_id VARCHAR(255) PRIMARY KEY,
    joueur1_hp INT DEFAULT 1000,
    joueur2_hp INT DEFAULT 1000,
    duree_partie INT DEFAULT 0,
    FOREIGN KEY (partie_id) REFERENCES parties(partie_id) ON DELETE CASCADE
);
";

if (mysqli_query($link, $sql)) {
    echo "Table 'game_state' created successfully or already exists.\n";
} else {
    echo "Error creating table: " . mysqli_error($link) . "\n";
}

mysqli_close($link);
?>