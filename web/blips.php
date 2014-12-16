<?php
require_once('../db.php');
require_once('../haversine.php');

echo json_encode(array($db->getBlipsFor(array(1))));
echo "\n";

// DEFINE('MIN_TAIL_TIME_SEPARATION', 30);
// DEFINE('MAX_DIVERGENCE_METRES', 200);
// DEFINE('BUS_ID', 1);

// $rawTailStarts = $db->selectNearbyBlipsAtPlace();

// $tailStarts = array();

// $lastTime = 0;
// foreach ($rawTailStarts as $tail) {
// 	$blipTime = strtotime($tail['at']);

// 	$timeSeparation = $blipTime - $lastTime;

// 	if ($timeSeparation > MIN_TAIL_TIME_SEPARATION) {
// 		$tailStarts[] = $tail;
// 		$lastTime = $blipTime;
// 	}
// }


// $tails = array();

// foreach ($tailStarts as $tailStart) {
// 	$tails[] = $db->loadTail($tailStart['bus_id'], $tailStart['at']);
// }


// $currentRoute = $tails[1];


// $correctTails = array();
// foreach ($tails as $tail) {
// 	$i = 0;
// 	$j = 0;

// 	$nearEnough = true;
// 	while ($i + 1 < count($tail) && $j + 1 < count($currentRoute)) {
// 		$nextIDist = haversine($tail[$i + 1], $currentRoute[$j]);
// 		$nextJDist = haversine($tail[$i], $currentRoute[$j + 1]);

// 		if ($nextIDist > MAX_DIVERGENCE_METRES && $nextJDist > MAX_DIVERGENCE_METRES) {
// 			$nearEnough = false;
// 			break;
// 		}

// 		if ($nextIDist < $nextJDist) {
// 			$i++;
// 		}
// 		else {
// 			$j++;
// 		}
// 	}

// 	if ($nearEnough) {
// 		$correctTails[] = $tail;
// 	}
// }

// echo json_encode($correctTails);
