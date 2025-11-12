<?php
include_once './personne.php';

class Mentaliste extends Personne
{
    private $mana;

    public function __construct($nom, $prenom, $age)
    {
        parent::__construct($nom, $prenom, $age, "mentaliste");
        $this->mana = 100;
    }

    public function sePresenter()
    {
        return "Je suis un mentaliste de " . $this->getAge() . " ans, il me reste $this->mana de mana.";
    }

    public function vieillir()
    {
        parent::vieillir();
    }

    public function agir()
    {
        echo "Le mentaliste est prêt à agir, mais qui influencer ? <br>";
    }

    public function influencer($personne)
    {
        if ($this->mana >= 20) {
            $nomPersonne = $personne->getNom();
            echo "Le mentaliste influence " . $nomPersonne . " qui agit maintenant ! <br>";
            $this->mana -= 20;
            $personne->agir();
        } else {
            echo "Pas assez de mana pour influencer ! <br>";
        }
    }

    public function getMana()
    {
        return $this->mana;
    }
}
