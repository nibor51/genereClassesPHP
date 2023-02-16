<?php
// Path: GenereClasses.php

require_once '_connec.php';

// connexion database
try {
    $pdo = new \PDO(DB_DSN, DB_USER, DB_PASSWORD);
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
    // mise en variable de la table
    $table = $table['Tables_in_'.DB_NAME];
    // récupération du schéma de la table
    $query = "DESCRIBE ".$table;
    $pdoStatement = $pdo->query($query);
    $columns = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

    // génération de la classe
    $className = ucfirst($table);
    $class = "<?php\n\nclass $className {\n\n";

    // définition des attributs
    foreach ($columns as $column) {
        $type = $column['Type'];
        $pos = strpos($type, '(');
        if ($pos !== false) {
            $type = substr($type, 0, $pos);
        }
        if (strpos($type, 'int') !== false) {
            $class .= "    private int $".$column['Field'].";\n\n";
        } else {
            $class .= "    private string $".$column['Field'].";\n\n";
        }
    }

    // définition des méthodes

    // constructeur
    $class .= "    public function __construct(";
    $i = 0;
    foreach ($columns as $column) {
        $class .= "$".$column['Field'];
        if ($i < count($columns) - 1) {
            $class .= ", ";
        }
        $i++;
    }
    $class .= ") {\n";

    foreach ($columns as $column) {
        $class .= "        \$this->".$column['Field']." = $".$column['Field'].";\n";
    }
    $class .= "    }\n\n";


    foreach ($columns as $column) {
        // getters
        // condition pour ajouter le type de retour
        if (strpos($column['Type'], 'int') !== false) {
            $class .= "    public function get".$column['Field']."(): ?int {\n";
        } else {
            $class .= "    public function get".$column['Field']."(): ?string\n    {\n";
        }
        $class .= "        return \$this->".$column['Field'].";\n";
        $class .= "    }\n\n";
    // setters
    // setters
    foreach ($columns as $column) {
        // setters
    foreach ($columns as $column) {
        // ne pas generer de setter pour l'id
        if ($column['Field'] == 'id') {
            continue;
        }
        // condition pour ajouter le type de retour
        if (strpos($column['Type'], 'int') !== false) {
            $class .= "    public function set".$column['Field']."(int $".$column['Field']."): self\n    {\n";
        } else {
            $class .= "    public function set".$column['Field']."(string $".$column['Field']."): self\n    {\n";
        }
        $class .= "        \$this->".$column['Field']." = $".$column['Field'].";\n\n";
        $class .= "        return \$this;\n";
        $class .= "    }\n\n";
    }

    // méthode select
    $class .= "    public static function select(\$pdo, \$id) {\n";
    $class .= "        \$query = \"SELECT * FROM ".$table." WHERE id = '\$id'\";\n";
    $class .= "        \$pdoStatement = \$pdo->query(\$query);\n";
    $class .= "        \$result = \$pdoStatement->fetchObject('".ucfirst($table)."');\n";
    $class .= "        return \$result;\n";
    $class .= "    }\n\n";
    
    // méthode selectAll
    $class .= "    public static function selectAll(\$pdo) {\n";
    $class .= "        \$query = \"SELECT * FROM ".$table."\";\n";
    $class .= "        \$pdoStatement = \$pdo->query(\$query);\n";
    $class .= "        \$results = \$pdoStatement->fetchAll(PDO::FETCH_CLASS, '".ucfirst($table)."');\n";
    $class .= "        return \$results;\n";
    $class .= "    }\n\n";

    // méthode add
    $class .= "    public static function add(\$pdo, ";
    $i = 0;
    foreach ($columns as $column) {
        $class .= "$".$column['Field'];
        if ($i < count($columns) - 1) {
            $class .= ", ";
        }
        $i++;
    }
    $class .= ") {\n";
    $class .= "        \$query = \"INSERT INTO ".$table." VALUES (";
    $i = 0;
    foreach ($columns as $column) {
        $class .= "'$".$column['Field']."'";
        if ($i < count($columns) - 1) {
            $class .= ", ";
        }
        $i++;
    }
    $class .= ")\";\n";
    $class .= "        \$pdoStatement = \$pdo->query(\$query);\n";
    $class .= "        return \$pdoStatement;\n";
    $class .= "    }\n\n";
    
    // méthode delete
    $class .= "    public static function delete(\$pdo, \$id) {\n";
    $class .= "        \$query = \"DELETE FROM ".$table." WHERE id = '\$id'\";\n";
    $class .= "        \$pdoStatement = \$pdo->query(\$query);\n";
    $class .= "        return \$pdoStatement;\n";
    $class .= "    }\n\n";

    // méthode update
    $class .= "    public static function update(\$pdo, \$id, ";
    $i = 0;
    foreach ($columns as $column) {
        // ne pas duppliquer l'id
        if ($column['Field'] == 'id') {
            continue;
        }
        $class .= "$".$column['Field'];
        if ($i < count($columns) - 1) {
            $class .= ", ";
        }
        $i++;
    }
    $class .= ") {\n";
    $class .= "        \$query = \"UPDATE ".$table." SET ";
    $i = 0;
    foreach ($columns as $column) {
        $class .= $column['Field']." = '$".$column['Field']."'";
        if ($i < count($columns) - 1) {
            $class .= ", ";
        }
        $i++;
    }
    $class .= " WHERE id = '\$id'\";\n";
    $class .= "        \$pdoStatement = \$pdo->query(\$query);\n";
    $class .= "        return \$pdoStatement;\n";
    $class .= "    }\n\n";

    // méthode search
    $class .= "    public static function search(\$pdo, \$search) {\n";
    $class .= "        \$query = \"SELECT * FROM ".$table." WHERE \";\n";
    $i = 0;
    foreach ($columns as $column) {
        $class .= "        \$query .= \"".$column['Field']." LIKE '%\$search%'\";\n";
        if ($i < count($columns) - 1) {
            $class .= "        \$query .= \" OR \";\n";
        }
        $i++;
    }
    $class .= "        \$pdoStatement = \$pdo->query(\$query);\n";
    $class .= "        \$results = \$pdoStatement->fetchAll(PDO::FETCH_CLASS, '".ucfirst($table)."');\n";
    $class .= "        return \$results;\n";
    $class .= "    }\n\n";
    

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