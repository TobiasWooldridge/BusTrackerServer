<?php

require_once('./db.php');
require_once('./haversine.php');

DEFINE('MIN_TAIL_TIME_SEPARATION', 60);
DEFINE('MAX_DIVERGENCE_METRES', 300);
DEFINE('BUS_ID', 1);
DEFINE('TAIL_LENGTH', '5 minutes');

function areRoutesSimilar($a, $b) {
	$i = 0;
	$j = 0;

	while ($i + 1 < count($a) && $j + 1 < count($b)) {
		$nextIDist = haversine($a[$i + 1], $b[$j]);
		$nextJDist = haversine($a[$i], $b[$j + 1]);

		if ($nextIDist > MAX_DIVERGENCE_METRES && $nextJDist > MAX_DIVERGENCE_METRES) {
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


$rawTailStarts = $db->selectNearbyBlipsAtPlace();

$tailStarts = array();

$lastTime = 0;
foreach ($rawTailStarts as $tail) {
	$blipTime = strtotime($tail['at']);

	$timeSeparation = $blipTime - $lastTime;

	if ($timeSeparation > MIN_TAIL_TIME_SEPARATION) {
		$tailStarts[] = $tail;
		$lastTime = $blipTime;
	}
}


$tails = [];

foreach ($tailStarts as $tailStart) {
	$tails[] = $db->loadTail($tailStart['bus_id'], $tailStart['at'], TAIL_LENGTH);
}

$currentRoute = $tails[0];
$nearTails = array();

foreach ($tails as $key => $tail) {
	$nearEnough = areRoutesSimilar($tail, $currentRoute);

	echo "Tail #$key:\t" . ($nearEnough ? "Similar" : "Not similar") . "\n";
	if ($nearEnough) {
		$nearTails[] = $tail;
	}
}


echo "Analysing " . count($nearTails) . " similar tails\n";

$stops = $db->getStops();

$analyzedTails = array();

foreach ($nearTails as $tail) {
	$analyzedTail = array('tail' => $tail, 'first_blip' => $nearTail[0]);
	$head = $db->loadHead($analyzedTail['first_blip']['bus_id'], $analyzedTail['first_blip']['at']);
}

# Select nearby blips
#SELECT id FROM blip WHERE ST_DWithin(location, (SELECT location FROM blip ORDER BY id DESC LIMIT 1), 10);


# Select tails
#SELECT * FROM blip WHERE at > '2014-10-15 14:30:16' AND at < '2014-10-15 14:35:16'; 






