<?php

/* Gets the haversine distance in meters between two blips */
function haversine($a, $b) {
	// http://stackoverflow.com/questions/14750275/haversine-formula-with-php
	$latFrom = deg2rad($b['latitude']);
	$lonFrom = deg2rad($b['longitude']);
	$latTo = deg2rad($a['latitude']);
	$lonTo = deg2rad($b['longitude']);

	$latDelta = $latTo - $latFrom;
	$lonDelta = $lonTo - $lonFrom;

	$angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
	cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
	return $angle * 6367444;
}