<?php

require_once 'vaisseau.php';
require_once 'magicien.php';

class Magie
{
    public function lancerSort(Magicien $magicien, Vaisseau $cible, Vaisseau $lanceur)
    {
        if ($magicien->getMana() === 0) {
            return "Le magicien n'a plus de mana et ne peut pas lancer de sort.";
        }

        $puissanceMagie = $magicien->getPuissance();

        if ($cible->getPointDeVie() > (50 * $puissanceMagie)) {
            return "La cible a trop de PV pour que le sort soit efficace (seuil : " . (50 * $puissanceMagie) . " PV).";
        }

        $magicien->utiliserMana();

        $chance = mt_rand(1, 99);

        $message = "";
        if ($chance <= 33) {
            
            $degatsPoison = 10 * $puissanceMagie;
            $effetPoison = [
                'type' => 'poison',
                'damage' => $degatsPoison,
                'duration' => -1,
            ];
            $cible->appliquerEffet($effetPoison);
            $message = "Le magicien a lancé un sort de poison ! La cible subira " . $degatsPoison . " dégâts par tour.";
        } elseif ($chance <= 66) {
            
            $soinParTour = 10 * $puissanceMagie;
            $effetSoin = [
                'type' => 'soin',
                'amount' => $soinParTour,
                'duration' => -1,
            ];
            $lanceur->appliquerEffet($effetSoin);
            $message = "Le magicien a lancé un sort de soin ! Le lanceur récupérera " . $soinParTour . " PV par tour.";
        } else {
            
            $chanceParalysie = (1 / 5) * ($puissanceMagie / 5) * 100;
            if (mt_rand(1, 100) <= $chanceParalysie) {
                $effetParalysie = [
                    'type' => 'paralysie',
                    'duration' => 5,
                    'chance' => $chanceParalysie,
                ];
                $cible->appliquerEffet($effetParalysie);
                $message = "Le magicien a lancé un sort de paralysie ! La cible a une chance de bloquer son action durant 5 tours.";
            } else {
                $message = "Le magicien a tenté un sort de paralysie, mais cela n'a pas fonctionné.";
            }
        }

        return $message;
    }
}
