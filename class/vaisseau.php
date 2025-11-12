<?php

require_once './personne.php';
require_once './drone.php';

class Vaisseau
{
    private $nom;
    private $equipage = [];
    private $propre = true;
    private $enEtatDeMarche = true;

    private $position;
    private $energie;
    private $puissanceDeTir;
    private $drones = [];

    public function __construct($nom)
    {
        $this->nom = $nom;

        // Initialisation des attributs de jeu
        $this->position = ['x' => 0, 'y' => 0];
        $this->energie = 100;
        $this->puissanceDeTir = 10;
    }

    // --- Méthodes existantes ---

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($nouveauNom)
    {
        $this->nom = $nouveauNom;
    }

    public function estPareADecoller()
    {
        $nb = count($this->equipage);
        if ($nb < 2 || $nb > 10) {
            return false;
        }
        if (!$this->propre) {
            return false;
        }
        if (!$this->enEtatDeMarche) {
            return false;
        }
        return true;
    }

    public function remettreEnEtat()
    {
        $roles = array_map(function ($p) {
            return $p->getRole();
        }, $this->equipage);

        if (!$this->propre) {
            if (!in_array('agent d\'entretien', $roles, true)) {
                return 0;
            }
            $this->propre = true;
        }

        if (!$this->enEtatDeMarche) {
            if (!in_array('mécanicien', $roles, true)) {
                return 0;
            }
            $this->enEtatDeMarche = true;
        }

        return 1;
    }

    public function decoller()
    {
        if (!$this->estPareADecoller()) {
            $ok = $this->remettreEnEtat();
            if ($ok === 0) {
                return 0;
            }
            if (!$this->estPareADecoller()) {
                return 0;
            }
        }

        $aPilote = false;
        foreach ($this->equipage as $personne) {
            if ($personne->getRole() === 'pilote') {
                $aPilote = true;
                break;
            }
        }

        if (!$aPilote) {
            return 0;
        }

        return 1;
    }

    public function affecterMembre($personne)
    {
        if (count($this->equipage) >= 10) {
            return false;
        }
        foreach ($this->equipage as $membre) {
            if ($membre->getNom() === $personne->getNom()) {
                return false;
            }
        }
        $this->equipage[] = $personne;
        return true;
    }

    public function retirerMembre($nom)
    {
        foreach ($this->equipage as $index => $membre) {
            if ($membre->getNom() === $nom) {
                array_splice($this->equipage, $index, 1);
                return true;
            }
        }
        return false;
    }

    public function salir()
    {
        $this->propre = false;
    }

    public function avarier()
    {
        $this->enEtatDeMarche = false;
    }

    public function getEquipage()
    {
        return $this->equipage;
    }

    public function getEnergie()
    {
        return $this->energie;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getDrones()
    {
        return $this->drones;
    }

    public function deplacer($x, $y)
    {
        $this->position['x'] = $x;
        $this->position['y'] = $y;
    }

    public function recevoirDegats($quantite)
    {
        $this->energie -= $quantite;
        if ($this->energie < 0) {
            $this->energie = 0;
        }
    }

    public function tirer($cible)
    {
        $cible->recevoirDegats($this->puissanceDeTir);
    }

    public function lancerDrone($type)
    {
        $coutEnergie = 25;
        if ($this->energie >= $coutEnergie) {
            $this->energie -= $coutEnergie;
            $nouveauDrone = new Drone($type, $this->position);
            $this->drones[] = $nouveauDrone;
            return $nouveauDrone;
        }
        return false;
    }
}
