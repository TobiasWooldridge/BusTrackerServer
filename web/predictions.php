<?php

require_once(__DIR__.'/../db.php');
require_once(__DIR__.'/../predict.php');

$hyperBlip = $db->findBlipNearestTime(1, date('Y-m-d H:i:s.u', time()));

if ($hyperBlip == null) {
	echo json_encode(["error" => "Bus or tracker not active."]);
}

$predictions = predictFutureStops($db, $hyperBlip[0]);
echo json_encode(array($predictions));
echo "\n";
