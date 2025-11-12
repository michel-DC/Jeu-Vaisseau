<?php

class Personne
{
    private $nom;
    private $prenom;
    private $age;
    private $pointDeVie;
    private $role;

    public function __construct($nom, $prenom, $age, $role)
    {
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->age = $age;
        $this->pointDeVie = 100;
        $this->role = $role;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function getAge()
    {
        return $this->age;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function sePresenter()
    {
        return "Bonjour, je m'appelle $this->nom $this->prenom et j'ai $this->age ans.";
    }

    public function agir()
    {
        echo "Mais que voulez-vous que je fasse ?";
    }

    public function vieillir()
    {
        $this->age++;
    }
}
