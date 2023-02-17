<?php
// Path: GenereClasses.php

require_once '_connec.php';
require_once 'sqlToPhpType.php';

// connexion database
try {
    $pdo = new \PDO(DB_DSN, DB_USER, DB_PASSWORD);
    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    echo "
        Erreur de connexion à la base de données \n
        Message d'erreur : ".$e->getMessage()." \n
        Vérifiez les paramètres de connexion dans le fichier _connec.php \n
    ";
    exit();
}

//récupération du schéma de la database
$query = "SELECT * FROM information_schema.columns WHERE table_schema = ?";
$pdoStatement = $pdo->prepare($query);
$pdoStatement->execute([DB_NAME]);
$databaseSchema = $pdoStatement->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

// regroupement des colonnes par table
$tables = [];
foreach ($databaseSchema['def'] as $table) {
    $tables[$table['TABLE_NAME']][] = $table;
}

// génération des classes
foreach ($tables as $table => $columns) {
    $className = ucfirst($table);
    $class = "<?php\n\nclass $className {\n\n";

    // génération des attributs
    $classAttributes = [];
    foreach ($columns as $column) {
        $type = $column['DATA_TYPE'];
        $attributeName = $column['COLUMN_NAME'];
        $attributeType = sqlToPhpType($type);
        $classAttributes[] = "    private ".$attributeType." $".$attributeName.";";
    }
    $class .= implode("\n\n", $classAttributes);

    // définition des méthodes

    // constructeur
    $class .= "\n\n    public function __construct(";
    $i = 0;
    foreach ($columns as $column) {
        $class .= "$".$column['COLUMN_NAME'];
        if ($i < count($columns) - 1) {
            $class .= ", ";
        }
        $i++;
    }
    $class .= ") {\n";

    foreach ($columns as $column) {
        $class .= "        \$this->".$column['COLUMN_NAME']." = $".$column['COLUMN_NAME'].";\n";
    }
    $class .= "    }\n\n";


    foreach ($columns as $column) {
        // getters
        // condition pour ajouter le type de retour
        if (strpos($column['DATA_TYPE'], 'int') !== false) {
            $class .= "    public function get".$column['COLUMN_NAME']."(): ?int {\n";
        } else {
            $class .= "    public function get".$column['COLUMN_NAME']."(): ?string\n    {\n";
        }
        $class .= "        return \$this->".$column['COLUMN_NAME'].";\n";
        $class .= "    }\n\n";
        // setters
        // ne pas generer de setter pour l'id
        if ($column['COLUMN_NAME'] == 'id') {
            continue;
        }
        // condition pour ajouter le type de retour
        if (strpos($column['DATA_TYPE'], 'int') !== false) {
            $class .= "    public function set".$column['COLUMN_NAME']."(int $".$column['COLUMN_NAME']."): self\n    {\n";
        } else {
            $class .= "    public function set".$column['COLUMN_NAME']."(string $".$column['COLUMN_NAME']."): self\n    {\n";
        }
        $class .= "        \$this->".$column['COLUMN_NAME']." = $".$column['COLUMN_NAME'].";\n\n";
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
        $class .= "$".$column['COLUMN_NAME'];
        if ($i < count($columns) - 1) {
            $class .= ", ";
        }
        $i++;
    }
    $class .= ") {\n";
    $class .= "        \$query = \"INSERT INTO ".$table." VALUES (";
    $i = 0;
    foreach ($columns as $column) {
        $class .= "'$".$column['COLUMN_NAME']."'";
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
        if ($column['COLUMN_NAME'] == 'id') {
            continue;
        }
        $class .= "$".$column['COLUMN_NAME'];
        if ($i < count($columns) - 1) {
            $class .= ", ";
        }
        $i++;
    }
    $class .= ") {\n";
    $class .= "        \$query = \"UPDATE ".$table." SET ";
    $i = 0;
    foreach ($columns as $column) {
        $class .= $column['COLUMN_NAME']." = '$".$column['COLUMN_NAME']."'";
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
        $class .= "        \$query .= \"".$column['COLUMN_NAME']." LIKE '%\$search%'\";\n";
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