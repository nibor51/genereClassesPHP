<?php
// Path: GenereClasses.php

require_once '_connec.php';
// connexion database
try {
    $pdo = new \PDO(DSN, USER, PASS);
} catch (\PDOException $e) {
    echo "
        Erreur de connexion à la base de données \n
        Vérifiez les paramètres de connexion dans le fichier _connec.php \n
    ";
}

//récupération du schéma de la database
$query = "SHOW TABLES";
$pdoStatement = $pdo->query($query);
$tables = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

// parcours des tables
foreach ($tables as $table) {
    echo $table['Tables_in_'.DBNAME]."\n";

    // récupération du schéma de la table

    // debut boucle sur chaque colonne d'une table

        // génération de la classe
        
    // fin boucle sur chaque colonne d'une table

    //ajout des methodes
    // création fichier class

}