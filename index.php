<?php

require_once '_connec.php';
require_once 'GenereClasses.php';

$portfolioClasses = new GenereClasses(DB_NAME, DB_HOST, DB_USER, DB_PASSWORD);
$portfolioClasses->generateClasses();