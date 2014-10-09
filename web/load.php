<?php
require_once('db.php');

function loadStops($db, $stops) {
	$statement = $db->prepare("INSERT INTO stop(location, altitude, bearing) VALUES (ST_MakePoint(:longitude, :latitude), :altitude, :bearing)");
	foreach ($stops as $stop) {
		$statement->bindParam(':longitude', $stop['longitude']);
		$statement->bindParam(':latitude', $stop['latitude']);
		$statement->bindParam(':altitude', $stop['altitude']);
		$statement->bindParam(':bearing', $stop['bearing']);
		$statement->execute();
	}
}

	// at TIMESTAMP,
	// location GEOGRAPHY,
	// speed DOUBLE PRECISION,
	// altitude DOUBLE PRECISION,
	// bearing DOUBLE PRECISION,
	// bus_id INTEGER	
function loadBlips($db, $blips) {
	$statement = $db->prepare("INSERT INTO blip(at, location, speed, altitude, bearing, bus_id) VALUES (to_timestamp(:at), ST_MakePoint(:longitude, :latitude), :speed, :altitude, :bearing, :bus_id)");

	$bus_id = 1;

	foreach ($blips as $blip) {
		$at = $blip['time'] / 1000;
		$statement->bindParam(':at', $at);
		$statement->bindParam(':longitude', $blip['longitude']);
		$statement->bindParam(':latitude', $blip['latitude']);
		$statement->bindParam(':speed', $blip['speed']);
		$statement->bindParam(':altitude', $blip['altitude']);
		$statement->bindParam(':bearing', $blip['bearing']);
		$statement->bindParam(':bus_id', $bus_id);
		$statement->execute();
	}
}

$stops = json_decode(file_get_contents("stops.json"), true);
loadStops($db, $stops);

$blips = json_decode(file_get_contents("blips.json"), true);
loadBlips($db, $blips);
