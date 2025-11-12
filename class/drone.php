<?php

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

    public function agir($cible = null)
    {
        if ($this->type === 'attaque' && $cible) {
            if (method_exists($cible, 'recevoirDegats')) {
                echo "Le drone d'attaque tire sur la cible !<br>";
                $cible->recevoirDegats($this->puissance);
                return true;
            }
        } elseif ($this->type === 'reconnaissance') {
            echo "Le drone de reconnaissance scanne la zone.<br>";
            return $cible ? $cible->getPosition() : true;
        }
        return false;
    }
}
