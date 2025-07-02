<?php
// config/bootstrap.php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Charge les variables d'environnement depuis .env
if (file_exists(dirname(__DIR__).'/.env')) {
    (new Dotenv())->loadEnv(dirname(__DIR__).'/.env');
}

// Définit la timezone par défaut (important pour éviter les décalages)
date_default_timezone_set('Europe/Paris');
