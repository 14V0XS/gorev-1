<?php
session_start();
$numb = rand(0, 1);

if ($numb == 0) {
    require_once '404code.php';
}
else if ($numb == 1) {
    require_once '404mechanic.php';
}
?>