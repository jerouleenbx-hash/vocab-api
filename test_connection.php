<?php
require 'vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$connectionParams = [
    'dbname' => 'symfony_db',
    'user' => 'symfony',
    'password' => 'symfony',
    'host' => 'db',
    'driver' => 'pdo_mysql',
];

try {
    $conn = DriverManager::getConnection($connectionParams);
    $tables = $conn->fetchAllAssociative('SHOW TABLES');
    echo "Connexion réussie. Tables disponibles :\n";
    print_r($tables);
} catch (\Exception $e) {
    echo "Erreur de connexion : " . $e->getMessage() . "\n";
}
