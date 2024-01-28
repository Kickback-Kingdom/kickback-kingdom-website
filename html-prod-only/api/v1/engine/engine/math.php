<?php 

function multiply($a, $b, $scale = 2) {
    return round($a * $b, $scale);
}

function add($a, $b, $scale = 2) {
    return round($a + $b, $scale);
}

function divide($a, $b, $scale = 2) {
    if ($b == 0) {
        throw new Exception('Division by zero.');
    }
    return round($a / $b, $scale);
}


function subtract($a, $b, $scale = 2) {
    return round($a - $b, $scale);
}

?>