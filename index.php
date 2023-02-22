<?php

require_once '_connec.php';
require_once 'GenereClasses.php';

$portfolioClasses = new GenereClasses(DB_NAME, DB_HOST, DB_NAME, DB_PASSWORD);
$portfolioClasses->generateClasses();


// Example of use, replace by your own class name

require_once 'src/Model/TechManager.php';
$techManager = new TechManager();

print_r($techManager->selectAll());
$techManager->add('test', 'test');
print_r($techManager->search('test'));


// $techManager->delete(18);