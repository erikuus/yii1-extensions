<?php

error_reporting(E_ALL);
chdir(dirname(__FILE__));

require_once("SqlParser.php");

$sqlLists = SqlParser::parse(file_get_contents("test.sql"));

foreach($sqlLists as $sql):
    //Execute your query :)
    echo $sql."\n<br>";
endforeach;

?>