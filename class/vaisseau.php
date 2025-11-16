<?php

require_once './magicien.php';
require_once './drone.php';

class Vaisseau
{
    private $nom;
    private $magicien;

    private $position;
    private $pointDeVie;
    private $puissanceDeTir;
    private $drones = [];

    private $cooldown_attaque_speciale = 0;
    private $modificateur_degats_infliges = 1.0;
    private $modificateur_degats_subis = 1.0;
    private $status_effects = [];

    public function __construct($nom)
    {
        $this->nom = $nom;
        $this->magicien = new Magicien("Merlin", 100, 30);
        $this->position = ['x' => 0, 'y' => 0];
        $this->pointDeVie = 1000;
        $this->puissanceDeTir = 50;
    }

    // --- Getters et Setters ---

    public function getMagicien()
    {
        return $this->magicien;
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

    public function getPointDeVie()
    {
        return $this->pointDeVie;
    }

    public function getPosition()
    {
        return $this->position;
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

    // --- Gestion des effets de statut ---

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

            if (isset($effet['duration']) && $effet['duration'] <= 0) {
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

    public function deplacer($x, $y)
    {
        $this->position['x'] = $x;
        $this->position['y'] = $y;
    }

    public function recevoirDegats($quantite)
    {
        $this->pointDeVie -= $quantite * $this->modificateur_degats_subis;
        if ($this->pointDeVie < 0) {
            $this->pointDeVie = 0;
        }
    }

    public function lancerDrone($type)
    {
        $coutEnergie = 25;
        if ($this->pointDeVie >= $coutEnergie) {
            $this->pointDeVie -= $coutEnergie;
            $nouveauDrone = new Drone($type, $this->position);
            $this->drones[] = $nouveauDrone;
            return $nouveauDrone;
        }
        return false;
    }
}
