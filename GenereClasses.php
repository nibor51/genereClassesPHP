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
    $query = "DESCRIBE ".$table['Tables_in_'.DBNAME];
    $pdoStatement = $pdo->query($query);
    $columns = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

    // génération de la classe
    $className = ucfirst($table['Tables_in_'.DBNAME]);
    $class = "<?php \nclass $className { \n";

    // définition des attributs
    foreach ($columns as $column) {
        // TODO vérification sur le type de la colone pour verifier si c'est un int ou un string
        // $class .= "    private ".$column['Type']." $".$column['Field'].";\n";
        $class .= "    private $".$column['Field'].";\n";
    }
    // définition des méthodes
    // actuellement get et set uniquement
    foreach ($columns as $column) {
        // getter
        $class .= "    public function get".ucfirst($column['Field'])."() {\n";
        $class .= "        return \$this->".$column['Field'].";\n";
        $class .= "    }\n";
        // setter
        $class .= "    public function set".ucfirst($column['Field'])."($".$column['Field'].") {\n";
        $class .= "        \$this->".$column['Field']." = $".$column['Field'].";\n";
        $class .= "    }\n";
    }
    // fin de la class
    $class .= "}";
    //verification de la class
    // echo $class;

    //création du dossier classes
    if (!file_exists('classes')) {
        mkdir('classes', 0777, true);
    }
    // création fichier class
    $file = fopen('classes/'.$className.'.php', 'w+');
    fwrite($file, $class);
    fclose($file);

}