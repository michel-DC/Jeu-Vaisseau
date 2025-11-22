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
        $zoneAttaquant = $this->determinerZone($attaquant);
        $zoneDefenseur = $this->determinerZone($defenseur);

        $degatsBase = $attaquant->getPuissanceDeTir();
        $degatsInfliges = $degatsBase;
        $message = "";

        // Apply damage modification based on consecutive attacks
        if ($attaquant->getAttaquesConsecutivesSansTirer() === 0) {
            // Fired in previous turn, reduce damage
            $attaquant->setPuissanceDeTir(max(50, $attaquant->getPuissanceDeTir() - 20)); // Min damage 50
            $degatsInfliges = $attaquant->getPuissanceDeTir();
        } else {
            // Did not fire in previous turn, increase damage
            $attaquant->setPuissanceDeTir(min($attaquant->getMaxPuissanceDeTir(), $attaquant->getPuissanceDeTir() + 20));
            $degatsInfliges = $attaquant->getPuissanceDeTir();
        }
        $attaquant->setAttaquesConsecutivesSansTirer(0); // Reset for next turn

        switch ($zoneAttaquant) {
            case 'eloignee': // x=3
                if ($attaquant->getCooldownAttaqueSpeciale() > 0) {
                    $attaquant->setCooldownAttaqueSpeciale($attaquant->getCooldownAttaqueSpeciale() - 1);
                    $message = "L'arme spéciale est en rechargement. Tir normal.";
                } else {
                    $degatsInfliges *= 1.5;
                    $attaquant->setCooldownAttaqueSpeciale(1); // One turn recharge
                    $message = "Attaque spéciale depuis la zone éloignée ! Dégâts infligés: ";
                }
                break;
            case 'milieu': // x=2
                $degatsInfliges *= 1;
                $message = "Attaque basique depuis la zone de milieu. Dégâts infligés: ";
                break;
            case 'proche': // x=1
                $degatsInfliges *= 0.75;
                $message = "Attaque depuis la zone proche. Dégâts infligés réduits: ";
                break;
        }

        // Adjust damage if defender is in 'proche' zone (x=1)
        if ($zoneDefenseur === 'proche') {
            $degatsInfliges *= 0.75;
            $message .= "(Défenseur en zone proche, dégâts réduits.) ";
        }

        $defenseur->recevoirDegats($degatsInfliges);
        return $message . $degatsInfliges;
    }
}
