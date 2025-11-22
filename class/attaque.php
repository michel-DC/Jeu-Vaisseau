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
        $degatsInfliges = $puissanceDeTirActuelle;
        
        $degatsInfliges = floor($degatsInfliges);
        $defenseur->recevoirDegats($degatsInfliges);
        $narration = "{$attaquant->getNom()} a infligé {$degatsInfliges} dégâts depuis la zone {$zoneAttaquant} à {$defenseur->getNom()}. {$defenseur->getNom()} a {$defenseur->getPointDeVie()} PV restants.";


        return [
            'message' => $narration,
            'degatsInfliges' => $degatsInfliges,
            'defenseurVieRestante' => $defenseur->getPointDeVie(),
            'attaquantNom' => $attaquant->getNom(),
            'defenseurNom' => $defenseur->getNom()
        ];
    }
}
