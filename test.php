<?php
error_reporting(E_ALL);
require('Campanoscraper/load.php');

$c = Campanophile::getInstance();
$db = new Database('localhost', 'root', '', 'campanophile');

$r = $c->browse();

//$r = $c->get_performance(103998);

//$r = $c->search_all(array('StartDate' => '01/01/2009', 'FinalDate' => '31/12/2009', 'Guild' => 'Surrey Association'));

print_r($r);

