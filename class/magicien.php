<?php

class Magicien
{
  private $nom;
  private $mana;
  private $puissance;

  public function __construct($nom, $mana = 1, $puissance)
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

  public function restaurerMana()
  {
    $this->mana = 1;
  }

  public function utiliserMana()
  {
    $this->mana = 0;
  }
}
