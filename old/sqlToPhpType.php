<?php

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
  
  function sqlToPhpType(string $type) : string
  {
    global $typeMap;
    $pos = strpos($type, '(');
    if ($pos !== false) {
      $type = substr($type, 0, $pos);
    }
    return isset($typeMap[$type]) ? $typeMap[$type] : 'string';	
  }