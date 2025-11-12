<?php

include_once './personne.php';

class Operateur extends Personne
{
    private $metier;
    private $experience;

    public function __construct($nom, $prenom, $age, $metier)
    {
        parent::__construct($nom, $prenom, $age, $metier);
        $this->metier = $metier;
        $this->experience = 0;
    }

    public function sePresenter()
    {
        return parent::sePresenter() . " et je suis un opérateur $this->metier de " . $this->getAge() . " ans, j'ai $this->experience points d'expérience.";
    }

    public function vieillir()
    {
        parent::vieillir();
    }

    public function agir()
    {
        switch ($this->metier) {
            case 'pilote':
                echo "Le vaisseau est en train de décoller !";
                break;
            case 'agent d\'entretien':
                echo "Le vaisseau est nettoyé !";
                break;
            case 'mécanicien':
                echo "Les réparations sont effectuées !";
                break;
            case 'navigateur':
                echo "La trajectoire est calculée !";
                break;
            default:
                echo "L'opérateur effectue ses tâches !";
        }
        $this->experience += 5;
    }

    public function getMetier()
    {
        return $this->metier;
    }

    public function getExperience()
    {
        return $this->experience;
    }
}
