<?php
include ('xlib/spyc/spyc.php');
include ('lib/Configuration/config.php');
include ('lib/io/IO.php');
include ('lib/Logger/Logger.php');
include ('lib/Net/Net.php');
include ('lib/Net/Ftp.php');

include ('core/class.fileCopier.php');

// get the process name we need to launch (launched by CLI)
$process = $argv [1];

// web mode : attack via a GET request
if (!$process) $process = $_GET ['process'];

// manage all files
$fileCopier = new FileCopier ();
$fileCopier->startProcess ($process);
?>