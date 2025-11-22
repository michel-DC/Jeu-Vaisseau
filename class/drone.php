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

    public function getType()
    {
        return $this->type;
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
        if ($this->type === 'attaque') {
            $chance = mt_rand(1, 100);
            if ($chance <= 50) {
                $vaisseauLanceur->setModificateurDegatsInfliges(1.5);
                return "Votre drone d'attaque a trouvé une faille ! Votre prochaine attaque infligera 1.5x dégâts.";
            } elseif ($chance <= 70) {
                $vaisseauLanceur->setModificateurDegatsInfliges(1.0);
                return "Votre drone d'attaque n'a rien trouvé d'exceptionnel. Attaque normale.";
            } else {
                $vaisseauLanceur->setModificateurDegatsInfliges(2.0);
                return "Votre drone d'attaque a trouvé une énorme faille ! Votre prochaine attaque infligera 2x dégâts.";
            }
        } elseif ($this->type === 'reconnaissance') {
            $chance = mt_rand(1, 100);
            $message = "";

            $vieActuelle = $vaisseauLanceur->getPointDeVie();
            $maxVie = 1000;

            if ($vieActuelle >= $maxVie) {
                
                $adjustedChance = mt_rand(1, 2);
                if ($adjustedChance === 1) {
                    $puissanceMagicien = mt_rand(2, 5);
                    $nouveauMagicien = new Magicien("Nouveau Sage", 1, $puissanceMagicien);
                    $vaisseauLanceur->setMagicienActuel($nouveauMagicien);
                    $message = "Votre drone de reconnaissance a trouvé un magicien plus puissant (Puissance: " . $puissanceMagicien . ") ! Il remplace votre ancien magicien.";
                } else {
                    $nouvellePuissanceTir = mt_rand(80, 170);
                    $vaisseauLanceur->setPuissanceDeTir($nouvellePuissanceTir);
                    $message = "Votre drone a trouvé un meilleur canon ! Votre puissance de tir est maintenant de " . $nouvellePuissanceTir . ".";
                }
            } else {
                if ($chance <= 33) {
                    $puissanceMagicien = mt_rand(2, 5);
                    $nouveauMagicien = new Magicien("Nouveau Sage", 1, $puissanceMagicien);
                    $vaisseauLanceur->setMagicienActuel($nouveauMagicien);
                    $message = "Votre drone de reconnaissance a trouvé un magicien plus puissant (Puissance: " . $puissanceMagicien . ") ! Il remplace votre ancien magicien.";
                } elseif ($chance <= 66) {
                    $nouvellePuissanceTir = mt_rand(80, 170);
                    $vaisseauLanceur->setPuissanceDeTir($nouvellePuissanceTir);
                    $message = "Votre drone a trouvé un meilleur canon ! Votre puissance de tir est maintenant de " . $nouvellePuissanceTir . ".";
                } else {
                    $soin = floor($maxVie / 10);
                    $vaisseauLanceur->recevoirSoins($soin);
                    $message = "Votre drone a trouvé une étoile de soin ! Votre vaisseau a récupéré " . $soin . " points de vie.";
                }
            }
            return $message;
        }
        return "Le drone ne peut pas effectuer d'action.";
    }
}
