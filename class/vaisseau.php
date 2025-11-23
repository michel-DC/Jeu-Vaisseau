<?php

require_once __DIR__ . '/magicien.php';
require_once __DIR__ . '/drone.php';

class Vaisseau
{
    private $nom;
    private $magicienActuel;

    private $position;
    private $pointDeVie;
    private $puissanceDeTir;
    private $maxPuissanceDeTir;
    private $attaquesConsecutivesSansTirer;
    private $drones = [];

    private $cooldown_attaque_speciale = 0;
    private $modificateur_degats_infliges = 1.0;
    private $modificateur_degats_subis = 1.0;
    private $status_effects = [];

    public function __construct($nom)
    {
        $this->nom = $nom;
        $this->magicienActuel = new Magicien("Merlin", 1, 1);
        $this->position = 2;
        $this->pointDeVie = 1000;
        $this->puissanceDeTir = 100;
        $this->maxPuissanceDeTir = 100;
        $this->attaquesConsecutivesSansTirer = 0;
        $this->drones[] = new Drone('reconnaissance', $this->position);
        $this->drones[] = new Drone('reconnaissance', $this->position);
        $this->drones[] = new Drone('attaque', $this->position);
    }

    

    public function setMagicienActuel($magicien)
    {
        $this->magicienActuel = $magicien;
    }

    public function getMagicienActuel()
    {
        return $this->magicienActuel;
    }

    public function getCooldownAttaqueSpeciale()
    {
        return $this->cooldown_attaque_speciale;
    }

    public function setCooldownAttaqueSpeciale($cooldown)
    {
        $this->cooldown_attaque_speciale = $cooldown;
    }

    public function getModificateurDegatsInfliges()
    {
        return $this->modificateur_degats_infliges;
    }

    public function setModificateurDegatsInfliges($modificateur)
    {
        $this->modificateur_degats_infliges = $modificateur;
    }

    public function getModificateurDegatsSubis()
    {
        return $this->modificateur_degats_subis;
    }

    public function setModificateurDegatsSubis($modificateur)
    {
        $this->modificateur_degats_subis = $modificateur;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($nouveauNom)
    {
        $this->nom = $nouveauNom;
    }

    public function setPointDeVie($pointDeVie)
    {
        $this->pointDeVie = $pointDeVie;
    }

    public function getPointDeVie()
    {
        return $this->pointDeVie;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getMaxPuissanceDeTir()
    {
        return $this->maxPuissanceDeTir;
    }

    public function setMaxPuissanceDeTir($maxPuissanceDeTir)
    {
        $this->maxPuissanceDeTir = $maxPuissanceDeTir;
    }

    public function getAttaquesConsecutivesSansTirer()
    {
        return $this->attaquesConsecutivesSansTirer;
    }

    public function setAttaquesConsecutivesSansTirer($attaquesConsecutivesSansTirer)
    {
        $this->attaquesConsecutivesSansTirer = $attaquesConsecutivesSansTirer;
    }

    public function getDrones()
    {
        return $this->drones;
    }

    public function getPuissanceDeTir()
    {
        return $this->puissanceDeTir;
    }

    public function setPuissanceDeTir($nouvellePuissance)
    {
        $this->puissanceDeTir = $nouvellePuissance;
    }

    public function getStatusEffects()
    {
        return $this->status_effects;
    }

    public function setStatusEffects($effects)
    {
        $this->status_effects = $effects;
    }

    

    public function appliquerEffet($effet)
    {
        $this->status_effects[] = $effet;
    }

    public function gererEffetsDebutTour()
    {
        $peutJouer = true;
        $messages = [];

        foreach ($this->status_effects as $key => &$effet) {
            if (isset($effet['type'])) {
                switch ($effet['type']) {
                    case 'poison':
                        $this->recevoirDegats($effet['damage']);
                        $messages[] = $this->getNom() . " subit " . $effet['damage'] . " dégâts de poison.";
                        break;
                    case 'soin':
                        $this->recevoirSoins($effet['amount']);
                        $messages[] = $this->getNom() . " récupère " . $effet['amount'] . " PV grâce au sort de soin.";
                        break;
                    case 'paralysie':
                        if (mt_rand(1, 100) <= $effet['chance']) {
                            $peutJouer = false;
                            $messages[] = $this->getNom() . " est paralysé et ne peut pas jouer ce tour !";
                        }
                        if (isset($effet['duration']) && $effet['duration'] > 0) {
                            $effet['duration']--;
                        }
                        break;
                    case 'dot':
                        $this->recevoirDegats($effet['damage']);
                        $messages[] = $this->getNom() . " subit " . $effet['damage'] . " dégâts de poison.";
                        break;
                    case 'stun':
                        if (mt_rand(1, 100) <= $effet['chance']) {
                            $peutJouer = false;
                            $messages[] = $this->getNom() . " est étourdi et ne peut pas jouer ce tour !";
                        }
                        if (isset($effet['duration'])) {
                            $effet['duration']--;
                        }
                        break;
                }
            }

            if (isset($effet['duration']) && $effet['duration'] <= 0 && $effet['duration'] != -1) {
                unset($this->status_effects[$key]);
            }
        }

        $this->status_effects = array_values($this->status_effects);

        return ['peut_jouer' => $peutJouer, 'messages' => $messages];
    }

    public function recevoirSoins($quantite)
    {
        $this->pointDeVie += $quantite;
        if ($this->pointDeVie > 1000) {
            $this->pointDeVie = 1000;
        }
    }

    public function deplacer($direction)
    {
        $nouvellePosition = $this->position;
        if ($direction === 'gauche') {
            $nouvellePosition--;
        } elseif ($direction === 'droite') {
            $nouvellePosition++;
        }

        if ($nouvellePosition >= 1 && $nouvellePosition <= 3) {
            $this->position = $nouvellePosition;
            return "Déplacement réussi. Nouvelle position : " . $this->position;
        }
        return "Déplacement impossible. Le vaisseau est déjà à la limite ou la direction est invalide.";
    }

    public function recevoirDegats($quantite)
    {
        $this->pointDeVie -= $quantite * $this->modificateur_degats_subis;
        if ($this->pointDeVie < 0) {
            $this->pointDeVie = 0;
        }
    }

    public function recharger()
    {
        $this->magicienActuel->restaurerMana();
        $this->drones[] = new Drone('reconnaissance', $this->position);
        $this->drones[] = new Drone('attaque', $this->position);
        return "Le vaisseau a été rechargé. Mana du magicien restaurée et 1 drone de reconnaissance et 1 drone d'attaque ajoutés.";
    }

    public function lancerDrone($index)
    {
        if (isset($this->drones[$index])) {
            $drone = $this->drones[$index];
            unset($this->drones[$index]);
            $this->drones = array_values($this->drones);
            return $drone;
        }
        return false;
    }
}
