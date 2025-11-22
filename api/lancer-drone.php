<?php
require_once '../database/db.php';
require_once '../class/vaisseau.php';
require_once '../class/drone.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée. Seul POST est accepté.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$partie_id_from_client = $data['partie_id'] ?? null;
$joueur_id = $data['joueur_id'] ?? null;
$drone_type = $data['drone_type'] ?? null; // 'attaque' ou 'reconnaissance'

if (!$partie_id_from_client || !$joueur_id || !$drone_type) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Données manquantes (partie_id, joueur_id, drone_type).']);
    exit();
}

$partie_id_session = $_SESSION['partie_id'] ?? null;
if (!$partie_id_session || $partie_id_from_client !== $partie_id_session) {
    http_response_code(403);
    echo json_encode(['erreur' => 'Session invalide ou ID de partie incorrect.']);
    exit();
}

$link = connexionDB();


// 1. Récupérer l'état du jeu
$sql_game_state = "SELECT p.joueur1_id, p.joueur2_id, gs.* FROM parties p JOIN game_state gs ON p.partie_id = gs.partie_id WHERE p.partie_id = ?";
$stmt = mysqli_prepare($link, $sql_game_state);
mysqli_stmt_bind_param($stmt, "s", $partie_id_session);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$game_state = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$game_state) {
    http_response_code(404);
    echo json_encode(['erreur' => 'Partie introuvable.']);
    mysqli_close($link);
    exit();
}

// 2. Vérifier le tour et le joueur
$joueur_role = '';
if ($game_state['joueur1_id'] === $joueur_id) {
    $joueur_role = 'joueur1';
} elseif ($game_state['joueur2_id'] === $joueur_id) {
    $joueur_role = 'joueur2';
} else {
    http_response_code(403);
    echo json_encode(['erreur' => 'Joueur non reconnu.']);
    mysqli_close($link);
    exit();
}

if ($game_state['joueur_actuel'] !== $joueur_id) {
    http_response_code(403);
    echo json_encode(['erreur' => "Ce n'est pas votre tour."]);
    mysqli_close($link);
    exit();
}

if ($game_state[$joueur_role . '_action_faite'] == 1) {
    http_response_code(403);
    echo json_encode(['erreur' => 'Vous avez déjà effectué une action ce tour.']);
    mysqli_close($link);
    exit();
}

// 3. Reconstituer le vaisseau et ses drones
$vaisseau = new Vaisseau($joueur_role); // Nom arbitraire, on va setter les stats
$vaisseau->setPointDeVie($game_state[$joueur_role . '_hp']);
$vaisseau->setPuissanceDeTir($game_state[$joueur_role . '_puissance_tir']);
$vaisseau->setMaxPuissanceDeTir($game_state[$joueur_role . '_max_puissance_tir']);
$vaisseau->setModificateurDegatsInfliges(1.0); // Reset par défaut, sera modifié par le drone si besoin

// Gestion des drones stockés en JSON dans la BDD
$drones_json = $game_state[$joueur_role . '_drones'];
$drones_data = $drones_json ? json_decode($drones_json, true) : null;

// Si pas de drones en BDD, on initialise avec ceux par défaut du constructeur Vaisseau (cas début de partie ou migration)
// MAIS attention, si la colonne vient d'être créée, elle est NULL.
// Le constructeur de Vaisseau crée déjà des drones.
// Si $drones_data est null, on suppose que c'est les drones par défaut du constructeur.
// SAUF SI c'est une partie en cours où on a déjà tout utilisé...
// Pour simplifier : si NULL, on prend ceux du constructeur. Si tableau vide [], plus de drones.
$drones_objets = [];
if ($drones_data === null) {
    // Utiliser les drones par défaut du vaisseau
    $drones_objets = $vaisseau->getDrones();
} else {
    // Reconstituer les objets Drone depuis le JSON
    foreach ($drones_data as $d) {
        $drones_objets[] = new Drone($d['type'], $vaisseau->getPosition());
    }
    // On force la liste de drones du vaisseau avec celle de la BDD
    // Il n'y a pas de setter pour les drones dans Vaisseau, c'est un problème.
    // On va devoir modifier la classe Vaisseau ou ruser.
    // Ruse: on va gérer la logique de choix ici et juste mettre à jour la BDD.
}

// 4. Trouver un drone du type demandé
$drone_index = -1;
foreach ($drones_objets as $index => $drone) {
    // On accède à la propriété type via reflection ou on suppose que le constructeur l'a bien mis.
    // La classe Drone a une propriété privée $type. On ne peut pas la lire directement sans getter.
    // Ajoutons une méthode getType() à Drone ou utilisons la reflection.
    // Attend, Drone a agir() qui utilise le type.
    // On va supposer qu'on peut identifier le type.
    // Ah, dans le constructeur Drone: $this->type = $type;
    // Il n'y a pas de getter pour le type dans le code fourni !
    // On va devoir modifier la classe Drone pour ajouter getType() ou public $type.
    // Pour l'instant, supposons qu'on modifie Drone.php.
    
    // WAIT: Le JSON stocke le type.
    $type_courant = '';
    if ($drones_data !== null) {
        $type_courant = $drones_data[$index]['type'];
    } else {
        // Si on vient du constructeur, on sait l'ordre : 2 reco, 1 attaque.
        if ($index < 2) $type_courant = 'reconnaissance';
        else $type_courant = 'attaque';
    }

    if ($type_courant === $drone_type) {
        $drone_index = $index;
        break;
    }
}

if ($drone_index === -1) {
    http_response_code(400);
    echo json_encode(['erreur' => "Vous n'avez plus de drone de type $drone_type."]);
    mysqli_close($link);
    exit();
}

// 5. Lancer le drone
$drone_utilise = $drones_objets[$drone_index];
// Retirer le drone de la liste
array_splice($drones_objets, $drone_index, 1);

// Exécuter l'action
$message_resultat = $drone_utilise->agir($vaisseau);

// Ajouter le rôle du joueur au début du message pour personnalisation côté client
$message_resultat = "DRONE:{$joueur_role}:" . $message_resultat;

// 6. Mettre à jour la BDD
// Sérialiser les drones restants
$nouveaux_drones_data = [];
foreach ($drones_objets as $d) {
    // On a besoin du type pour le sauvegarder.
    // Comme on n'a pas de getter, on se base sur ce qu'on savait.
    // C'est fragile. Il FAUT modifier la classe Drone pour avoir getType().
    // Je vais ajouter getType() à la classe Drone dans une étape séparée.
    // Pour ce script, je vais assumer que j'ai ajouté getType().
    $nouveaux_drones_data[] = ['type' => $d->getType()]; 
}
$nouveaux_drones_json = json_encode($nouveaux_drones_data);

// Préparer l'update
$sql_update = "UPDATE game_state SET 
    {$joueur_role}_drones = ?, 
    {$joueur_role}_hp = ?, 
    {$joueur_role}_puissance_tir = ?, 
    {$joueur_role}_action_faite = 1,
    joueur_actuel = ?,
    {$joueur_role}_action_faite = 0, 
    joueur1_action_faite = 0, 
    joueur2_action_faite = 0,
    joueur1_a_bouge = 0, 
    joueur2_a_bouge = 0
    WHERE partie_id = ?";

// NOTE: Le changement de tour est immédiat après une action offensive ?
// Dans PRD: "Cinq actions disponibles : attaquer, se déplacer, magie, drone, recharger."
// "Jeu en tour par tour où chaque joueur ne peut réaliser qu'une seule action offensive par tour."
// Donc oui, lancer un drone termine le tour (sauf déplacement).
$joueur_suivant_id = ($joueur_role === 'joueur1') ? $game_state['joueur2_id'] : $game_state['joueur1_id'];

// ATTENTION: Si le drone donne un buff pour la PROCHAINE attaque, il faut le stocker !
// Le PRD dit: "Drone d'attaque : buff x1.5 sur la prochaine attaque".
// Il faut une colonne pour stocker ce modificateur temporaire ou l'appliquer direct à la puissance ?
// "puissance de tir" est stockée. Mais le buff est un multiplicateur.
// Vaisseau a $modificateur_degats_infliges. Il faut le persister !
// Il n'y a pas de colonne pour ça dans game_state actuel (vu dans attaquer-vaisseau.php).
// Je dois ajouter une colonne `joueurX_modificateur_degats` aussi ?
// Ou alors j'applique le buff directement à la puissance de tir si c'est permanent ?
// PRD: "sur la prochaine attaque". Donc c'est temporaire.
// Si je ne peux pas changer le schéma trop, je peux peut-être utiliser une colonne existante ou en ajouter une autre.
// J'ai déjà ajouté des colonnes drones. Je vais ajouter `joueurX_prochain_multiplicateur` dans le script de schema.

// Update SQL avec les nouvelles valeurs
// J'ai besoin de mettre à jour le schéma pour le multiplicateur aussi.

$stmt_update = mysqli_prepare($link, "UPDATE game_state SET 
    {$joueur_role}_drones = ?, 
    {$joueur_role}_hp = ?, 
    {$joueur_role}_puissance_tir = ?, 
    joueur_actuel = ?,
    joueur1_action_faite = 0,
    joueur2_action_faite = 0,
    joueur1_a_bouge = 0,
    joueur2_a_bouge = 0
    WHERE partie_id = ?");

// Wait, if I change turn, I reset action_faite.
// But I also need to persist the damage modifier if it's an attack drone.
// Let's assume I'll add `joueurX_damage_multiplier` to DB.

// ... (I will update the schema script first to include this)

// For now, let's finalize the PHP logic assuming columns exist.
// But wait, `agir()` returns a message string. It modifies the $vaisseau object state.
// $vaisseau->getModificateurDegatsInfliges() will have the new value.

// I need to persist this modifier.
// Let's add `joueur1_damage_multiplier` and `joueur2_damage_multiplier` (FLOAT, default 1.0).

$nouv_hp = $vaisseau->getPointDeVie();
$nouv_puissance = $vaisseau->getPuissanceDeTir();
// $nouv_modif = $vaisseau->getModificateurDegatsInfliges(); // Need to save this

// Let's update the SQL to include the multiplier.
// And I need to update `attaquer-vaisseau.php` to USE this multiplier and reset it to 1.0 after attack.

// This is getting complicated. Let's stick to the plan but ensure DB supports it.
// I will update the `update_db_schema.php` content in the next tool call to include these new columns.

// Back to this file content. I'll write a placeholder for now and update it after I fix the schema script.
// Actually, I can write the final version now if I'm sure about the schema.

$stmt_update = mysqli_prepare($link, "UPDATE game_state SET 
    {$joueur_role}_drones = ?, 
    {$joueur_role}_hp = ?, 
    {$joueur_role}_puissance_tir = ?, 
    {$joueur_role}_damage_multiplier = ?,
    joueur_actuel = ?,
    joueur1_action_faite = 0,
    joueur2_action_faite = 0,
    joueur1_a_bouge = 0,
    joueur2_a_bouge = 0
    WHERE partie_id = ?");

$nouv_modif = $vaisseau->getModificateurDegatsInfliges();

mysqli_stmt_bind_param($stmt_update, "siidss", 
    $nouveaux_drones_json, 
    $nouv_hp, 
    $nouv_puissance, 
    $nouv_modif,
    $joueur_suivant_id, 
    $partie_id_session
);

if (mysqli_stmt_execute($stmt_update)) {
    echo json_encode([
        'success' => true, 
        'message' => $message_resultat,
        'drone_type' => $drone_type
    ]);
} else {
    http_response_code(500);
    echo json_encode(['erreur' => 'Erreur BDD: ' . mysqli_error($link)]);
}

mysqli_stmt_close($stmt_update);
mysqli_close($link);
?>
