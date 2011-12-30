<?php

require_once __DIR__.'/../../../../autoload.php';

$catcher = new UniversalErrorCatcher_Catcher();
$catcher->setThrowRecoverableErrors(true);

$catcher->registerCallback(function(){
    echo "The error was caught\n\n\n";
});

$catcher->start();

include $_SERVER['argv'][1];