<?php
require_once '../class/vaisseau.php';

function gerer_debut_tour($link, $partie_id, $joueur_id) {
    // 1. Fetch current state of the player
    $sql = "SELECT p.joueur1_id, p.joueur2_id, gs.joueur1_hp, gs.joueur2_hp, gs.joueur1_effets, gs.joueur2_effets FROM parties p JOIN game_state gs ON p.partie_id = gs.partie_id WHERE p.partie_id = ?";
    $stmt = mysqli_prepare($link, $sql);
    
    if (!$stmt) {
        error_log("Erreur prepare gerer_debut_tour: " . mysqli_error($link));
        return;
    }

    mysqli_stmt_bind_param($stmt, "s", $partie_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$data) return;

    $role = ($data['joueur1_id'] === $joueur_id) ? 'joueur1' : 'joueur2';
    
    // 2. Setup Vaisseau
    $vaisseau = new Vaisseau($joueur_id);
    $vaisseau->setPointDeVie($data[$role . '_hp']);
    $effets = $data[$role . '_effets'] ? json_decode($data[$role . '_effets'], true) : [];
    if (!is_array($effets)) $effets = [];
    $vaisseau->setStatusEffects($effets);
    
    // Use a generic name or fetch it if needed, but 'Vaisseau' is fine for internal logic.
    // The class Vaisseau uses $nom in messages.
    $vaisseau->setNom($role === 'joueur1' ? 'Joueur 1' : 'Joueur 2'); 

    // 3. Apply effects
    $resultat = $vaisseau->gererEffetsDebutTour();
    
    // 4. Update DB if changes or just to update durations
    // Even if no messages, durations might have changed (decremented).
    // So we should always update if there were effects.
    if (!empty($effets) || !empty($resultat['messages'])) {
        $new_hp = $vaisseau->getPointDeVie();
        $new_effets = json_encode($vaisseau->getStatusEffects());
        
        $sql_update = "UPDATE game_state SET {$role}_hp = ?, {$role}_effets = ? WHERE partie_id = ?";
        $stmt_up = mysqli_prepare($link, $sql_update);
        if ($stmt_up) {
            mysqli_stmt_bind_param($stmt_up, "iss", $new_hp, $new_effets, $partie_id);
            mysqli_stmt_execute($stmt_up);
            mysqli_stmt_close($stmt_up);
        } else {
             error_log("Erreur prepare update gerer_debut_tour: " . mysqli_error($link));
        }

        // 5. Add narration
        if (!empty($resultat['messages'])) {
            $sql_narr = "INSERT INTO narration_events (partie_id, message) VALUES (?, ?)";
            $stmt_narr = mysqli_prepare($link, $sql_narr);
            if ($stmt_narr) {
                foreach ($resultat['messages'] as $msg) {
                    // Prefix with SYSTEM or similar to indicate it's a game event
                    $full_msg = "EFFECT:" . $msg; 
                    mysqli_stmt_bind_param($stmt_narr, "ss", $partie_id, $full_msg);
                    mysqli_stmt_execute($stmt_narr);
                }
                mysqli_stmt_close($stmt_narr);
            }
        }
    }
    
    return $resultat;
}
