<?php

//This is a simple interface sample!

var_dump($_REQUEST);

if ($_REQUEST['_varname'] === 'test1') {
    if ($_REQUEST['_language'] === 'en-gb') {
        echo 'Result for "test1"';
    } elseif ($_REQUEST['_language'] === 'de-de') {
        echo 'Ergebnis zu "test1"';
    }
    exit;
} else {
    echo 'Result for all other than test1';
}
exit;
