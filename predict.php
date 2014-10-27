<?php

require_once(__DIR__.'/db.php');
require_once(__DIR__.'/haversine.php');

DEFINE('MIN_TAIL_TIME_SEPARATION', 600);
DEFINE('MAX_DIVERGENCE_METRES', 300);
DEFINE('TAIL_LENGTH', '5 minutes');
DEFINE('HEAD_LENGTH', '30 minutes');

function testRouteAlignment($a, $b, $maxDivergenceMeters) {
	$i = 0;
	$j = 0;

	while ($i + 1 < count($a) && $j + 1 < count($b)) {
		$nextIDist = haversine($a[$i + 1], $b[$j]);
		$nextJDist = haversine($a[$i], $b[$j + 1]);

		if (min($nextIDist, $nextJDist) > $maxDivergenceMeters) {
			return false;
		}

		if ($nextIDist < $nextJDist) {	
			$i++;
		}
		else {
			$j++;
		}
	}

	return true;
}

function timeOfDayDifference($a, $b) {
	$aTime = date('i', $a) * 60 + date('s', $a);
	$bTime = date('i', $b) * 60 + date('s', $b);

	return abs($aTime - $bTime);
}

function weightTimestampSimilarity($a, $b) {
	$timeOfDayDifference = timeOfDayDifference($a, $b);

	$weight = 0;

	// Weight based on similar time of day
	if ($timeOfDayDifference < 120)
		$weight += 500;
	else if ($timeOfDayDifference < 300)
		$weight += 300;
	else if ($timeOfDayDifference < 3600)
		$weight += 40;
	else
		$weight += 10;

	// Weight based on weekday
	if (date('l', $a) == date('l', $b))
		$weight *= 2;

	return $weight;
}


function predictFutureStops(DB $db, $hyperBlip) {
	$busId = $hyperBlip['bus_id'];

	$nearbyBlips = $db->findHistoricalBlipsAtLocationBefore(
		$busId,
		$hyperBlip['longitude'],
		$hyperBlip['latitude'],
		date('Y-m-d H:i:s.u', strtotime($hyperBlip['at'])),
		MIN_TAIL_TIME_SEPARATION	
	);

	$tails = [];

	$lastTime = 0;
	foreach ($nearbyBlips as $blip) {
		$blipTime = strtotime($blip['at']);

		$timeSeparation = $blipTime - $lastTime;

		if ($timeSeparation > MIN_TAIL_TIME_SEPARATION) {
			$tails[] = array('nearest_blip' => $blip, 'nearest_time' => $blipTime);
			$lastTime = $blipTime;
		}
	}

	if (count($tails) == 0) {
		throw new Exception("No tails available. It will be impossible to predict routes based on this history.");
	}

	foreach ($tails as &$tail) {
		$tail['before'] = $db->loadTail($busId, $tail['nearest_blip']['at'], TAIL_LENGTH);
	}

	$currentRoute = $db->loadTail($busId, $hyperBlip['at'], TAIL_LENGTH);


	// Find a list of tails that are aligned with the current match
	$alignedTails = array_filter($tails, function($tail) use($currentRoute) {
		return testRouteAlignment($tail['before'], $currentRoute, MAX_DIVERGENCE_METRES);
	});

	foreach ($alignedTails as &$tail) {
		$futureStops = $db->getFutureStops($busId, $tail['nearest_blip']['at'], HEAD_LENGTH);

		foreach ($futureStops as &$futureStop) {
			$futureStop['seconds_until'] = strtotime($futureStop['at']) - strtotime($tail['nearest_blip']['at']);
		}

		$tail['future_stops'] = $futureStops;
	}

	// Simple algorithm to average the predicted times for now (and use those as stop estimates)
	$stopEstimates = [];
	foreach ($alignedTails as &$tail) {
		$weight = weightTimestampSimilarity(strtotime($tail['nearest_blip']['at']), strtotime($hyperBlip['at']));

		foreach ($tail['future_stops'] as $stop) {
			if (!array_key_exists($stop['stop_id'], $stopEstimates)) {
				$stopEstimates[$stop['stop_id']] = array(
					'id' => $stop['stop_id'],
					'name' => $stop['stop_name'],
					'predictions' => []
				);
			}

			$stopEstimates[$stop['stop_id']]['predictions'][] = [
				'time' => $stop['seconds_until'],
				'weight' => $weight
			];
		}
	}


	foreach ($stopEstimates as &$estimate) {
		$totalWeight = array_reduce($estimate['predictions'], function($carry, $item) {
			return $carry + $item['weight'];
		}, 0);

		$estimate['prediction'] = array_reduce($estimate['predictions'], function($carry, $prediction) use($totalWeight) {
			return $carry + ($prediction['time'] * ($prediction['weight'] / $totalWeight));
		}, 0);
	}

	usort($stopEstimates, function($a, $b) {
		return $a['prediction'] > $b['prediction'];
	});

	return $stopEstimates;
}


	









