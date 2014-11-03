<?php

require_once(__DIR__.'/db.php');
require_once(__DIR__.'/predict.php');

function getPredictionForTime(DB $db, $time, $stopId) {
	$hyperBlip = $db->findBlipNearestTime(1, $time);

	if ($hyperBlip == null) {
		throw new Exception("Could not find a recent blip, bus may not have been running at this time.");
	}


	$predictions = predictFutureStops($db, $hyperBlip[0]);

	foreach ($predictions as $prediction) {
		if ($prediction['id'] == $stopId) {
			return round($prediction['prediction']);
		}
	}

	return -1;
}

$startTime = strtotime('2014-10-15 14:10:42');

$arrivalPredictionTimeUnix = 0;

for ($i = 0; $i < 35; $i++) {
	$time = $startTime + (60 * $i);
	$predictionSeconds = getPredictionForTime($db, date('Y-m-d H:i:s', $time), 5);


	if ($predictionSeconds != -1) {
		$predictionFooTime = $time + $predictionSeconds;

		if (abs($predictionFooTime - $arrivalPredictionTimeUnix) > 600) {
			$arrivalPredictionTimeUnix = $predictionFooTime;
		}
		else {
			$arrivalPredictionTimeUnix = ($arrivalPredictionTimeUnix * 0.75 + $predictionFooTime * 0.25);
		}
	}

	print date('H:i:s', $time) . "\t". $predictionSeconds . "\t" . round($arrivalPredictionTimeUnix - $time, 2) . "\t\n";
}

