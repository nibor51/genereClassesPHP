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

foreach ($tables as $table) {
    // récupération du schéma de la table
    $query = "DESCRIBE ".$table['Tables_in_'.DBNAME];
    $pdoStatement = $pdo->query($query);
    $columns = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

    // génération de la classe
    $className = ucfirst($table['Tables_in_'.DBNAME]);
    $class = "<?php\n\nclass $className {\n\n";

    // définition des attributs
    foreach ($columns as $column) {
        // TODO vérification sur le type de la colone pour verifier si c'est un int ou un string
        // $class .= "    private ".$column['Type']." $".$column['Field'].";\n";
        $class .= "    private $".$column['Field'].";\n\n";
    }

    // définition des méthodes
    // TODO ajouter des méthodes tel que Ajouter, Modifier, Supprimer, etc...
    foreach ($columns as $column) {
        // getter
        $class .= "    public function get".ucfirst($column['Field'])."() {\n";
        $class .= "        return \$this->".$column['Field'].";\n";
        $class .= "    }\n\n";
        // setter
        $class .= "    public function set".ucfirst($column['Field'])."($".$column['Field'].") {\n";
        $class .= "        \$this->".$column['Field']." = $".$column['Field'].";\n";
        $class .= "    }\n\n";
    }

    // fin de la class
    $class .= "}";

    //création du dossier classes
    if (!file_exists('classes')) {
        mkdir('classes');
    }

    // création des fichiers class
    $file = fopen('classes/'.$className.'.php', 'w+');
    fwrite($file, $class);
    fclose($file);

}