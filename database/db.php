<?php

DEFINE('DB_HOST', 'localhost');
DEFINE('DB_USER', 'micheldjoumessi_flow-media');
DEFINE('DB_PASSWORD', 'michouflow');
DEFINE('DB_NAME', 'micheldjoumessi_jeu-vaisseau');

function connexionDB()
{
    $link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    if (!$link) {
        die('Connexion échouée : ' . mysqli_connect_error());
    }

    return $link;
}
