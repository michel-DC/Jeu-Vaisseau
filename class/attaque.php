<?php

require_once 'vaisseau.php';

class Attaque
{
    private function determinerZone(Vaisseau $vaisseau)
    {
        $position = $vaisseau->getPosition();

        switch ($position) {
            case 1:
                return 'proche';
            case 2:
                return 'milieu';
            case 3:
                return 'eloignee';
            default:
                return 'inconnue';
        }
    }



    public function tirer(Vaisseau $attaquant, Vaisseau $defenseur)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $partieId = $_SESSION['partie_id'] ?? 'partie_inconnue';
        $zoneAttaquant = $this->determinerZone($attaquant);
        $zoneDefenseur = $this->determinerZone($defenseur);

        $puissanceDeTirActuelle = $attaquant->getPuissanceDeTir();
        $modificateur = $attaquant->getModificateurDegatsInfliges();

        // Zone multipliers described in the spec:
        // - Attacker éloigné (3) : 1.5x damage and +1 extra turn cooldown on "tirer"
        // - Attacker milieu (2) : 1.0x damage
        // - Attacker proche (1) : 0.75x damage
        // - Defender proche (1) : takes 0.75x of incoming damage
        $attackerZoneFactor = 1.0;
        switch ($zoneAttaquant) {
            case 'eloignee':
                $attackerZoneFactor = 1.5;
                break;
            case 'milieu':
                $attackerZoneFactor = 1.0;
                break;
            case 'proche':
                $attackerZoneFactor = 0.75;
                break;
        }

        $defenderZoneFactor = 1.0;
        // Defender in 'proche' receives less damage; defender in 'eloignee' receives more damage
        if ($zoneDefenseur === 'proche') {
            $defenderZoneFactor = 0.75;
        } elseif ($zoneDefenseur === 'eloignee') {
            $defenderZoneFactor = 1.5;
        }

        // Calculate base damage before defender's zone/subis modifiers
        $baseDamage = $puissanceDeTirActuelle * $modificateur * $attackerZoneFactor;
        $baseDamage = floor($baseDamage);

        // Figure out final damage considering defender zone and their damage-subis modifier
        $degatsSubisEstimes = floor($baseDamage * $defenderZoneFactor * $defenseur->getModificateurDegatsSubis());

        // Apply damage to defender (recevoirDegats applies modificateur_degats_subis internally)
        $defenseur->recevoirDegats($baseDamage, $defenderZoneFactor);

        $narration = "Vous avez infligé {$degatsSubisEstimes} dégâts à l'adversaire.";

        // Add informational narration depending on zones
        if ($zoneAttaquant === 'eloignee') {
            $narration .= " (Tir proche : dégâts x1.5.)";
        } elseif ($zoneAttaquant === 'proche') {
            $narration .= " (Tir éloigné : dégâts réduits à 75%.)";
        }

        if ($zoneDefenseur === 'eloignee') {
            $narration .= " (Défenseur en proche : dégâts reçus x1.5.)";
        } elseif ($zoneDefenseur === 'proche') {
            $narration .= " (Défenseur en éloigné : dégâts reçus x0.75.)";
        }


        return [
            'message' => $narration,
            'degatsInfliges' => $degatsSubisEstimes,
            'defenseurVieRestante' => $defenseur->getPointDeVie(),
            'attaquantNom' => $attaquant->getNom(),
            'defenseurNom' => $defenseur->getNom()
        ];
    }
}
