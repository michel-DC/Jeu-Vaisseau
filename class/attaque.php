<?php

require_once 'vaisseau.php';

class Attaque
{
    private function determinerZone(Vaisseau $vaisseau, $roleJoueur)
    {
        $positionX = $vaisseau->getPosition()['x'];

        if ($roleJoueur === 'joueur1') {
            if ($positionX >= 4) {
                return 'proche';
            } elseif ($positionX >= 2) {
                return 'milieu';
            } else {
                return 'eloignee';
            }
        } else { // joueur2
            if ($positionX <= 1) {
                return 'proche';
            } elseif ($positionX <= 3) {
                return 'milieu';
            } else {
                return 'eloignee';
            }
        }
    }

    public function tirer(Vaisseau $attaquant, Vaisseau $defenseur, $roleJoueurAttaquant)
    {
        $zone = $this->determinerZone($attaquant, $roleJoueurAttaquant);

        $attaquant->setModificateurDegatsInfliges(1.0);
        $attaquant->setModificateurDegatsSubis(1.0);

        switch ($zone) {
            case 'eloignee':
                if ($attaquant->getCooldownAttaqueSpeciale() > 0) {
                    $attaquant->setCooldownAttaqueSpeciale($attaquant->getCooldownAttaqueSpeciale() - 1);
                    return "L'arme spéciale est en rechargement. Tir normal.";
                }
                $degats = $attaquant->getPuissanceDeTir() * 2;
                $defenseur->recevoirDegats($degats);
                $attaquant->setCooldownAttaqueSpeciale(2);
                return "Attaque spéciale depuis la zone éloignée ! Dégâts infligés: " . $degats;

            case 'milieu':
                $degats = $attaquant->getPuissanceDeTir() * $attaquant->getModificateurDegatsInfliges();
                $defenseur->recevoirDegats($degats);
                return "Attaque basique depuis la zone de milieu. Dégâts infligés: " . $degats;

            case 'proche':
                $attaquant->setModificateurDegatsInfliges(0.75);
                $attaquant->setModificateurDegatsSubis(0.75);

                $degats = $attaquant->getPuissanceDeTir() * $attaquant->getModificateurDegatsInfliges();
                $defenseur->recevoirDegats($degats);
                $degatsInfliges = $degats;
                return "Attaque depuis la zone proche. Dégâts infligés réduits, défense améliorée. Dégâts: " . $degatsInfliges;

            default:
                return "Zone inconnue.";
        }
    }
}
