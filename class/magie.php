<?php

require_once 'vaisseau.php';
require_once 'magicien.php';

class Magie
{
    public function lancerSort(Magicien $magicien, Vaisseau $cible, Vaisseau $lanceur)
    {
        if ($magicien->getMana() === 0) {
            $msg = "Votre magicien n'a plus de mana et ne peut pas lancer de sort.";
            return [
                'success' => false,
                'message_lanceur' => $msg,
                'message_cible' => "Le magicien ennemi n'a plus de mana."
            ];
        }

        $puissanceMagie = $magicien->getPuissance();

        if ($cible->getPointDeVie() > (50 * $puissanceMagie)) {
            $msg = "La cible a trop de PV pour que le sort soit efficace (seuil : " . (50 * $puissanceMagie) . " PV).";
            return [
                'success' => false,
                'message_lanceur' => $msg,
                'message_cible' => $msg
            ];
        }

        $magicien->utiliserMana();

        $chance = mt_rand(1, 99);

        $messageLanceur = "";
        $messageCible = "";
        
        if ($chance <= 33) {
            // Sort de poison
            $degatsPoison = 10 * $puissanceMagie;
            $effetPoison = [
                'type' => 'poison',
                'damage' => $degatsPoison,
                'duration' => -1,
            ];
            $cible->appliquerEffet($effetPoison);
            $messageLanceur = "Votre magicien a empoisonné le vaisseau ennemi ! Il subira " . $degatsPoison . " dégâts par tour.";
            $messageCible = "Le magicien ennemi vous a empoisonné ! Vous subirez " . $degatsPoison . " dégâts par tour.";
        } elseif ($chance <= 66) {
            // Sort de soin
            $soinParTour = 10 * $puissanceMagie;
            $effetSoin = [
                'type' => 'soin',
                'amount' => $soinParTour,
                'duration' => -1,
            ];
            $lanceur->appliquerEffet($effetSoin);
            $messageLanceur = "Votre magicien vous soigne ! Vous récupérerez " . $soinParTour . " PV par tour.";
            $messageCible = "Le magicien ennemi se soigne ! Il récupérera " . $soinParTour . " PV par tour.";
        } else {
            // Sort de paralysie
            $chanceParalysie = (1 / 5) * ($puissanceMagie / 5) * 100;
            if (mt_rand(1, 100) <= $chanceParalysie) {
                $effetParalysie = [
                    'type' => 'paralysie',
                    'duration' => 5,
                    'chance' => $chanceParalysie,
                ];
                $cible->appliquerEffet($effetParalysie);
                $messageLanceur = "Votre magicien a paralysé l'ennemi ! Il risque de bloquer son action durant 5 tours.";
                $messageCible = "Le magicien ennemi vous a paralysé ! Vous risquez de bloquer votre action durant 5 tours.";
            } else {
                $messageLanceur = "Votre magicien a tenté une paralysie, mais le sort a échoué.";
                $messageCible = "Le magicien ennemi a tenté de vous paralyser, mais le sort a échoué.";
            }
        }

        return [
            'success' => true,
            'message_lanceur' => $messageLanceur,
            'message_cible' => $messageCible
        ];
    }
}
