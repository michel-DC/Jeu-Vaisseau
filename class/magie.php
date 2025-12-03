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

        // Use mana immediately (the magician loses its mana once the spell is cast)
        $magicien->utiliserMana();

        $puissance = max(1, (int)$puissanceMagie);

        // 1) Execution rule: if target HP <= 50 * puissance => instant kill
        if ($cible->getPointDeVie() <= (50 * $puissance)) {
            $cible->setPointDeVie(0);
            $messageLanceur = "Votre magicien exécute le vaisseau ennemi (seuil : " . (50 * $puissance) . " PV) !";
            $messageCible = "Vous avez été exécuté par la magie ennemie !";
            return [
                'success' => true,
                'message_lanceur' => $messageLanceur,
                'message_cible' => $messageCible
            ];
        }

        // Otherwise choose one of three spells (roughly 1/3 each)
        $rand = mt_rand(1, 100);
        $messageLanceur = "";
        $messageCible = "";

        // Helper: inspect existing effects on target and lancer to decide replacement
        $targetEffects = $cible->getStatusEffects() ?: [];
        if (!is_array($targetEffects)) $targetEffects = [];

        $lanceurEffects = $lanceur->getStatusEffects() ?: [];
        if (!is_array($lanceurEffects)) $lanceurEffects = [];

        // keep this simple and compatible across PHP versions (no scalar type hints)
        $findEffectIndex = function($effects, $type) {
            foreach ($effects as $idx => $eff) {
                if (isset($eff['type']) && $eff['type'] === $type) return $idx;
            }
            return null;
        };

        if ($rand <= 33) {
            // Poison: 10 damage per turn * puissance, until end of game
            $idx = $findEffectIndex($targetEffects, 'poison');
            $degatsPoison = 10 * $puissance;
            $effetPoison = [
                'type' => 'poison',
                'damage' => $degatsPoison,
                'duration' => -1,
                'puissance' => $puissance
            ];

            if ($idx === null) {
                $cible->appliquerEffet($effetPoison);
                $messageLanceur = "Votre magicien a empoisonné le vaisseau ennemi : -" . $degatsPoison . " PV chaque tour (jusqu'à la fin).";
                $messageCible = "Vous êtes empoisonné ! Vous subirez -" . $degatsPoison . " PV chaque tour.";
            } else {
                // Target already has a poison: replace only when new puissance is higher
                $existant = $targetEffects[$idx];
                $existantPower = isset($existant['puissance']) ? (int)$existant['puissance'] : 1;
                if ($puissance > $existantPower) {
                    // Replace existing effect entry on target
                    $existingEffects = $cible->getStatusEffects() ?: [];
                    $existingEffects[$idx] = $effetPoison;
                    $cible->setStatusEffects(array_values($existingEffects));
                    $messageLanceur = "Votre magicien remplace un poison plus faible : nouvelle perte de -" . $degatsPoison . " PV chaque tour.";
                    $messageCible = "Un nouveau poison plus puissant vous affecte : -" . $degatsPoison . " PV chaque tour.";
                } else {
                    $messageLanceur = "Votre magicien n'a pas réussi à remplacer le poison existant (puissance trop faible).";
                    $messageCible = "Le magicien ennemi a essayé de vous empoisonner, mais votre poison actuel est plus puissant.";
                }
            }
        } elseif ($rand <= 66) {
            // Heal (applied to lancer): 10 HP per turn * puissance, until end of game
            $soinParTour = 10 * $puissance;
            $effetSoin = [
                'type' => 'soin',
                'amount' => $soinParTour,
                'duration' => -1,
                'puissance' => $puissance
            ];

            $idxL = $findEffectIndex($lanceurEffects, 'soin');
            if ($idxL === null) {
                $lanceur->appliquerEffet($effetSoin);
                $messageLanceur = "Votre magicien lance un sort de soin : +" . $soinParTour . " PV chaque tour (pour vous).";
                $messageCible = "Le magicien ennemi se soigne (il récupérera +" . $soinParTour . " PV par tour).";
            } else {
                $existing = $lanceurEffects[$idxL];
                $existingPower = isset($existing['puissance']) ? (int)$existing['puissance'] : 1;
                if ($puissance > $existingPower) {
                    $existingEffects = $lanceur->getStatusEffects() ?: [];
                    $existingEffects[$idxL] = $effetSoin;
                    $lanceur->setStatusEffects(array_values($existingEffects));
                    $messageLanceur = "Votre magicien remplace un sort de soin plus faible : vous récupérerez maintenant +" . $soinParTour . " PV par tour.";
                    $messageCible = "Le magicien ennemi a amélioré son soin — il récupèrera maintenant +" . $soinParTour . " PV par tour.";
                } else {
                    $messageLanceur = "Votre sort de soin n'a pas remplacé l'effet existant (puissance trop faible).";
                    $messageCible = "Le magicien ennemi a tenté de se soigner mais un soin plus fort est déjà actif.";
                }
            }
        } else {
            // Paralysis: duration 5 turns, each of these turns there's a chance to block the action
            $idxP = $findEffectIndex($targetEffects, 'paralysie');
            if ($idxP !== null) {
                $existant = $targetEffects[$idxP];
                $existantPower = isset($existant['puissance']) ? (int)$existant['puissance'] : (($existant['chance'] ?? 0) / 5);
                if ($puissance > $existantPower) {
                    // we'll replace below
                } else {
                    $messageLanceur = "Votre magicien n'a pas remplacé la paralysie existante (puissance trop faible).";
                    $messageCible = "Le magicien ennemi a tenté de vous paralyser, mais vous avez déjà une paralysie plus forte.";
                    // Avoid adding a new effect or replacing
                    return [
                        'success' => true,
                        'message_lanceur' => $messageLanceur,
                        'message_cible' => $messageCible
                    ];
                }
            }

            // Per-turn block chance scales with puissance: base 20% at puissance=1 up to 40% at puissance=5
            $chancePerTurn = 0.2 + (max(0, $puissance - 1) * 0.05);
            $chancePercent = (int)round(min(0.95, $chancePerTurn) * 100); // cap at 95%

            $effetParalysie = [
                'type' => 'paralysie',
                'duration' => 5,
                'chance' => $chancePercent,
                'puissance' => $puissance
            ];
            if ($idxP === null) {
                $cible->appliquerEffet($effetParalysie);
            } else {
                $existingEffects = $cible->getStatusEffects() ?: [];
                $existingEffects[$idxP] = $effetParalysie;
                $cible->setStatusEffects(array_values($existingEffects));
            }
            $messageLanceur = "Votre magicien a infligé une paralysie : chaque tour, l'ennemi a " . $chancePercent . "% de chance de ne pas pouvoir attaquer pendant 5 tours.";
            $messageCible = "Vous êtes visé par une paralysie : chaque tour, vous avez " . $chancePercent . "% de chance de rater votre action pendant 5 tours.";
        }

        return [
            'success' => true,
            'message_lanceur' => $messageLanceur,
            'message_cible' => $messageCible
        ];
    }

}
