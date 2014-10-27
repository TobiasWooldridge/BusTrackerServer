<?php

require_once('db.php');
require_once('predict.php');

$hyperBlip = $db->findBlipNearestTime(1, '2014-10-15 14:43:07');

if ($hyperBlip == null) {
	throw new Exception("Could not find a recent blip, bus may not be running.");
}

$startTime = microtime(true) * 1000;
$predictions = predictFutureStops($db, $hyperBlip[0]);
$endTime = microtime(true) * 1000;

echo round($endTime - $startTime, 2) . "ms to generate\n";

foreach ($predictions as $prediction) {
	echo round($prediction['prediction'], 2) . "s    \t to " . $prediction['name'] . "\n";
}
	