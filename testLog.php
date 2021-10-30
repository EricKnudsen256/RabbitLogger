#!/usr/bin/php
<?php

require_once('490Logger.php');

$logger = new rabbitLogger("logger.ini", "testListener");

$logger->log_rabbit('Debug', 'The Rabbit Log Has worked!');

?>
