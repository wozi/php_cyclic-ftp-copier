<?php
include ('xlib/spyc/spyc.php');
include ('lib/Configuration/config.php');
include ('lib/io/IO.php');
include ('lib/Logger/Logger.php');
include ('lib/Net/Net.php');
include ('lib/Net/Ftp.php');

include ('core/class.fileCopier.php');

// start a global copy, so all processes defined within the config will be done!
$fileCopier = new FileCopier ();
$fileCopier->start ();

// sleep
//sleep (2);
?>