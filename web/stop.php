<?php
$file = "stops.ljson";


$data = json_encode($_POST) . "\n";
file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
?>

