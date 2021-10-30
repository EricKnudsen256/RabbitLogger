#!/usr/bin/php
<?php

require_once("490Logger.php");

$logger = new rabbitLogListener("logger.ini", "testListener");

$logger->listen_for_logs();

?>
