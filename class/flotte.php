<?php

require_once  './vaisseau.php';

class Flotte
{
    private $nom;
    private $vaisseaux = [];

    public function __construct($nom)
    {
        $this->nom = $nom;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function ajouterVaisseau($vaisseau)
    {
        foreach ($this->vaisseaux as $v) {
            if ($v->getNom() === $vaisseau->getNom()) {
                return false;
            }
        }
        $this->vaisseaux[] = $vaisseau;
        return true;
    }

    public function retirerVaisseau($nomVaisseau)
    {
        foreach ($this->vaisseaux as $index => $v) {
            if ($v->getNom() === $nomVaisseau) {
                array_splice($this->vaisseaux, $index, 1);
                return true;
            }
        }
        return false;
    }

    public function getVaisseaux()
    {
        return $this->vaisseaux;
    }
}
