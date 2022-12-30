<?php

/**
 * earthquakeTest.php
 * Project: Earthquakes
 * Created with PhpStorm
 * Developer: David Barkman
 * Created on: 12/17/22 @ 17:31
 */

$thing1 = "N of 2km Mesa km Akmrizona";
$thing2 = "2km N of Mesa Arizona";
$thing3 = "200 km N of Mesa Arizona";
$thing4 = "49 km WSW of Saint George's, Grenada";
$thing5 = "D'Entrecasteaux Islands region";

$things = [$thing1, $thing2, $thing3, $thing4, $thing5];

$pattern1 = "/^([0-9]{1,})[ ]*(km)/";

$count = 0;
foreach ($things as $thing) {
    $matched = preg_match($pattern1, $thing, $matches);
    echo $count . ": " . trim($matched) . PHP_EOL;
    foreach ($matches as $match) {
        echo "matches: " . trim($match) . PHP_EOL;
    }
    $splits = preg_split($pattern1, $thing);
    foreach ($splits as $split) {
        echo "split: " . trim($split) . PHP_EOL;
    }
    $count++;
}

