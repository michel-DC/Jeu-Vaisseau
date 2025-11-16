<?php

require_once 'vaisseau.php';
require_once 'magicien.php';

class Magie
{
    public function lancerSort(Magicien $magicien, Vaisseau $cible)
    {
        $puissanceMagie = $magicien->getPuissance();
        $chanceSucces = 50 + ($puissanceMagie / 2);

        if (mt_rand(1, 100) > $chanceSucces) {
            return "Le magicien a échoué à canaliser son énergie...";
        }

        $choixEffet = mt_rand(1, 4); // 1/4 chance pour stun, 3/4 pour DoT

        // effet qui empêche l'adversaire de jouer au prochain tour 
        if ($choixEffet === 1) {
            $effetStun = [
                'type' => 'stun',
                'duration' => 1,
            ];
            $cible->appliquerEffet($effetStun);
            return "Le magicien étourdit l'adversaire, l'empêchant de jouer au prochain tour !";
            // effet qui inflige 10 de dégâts pendant 4 tours
        } else {
            $effetDot = [
                'type' => 'dot',
                'damage' => 10,
                'duration' => 4,
            ];
            $cible->appliquerEffet($effetDot);
            return "Le magicien inflige 10 dégâts à l'adversaire pendant 4 tours !";
        }
    }
}
