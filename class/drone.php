<?php

require_once 'vaisseau.php';
require_once 'magicien.php';

class Drone
{
    private $type;
    private $energie;
    private $portee;
    private $position;
    private $puissance;

    public function __construct($type, $positionInitiale)
    {
        $this->type = $type;
        $this->position = $positionInitiale;

        if ($type === 'attaque') {
            $this->energie = 50;
            $this->portee = 5;
            $this->puissance = 15;
        } elseif ($type === 'reconnaissance') {
            $this->energie = 30;
            $this->portee = 10;
            $this->puissance = 0;
        } else {
            $this->type = 'standard';
            $this->energie = 40;
            $this->portee = 7;
            $this->puissance = 5;
        }
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function recevoirDegats($quantite)
    {
        $this->energie -= $quantite;
        if ($this->energie < 0) {
            $this->energie = 0;
        }
    }
    public function agir(Vaisseau $vaisseauLanceur, Vaisseau $cible = null)
    {
        if ($this->type === 'attaque' && $cible) {
            // Chance de trouver une faille
            $chance = mt_rand(1, 100);
            if ($chance <= 30) { // 30% de chance
                $cible->setModificateurDegatsSubis(1.5); // Prochaine attaque subira 50% de dégâts en plus
                return "Le drone d'attaque a trouvé une faille ! La prochaine attaque infligera plus de dégâts.";
            } else {
                // Attaque normale du drone
                $cible->recevoirDegats($this->puissance);
                return "Le drone d'attaque tire sur la cible ! Dégâts infligés: " . $this->puissance;
            }
        } elseif ($this->type === 'reconnaissance') {
            $chance = mt_rand(1, 100);
            if ($chance <= 10) { // 10% de chance de trouver une source d'énergie
                $vaisseauLanceur->recevoirSoins(50); // Soigne de 50
                return "Le drone de reconnaissance a trouvé une source d'énergie pure ! Votre vaisseau a récupéré 50 points d'énergie.";
            } elseif ($chance <= 25) { // 15% de chance de trouver un canon (10+15)
                $nouvellePuissance = $vaisseauLanceur->getPuissanceDeTir() + 5;
                $vaisseauLanceur->setPuissanceDeTir($nouvellePuissance);
                return "Le drone a trouvé un meilleur canon ! Votre puissance de tir est augmentée de 5.";
            } elseif ($chance <= 45) { // 20% de chance de trouver une trousse de soin (25+20)
                $vaisseauLanceur->recevoirSoins(20);
                return "Le drone a trouvé une trousse de soin ! Votre vaisseau a récupéré 20 points d'énergie.";
            } else {
                return "Le drone de reconnaissance n'a rien trouvé d'intéressant.";
            }
        }
        return "Le drone ne peut pas effectuer d'action.";
    }
}
