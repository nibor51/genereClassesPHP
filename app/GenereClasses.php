<?php

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

    public function generateClasses() {
        // Connection to database
        $pdo = $this->pdoConnection;

        // Create the initial files if not exist (connection.php, AbstractManager.php, _connect.php.dist)
        $this->initialGeneration();
        
        // Get database schema
        $query = "SELECT * FROM information_schema.columns WHERE table_schema = ?";
        $pdoStatement = $pdo->prepare($query);
        $pdoStatement->execute([$this->dbName]);
        $databaseSchema = $pdoStatement->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

        // Group columns by table
        $tables = [];
        foreach ($databaseSchema['def'] as $table) {
            $tables[$table['TABLE_NAME']][] = $table;
        }

        // Generate classes
        foreach ($tables as $table => $columns) {
            $className = ucfirst($table) . "Manager";
            $class = "<?php\n\n";
            $class .= "require_once 'AbstractManager.php';\n\n";
            $class .= "class " . $className . " extends AbstractManager\n{\n";
        
            // Generate attributes, getters and setters
            $classAttributes = "";
            $gettersAndSetters = "";

            foreach ($columns as $column) {
                $attributeName = $column['COLUMN_NAME'];
                $attributeType = $this->sqlToPhpType($column['DATA_TYPE']);

                // Generate attributes
                $classAttributes .= sprintf("    private %s $%s;\n\n", $attributeType, $attributeName);

                // Generate getters
                $gettersAndSetters .= sprintf("    public function get%s(): ?%s {\n", ucfirst($attributeName), $attributeType);
                $gettersAndSetters .= sprintf("        return \$this->%s;\n", $attributeName);
                $gettersAndSetters .= "    }\n\n";

                // Generate setters without id
                if ($attributeName == 'id') {
                    continue;
                }
                $gettersAndSetters .= sprintf("    public function set%s(%s $%s) {\n", ucfirst($attributeName), $attributeType, $attributeName);
                $gettersAndSetters .= sprintf("        \$this->%s = $%s;\n", $attributeName, $attributeName);
                $gettersAndSetters .= "    }\n\n";
            }

            // Combine attributes, getters and setters
            $class .= "    public const TABLE = '$table';\n\n";
            $class .= $classAttributes;
            $class .= $gettersAndSetters;

            // Generate add method
            $class .= "    public function add(";
            $parameters = [];
            foreach ($columns as $column) {
                if ($column['COLUMN_NAME'] == 'id') {
                    continue;
                }
                $class .= $this->sqlToPhpType($column['DATA_TYPE']) . " $".$column['COLUMN_NAME'].", ";
                $parameters[] = $column['COLUMN_NAME'];
            }
            $class = rtrim($class, ", ");
            $class .= ") {\n";
            $class .= "        \$statement = \$this->pdo->prepare(\"INSERT INTO \" . self::TABLE . \"(";
            $class .= implode(", ", $parameters);
            $class .= ") VALUES (";
            $class .= ":".implode(", :", $parameters);
            $class .= ")\");\n";
            foreach ($parameters as $parameter) {
                $class .= "        \$statement->bindValue('".$parameter."', $".$parameter.", PDO::PARAM_STR);\n";
            }
            $class .= "        \$statement->execute();\n";
            $class .= "        return (int)\$this->pdo->lastInsertId();\n";
            $class .= "    }\n\n";

            // Generate update method
            $class .= "    public function update(int \$id, ";
            $parameters = [];
            foreach ($columns as $column) {
                if ($column['COLUMN_NAME'] == 'id') {
                    continue;
                }
                $class .= $this->sqlToPhpType($column['DATA_TYPE']) . " $".$column['COLUMN_NAME'].", ";
                $parameters[] = $column['COLUMN_NAME'];
            }
            $class = rtrim($class, ", ");
            $class .= ") {\n";
            $class .= "        \$statement = \$this->pdo->prepare(\"UPDATE \" . self::TABLE . \" SET ";
            $i = 0;
            foreach ($parameters as $parameter) {
                $class .= $parameter." = :".$parameter;
                if ($i < count($parameters) - 1) {
                    $class .= ", ";
                }
                $i++;
            }
            $class .= " WHERE id = :id\");\n";
            foreach ($parameters as $parameter) {
                $class .= "        \$statement->bindValue('".$parameter."', $".$parameter.", PDO::PARAM_STR);\n";
            }
            $class .= "        \$statement->bindValue('id', \$id, PDO::PARAM_INT);\n";
            $class .= "        return \$statement->execute();\n";
            $class .= "    }\n\n";
        
            // Generate search method
            $class .= "    public function search(mixed \$search): array|false {\n";
            $class .= "        \$statement = \$this->pdo->prepare(\"SELECT * FROM \" . self::TABLE . \" WHERE ";
            $conditions = [];
            foreach ($columns as $column) {
                if ($column['COLUMN_NAME'] == 'id') {
                    continue;
                }
                $conditions[] = $column['COLUMN_NAME'] . " LIKE :search";
            }
            $class .= implode(" OR ", $conditions) . "\");\n";
            $class .= "        \$statement->bindValue(':search', '%' . \$search . '%', PDO::PARAM_STR);\n";
            $class .= "        \$statement->execute();\n";
            $class .= "        return \$statement->fetchAll();\n";
            $class .= "    }\n\n";
        
            $class .= "}";

            // Create file
            $file = fopen('src/Model/'.$className.'.php', 'w+');
            fwrite($file, $class);
            fclose($file);
        }
    }

    private function initialGeneration() {
        // Create a folder for config files
        if(!file_exists('config')) {
            mkdir('config');
        }
        // Generate config.php
        if(!file_exists('config/config.php')) {
            $config = fopen('config/config.php', 'w');
            $content = "<?php\n\n";
            $content .= "require_once 'db.php';\n\n";
            $content .= "define('ENV', getenv('ENV') ? getenv('ENV') : 'dev');\n\n";
            $content .= "//Model (for connexion data, see unversionned db.php)\n";
            $content .= "define('DB_USER', getenv('DB_USER') ? getenv('DB_USER') : APP_DB_USER);\n";
            $content .= "define('DB_PASSWORD', getenv('DB_PASSWORD') ? getenv('DB_PASSWORD') : APP_DB_PASSWORD);\n";
            $content .= "define('DB_HOST', getenv('DB_HOST') ? getenv('DB_HOST') : APP_DB_HOST);\n";
            $content .= "define('DB_NAME', getenv('DB_NAME') ? getenv('DB_NAME') : APP_DB_NAME);\n";
            fwrite($config, $content);
            fclose($config);
        }

        // Generate db.php.dist if not exist and add db.php to .gitignore
        if(!file_exists('config/db.php.dist')) {
            $connect = fopen('config/db.php.dist', 'w');
            $content = "<?php\n\n";
            $content .= "define('APP_DB_USER', 'root');\n";
            $content .= "define('APP_DB_PASSWORD', 'root');\n";
            $content .= "define('APP_DB_HOST', 'localhost');\n";
            $content .= "define('APP_DB_NAME', 'database_Name');\n";
            fwrite($connect, $content);
            fclose($connect);
            $gitignore = fopen('.gitignore', 'a');
            fwrite($gitignore, "\ndb.php");
            fclose($gitignore);
        }
        // Create a folder for the models if not exist
        if(!file_exists('src/Model')) {
            mkdir('src/Model', 0777, true);
        }

        // Generate the connection.php file if not exist
        if(!file_exists('src/Model/connection.php')) {
            $connection = fopen('src/Model/connection.php', 'w');
            $content = "<?php\n\n";
            $content .= "require_once 'config/config.php';\n\n";
            $content .= "    class Connection {\n\n";
            $content .= "        private PDO \$pdoConnection;\n\n";
            $content .= "        private string \$user;\n\n";
            $content .= "        private string \$host;\n\n";
            $content .= "        private string \$password;\n\n";
            $content .= "        private string \$dbName;\n\n";
            $content .= "        public function __construct() {\n";
            $content .= "            \$this->user = DB_USER;\n";
            $content .= "            \$this->host = DB_HOST;\n";
            $content .= "            \$this->password = DB_PASSWORD;\n";
            $content .= "            \$this->dbName = DB_NAME;\n";
            $content .= "            try {\n";
            $content .= "                \$this->pdoConnection = new PDO(\n";
            $content .= "                'mysql:host=' . \$this->host . '; dbname=' . \$this->dbName . '; charset=utf8',\n";
            $content .= "                \$this->user,\n";
            $content .= "                \$this->password\n";
            $content .= "               );\n";
            $content .= "               \$this->pdoConnection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);\n\n";
            $content .= "               // show errors in DEV environment\n";
            $content .= "               if (ENV === 'dev') {\n";
            $content .= "                   \$this->pdoConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";
            $content .= "               }\n";
            $content .= "           } catch (PDOException \$e) {\n";
            $content .= "               echo \"\n";
            $content .= "                   Erreur de connexion à la base de données \n";
            $content .= "                   Message d'erreur : \".\$e->getMessage().\"\n";
            $content .= "               \";\n";
            $content .= "           };\n\n";
            $content .= "        }\n\n";
            $content .= "        public function getPdoConnection(): PDO {\n";
            $content .= "            return \$this->pdoConnection;\n";
            $content .= "        }\n}";
            fwrite($connection, $content);
            fclose($connection);
        }

        // Generate the AbstractManager.php file if not exist
        if(!file_exists('src/Model/AbstractManager.php')) {
            $abstractManager = fopen('src/Model/AbstractManager.php', 'w');
            $content = "<?php\n\n";
            $content .= "require_once 'connection.php';\n\n";
            $content .= "    abstract class AbstractManager {\n\n";
            $content .= "        protected PDO \$pdo;\n\n";
            $content .= "        public const TABLE = '';\n\n";
            $content .= "        public function __construct() {\n";
            $content .= "            \$connection = new Connection();\n";
            $content .= "            \$this->pdo = \$connection->getPdoConnection();\n";
            $content .= "        }\n\n";
            $content .= "        public function selectAll(): array {\n";
            $content .= "            \$query = 'SELECT * FROM ' . static::TABLE;\n";
            $content .= "            \$statement = \$this->pdo->query(\$query);\n";
            $content .= "            return \$statement->fetchAll();\n";
            $content .= "        }\n\n";
            $content .= "        public function selectOneById(int \$id): array {\n";
            $content .= "            \$query = 'SELECT * FROM ' . static::TABLE . ' WHERE id = :id';\n";
            $content .= "            \$statement = \$this->pdo->prepare(\$query);\n";
            $content .= "            \$statement->bindValue('id', \$id, PDO::PARAM_INT);\n";
            $content .= "            \$statement->execute();\n";
            $content .= "            return \$statement->fetch();\n";
            $content .= "        }\n\n";
            $content .= "        public function delete(int \$id): void {\n";
            $content .= "            \$query = 'DELETE FROM ' . static::TABLE . ' WHERE id = :id';\n";
            $content .= "            \$statement = \$this->pdo->prepare(\$query);\n";
            $content .= "            \$statement->bindValue('id', \$id, PDO::PARAM_INT);\n";
            $content .= "            \$statement->execute();\n";
            $content .= "        }\n\n}\n";
            fwrite($abstractManager, $content);
            fclose($abstractManager);
        }
    }

    private function sqlToPhpType(string $type) : ?string
    {
        $typeMap = array(
            'tinyint' => 'int',
            'smallint' => 'int',
            'mediumint' => 'int',
            'int' => 'int',
            'bigint' => 'int',
            'float' => 'float',
            'double' => 'float',
            'decimal' => 'float',
            'date' => 'string',
            'time' => 'string',
            'datetime' => 'string',
            'timestamp' => 'int',
            'year' => 'int',
            'char' => 'string',
            'varchar' => 'string',
            'text' => 'string',
            'blob' => 'string',
            'enum' => 'string',
            'set' => 'string',
            'binary' => 'string',
            'bool' => 'boolean',
            'json' => 'string',
            'serial' => 'int',
            'numeric' => 'string',
            'real' => 'float',
            'nchar' => 'string',
            'nvarchar' => 'string',
            'ntext' => 'string',
            'image' => 'string',
            'xml' => 'string',
            'uuid' => 'string'
        );        
        $pos = strpos($type, '(');
        if ($pos !== false) {
          $type = substr($type, 0, $pos);
        }
        return isset($typeMap[$type]) ? $typeMap[$type] : null;	
    }
}