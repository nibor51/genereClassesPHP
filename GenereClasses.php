<?php

use PDO;
use PDOException;

class GenereClasses
{
    private PDO $pdoConnection;

    private $dbName;

    private $host;

    private $user;

    private $password;

    public function __construct($dbName, $host, $user, $password) {
        $this->dbName = $dbName;
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        try {
            $this->pdoConnection = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->dbName . ';charset=utf8',
                $this->user,
                $this->password
            );
            $this->pdoConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "
                Erreur de connexion à la base de données \n
                Message d'erreur : ".$e->getMessage()." \n
            ";
        }
    }

    private function initialGeneration() {
        // TODO : generate AbstractManager.php and connection.php
    }

    private function getPdoConnection(): PDO
    {
        return $this->pdoConnection;
    }

    public function generateClasses() {
        //connection to database
        $pdo = $this->getPdoConnection();

        //get database schema
        $query = "SELECT * FROM information_schema.columns WHERE table_schema = ?";
        $pdoStatement = $pdo->prepare($query);
        $pdoStatement->execute([$this->dbName]);
        $databaseSchema = $pdoStatement->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        //group columns by table
        $tables = [];
        foreach ($databaseSchema['def'] as $table) {
            $tables[$table['TABLE_NAME']][] = $table;
        }

        //generate classes
        foreach ($tables as $table => $columns) {
            $className = ucfirst($table);
            $class = "<?php\n\nclass $className {\n\n";

            //generate attributes
            $classAttributes = [];
            foreach ($columns as $column) {
                $attributeName = $column['COLUMN_NAME'];
                $attributeType = $this->sqlToPhpType($column['DATA_TYPE']);
                $classAttributes[] = "    private ".$attributeType." $".$attributeName.";";
            }
            $class .= implode("\n\n", $classAttributes);

            //define methods

            //constructor
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
                $class .= "        \$this-> ".$column['COLUMN_NAME']." = $".$column['COLUMN_NAME'].";\n";
            }
            $class .= "    }\n\n";

            foreach ($columns as $column) {
                //getters
                //condition to add return type
                if (strpos($column['DATA_TYPE'], 'int') !== false) {
                    $class .= "    public function get".$column['COLUMN_NAME']."(): ?int {\n";
                } else {
                    $class .= "    public function get".$column['COLUMN_NAME']."(): ?string\n    {\n";
                }
                $class .= "        return \$this->".$column['COLUMN_NAME'].";\n";
                $class .= "    }\n\n";

                //setters
                //condition to add parameter type
                if (strpos($column['DATA_TYPE'], 'int') !== false) {
                    $class .= "    public function set".$column['COLUMN_NAME']."(int $".$column['COLUMN_NAME'].") {\n";
                } else {
                    $class .= "    public function set".$column['COLUMN_NAME']."(string $".$column['COLUMN_NAME'].") {\n";
                }
                $class .= "        \$this->".$column['COLUMN_NAME']." = $".$column['COLUMN_NAME'].";\n";
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
        
            $class .= "}";

            //create directory if not exists
            if (!file_exists('classes')) {
                mkdir('classes');
            }

            //create file
            $file = fopen('classes/'.$className.'.php', 'w+');
            fwrite($file, $class);
            fclose($file);
        }
    }

    private function sqlToPhpType(string $type) : string
    {
        $typeMap = array(
            'TINYINT' => 'int',
            'SMALLINT' => 'int',
            'MEDIUMINT' => 'int',
            'INT' => 'int',
            'BIGINT' => 'int',
            'FLOAT' => 'float',
            'DOUBLE' => 'float',
            'DECIMAL' => 'float',
            'DATE' => 'string',
            'TIME' => 'string',
            'DATETIME' => 'string',
            'TIMESTAMP' => 'int',
            'YEAR' => 'int',
            'CHAR' => 'string',
            'VARCHAR' => 'string',
            'TEXT' => 'string',
            'BLOB' => 'string',
            'ENUM' => 'string',
            'SET' => 'string'
          );
        $pos = strpos($type, '(');
        if ($pos !== false) {
          $type = substr($type, 0, $pos);
        }
        return isset($typeMap[$type]) ? $typeMap[$type] : 'string';	
    }
}