<?php

// Example of use, replace all "TechManager" by your own class name
// don't forget to create config/db.php file with your own credentials

require_once 'src/Model/TechManager.php';
$techManager = new TechManager();

// Test add method
$techManager->add('test', 'test');

// Test search method
echo "\nResult of search 'test' after add test value: \n";
$search = $techManager->search('test');
print_r($search);

// Test update method
$techManager->update($search[0]['id'], 'updateValue', 'test');

// Test selectOneById method
echo "\nResult of selectOneById after update test value: \n";
print_r($techManager->selectOneById($search[0]['id']));

// Test delete method
$techManager->delete($search[0]['id']);

// Test selectAll method
echo "\nResult of selectAll after delete test value: \n";
print_r($techManager->selectAll());