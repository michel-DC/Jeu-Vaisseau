<?php

class Magicien
{
  private $nom;
  private $mana;
  private $puissance;

  public function __construct($nom, $mana, $puissance)
  {
    $this->nom = $nom;
    $this->mana = $mana;
    $this->puissance = $puissance;
  }

  public function getNom()
  {
    return $this->nom;
  }

  public function getMana()
  {
    return $this->mana;
  }

  public function getPuissance()
  {
    return $this->puissance;
  }

  public function agir()
  {
    return "$this->nom lance un sort avec $this->puissance de puissance.";
  }

  public function mediter()
  {
    $this->mana += 10;
    return "$this->nom médite et récupère du mana. Mana actuel : $this->mana.";
  }
}
