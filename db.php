<?php

class DB {
	const BLIP_NEAREST_TIME_THRESHOLD = '5 minutes';

	function __construct($dsn) {
		$this->db = new PDO($dsn);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	private function selectAsArray($statement) {
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		return $results;
	}

	function findBlipNearestTime($busId, $time) {
		$statement = $this->db->prepare("
			SELECT
				bus_id,
				at,
				longitude,
				latitude,
				altitude,
				speed,
				bearing
			FROM blip
			WHERE bus_id = :bus_id
			AND at > (:time::timestamp - :threshold::interval)
			AND at < (:time::timestamp + :threshold::interval)
			ORDER BY abs(extract(epoch FROM (:time::timestamp - at))) ASC
			LIMIT 1;
			");
	
		$threshold = self::BLIP_NEAREST_TIME_THRESHOLD;

		$statement->bindParam(':bus_id', $busId);
		$statement->bindParam(':time', $time);
		$statement->bindParam(':threshold', $threshold);

		return $this->selectAsArray($statement);
	}

	function findHistoricalBlipsAtLocationBefore($busId, $longitude, $latitude, $before, $interval) {
	    $R = 6371;  // earth's mean radius, km
	    $radius = 50; // limit the radius to consider points at this "location", in meters

	    // first-cut bounding box (in degrees)
	    $maxLat = $latitude + rad2deg($radius/$R);
	    $minLat = $latitude - rad2deg($radius/$R);
	    // compensate for degrees longitude getting smaller with increasing latitude
	    $maxLon = $longitude + rad2deg($radius/$R/cos(deg2rad($latitude)));
	    $minLon = $longitude - rad2deg($radius/$R/cos(deg2rad($latitude)));

		$statement = $this->db->prepare("
			SELECT
				bus_id, at, longitude, latitude, altitude, speed, bearing
			FROM (
                SELECT
				bus_id, at, longitude, latitude, altitude, speed, bearing
				FROM blip
                WHERE bus_id = :bus_id
					AND at < :before::timestamp - :interval::interval
					AND latitude BETWEEN :minLat AND :maxLat AND longitude BETWEEN :minLon AND :maxLon
					LIMIT 1000
            ) AS FirstCut

			WHERE bus_id = :bus_id
			AND at < :before::timestamp - :interval::interval
			AND LBT_Distance(longitude, latitude, :longitude, :latitude) < :radius
			ORDER BY at DESC
			LIMIT 500;
			");

		$statement->bindParam(':bus_id', $busId);
		$statement->bindParam(':before', $before);
		$statement->bindParam(':interval', $interval);
		$statement->bindParam(':longitude', $longitude);
		$statement->bindParam(':latitude', $latitude);
		$statement->bindParam(':radius', $radius);

		$statement->bindParam(':minLat', $minLat);
		$statement->bindParam(':minLon', $minLon);
		$statement->bindParam(':maxLat', $maxLat);
		$statement->bindParam(':maxLon', $maxLon);

		return $this->selectAsArray($statement);
	}

	function findAtStopId($long, $lat) {
		$statement = $this->db->prepare('
			SELECT id FROM stop
			WHERE LBT_Distance(longitude, latitude, :longitude, :latitude) < 20
			LIMIT 1;
			');

		$statement->bindParam(':longitude', $long);
		$statement->bindParam(':latitude', $lat);

		return array_shift($this->selectAsArray($statement));
	}

	function loadTail($bus_id, $at, $interval) {
		$statement = $this->db->prepare("
			SELECT
				bus_id,
				at,
				longitude,
				latitude,
				altitude,
				speed,
				bearing
			FROM blip
			WHERE bus_id = :bus_id
			AND at > (:at::timestamp - :interval::interval)
			AND at <= :at::timestamp
			ORDER BY at DESC;
		");

		$statement->bindParam(':bus_id', $bus_id);
		$statement->bindParam(':at', $at);
		$statement->bindParam(':interval', $interval);


		return $this->selectAsArray($statement);
	}

	function getFutureStops($bus_id, $at, $interval) {
		$statement = $this->db->prepare("
			SELECT
				bus_id,
				min(at) as at,
				stop.id as stop_id,
				stop.name as stop_name
			FROM blip
			JOIN stop ON blip.at_stop = stop.id
			WHERE bus_id = :bus_id
			AND at_stop IS NOT NULL
			AND at > :at::timestamp
			AND at <= (:at::timestamp + :interval::interval)
			GROUP BY bus_id, at_stop, stop.id, stop.name; 
		");

		$statement->bindParam(':bus_id', $bus_id);
		$statement->bindParam(':at', $at);
		$statement->bindParam(':interval', $interval);


		return $this->selectAsArray($statement);
	}


	function getBus($id) {
		$statement = $this->db->prepare('
			SELECT id, secret_key, name
				FROM bus
				WHERE id = :id
				LIMIT 1
			');

		$statement->bindParam(':id', $id);

		return $this->selectAsArray($statement);
	}

	function getBusses() {
		$statement = $this->db->prepare('
			SELECT bus.id,
				bus.name,
				blip.at as last_blip_at,
				blip.speed,
				blip.altitude,
				blip.bearing,
				longitude,
				latitude
			FROM bus
			LEFT OUTER JOIN blip
			ON blip.id = (
						SELECT id FROM blip
						WHERE blip.bus_id = bus.id
						AND EXTRACT(EPOCH FROM now() - blip.at) < 3600
						ORDER BY at DESC
						LIMIT 1
					);
			');

		return $this->selectAsArray($statement);
	}

	function getBusWithSecret($id, $secret) {
		$statement = $this->db->prepare('
			SELECT id, secret_key, name
				FROM bus
				WHERE id = :id
				AND   secret_key = :secret_key
				LIMIT 1
			');

		$statement->bindParam(':id', $id);
		$statement->bindParam(':secret_key', $secret);

		return $this->selectAsArray($statement);
	}

	function getStops() {
		$statement = $this->db->prepare("
			SELECT 	id,
				longitude,
				latitude,
				altitude, bearing, name, note
				FROM stop ORDER BY id DESC
			");

		return $this->selectAsArray($statement);
	}

	function getBlipsFor($ids) {
		$statement = $this->db->prepare("
			SELECT 	bus_id,
				at,
				longitude,
				latitude,
				altitude,
				speed,
				bearing
				FROM blip
				WHERE bus_id IN (:ids)
				ORDER BY at, bus_id DESC
			");

		$statement->bindParam(':ids', implode(',', $ids));

		return $this->selectAsArray($statement);
	}

	function saveStop($stop) {
		$statement = $this->db->prepare("
			INSERT INTO stop(longitude, latitude, altitude, bearing)
			VALUES (
				:longitude,
				:latitude,
				:altitude,
				:bearing)");
		
		$statement->bindParam(':longitude', $stop['longitude']);
		$statement->bindParam(':latitude', $stop['latitude']);
		$statement->bindParam(':altitude', $stop['altitude']);
		$statement->bindParam(':bearing', $stop['bearing']);

		$statement->execute();
	}

	function saveBlip($blip) {
		// Pre-calculate whether this blip is at a stop or not
		$atStopId = $this->findAtStopId($blip['longitude'], $blip['latitude']);

		$statement = $this->db->prepare("
			INSERT INTO blip(bus_id, at, longitude, latitude, speed, altitude, bearing, at_stop)
			VALUES (:bus_id,
				to_timestamp(:at),
				:longitude,
				:latitude,
				:speed,
				:altitude,
				:bearing,
				:at_stop)");
		
		$at = $blip['time'] / 1000;
		$statement->bindParam(':bus_id', $blip['bus_id']);
		$statement->bindParam(':at', $at);
		$statement->bindParam(':longitude', $blip['longitude']);
		$statement->bindParam(':latitude', $blip['latitude']);
		$statement->bindParam(':speed', $blip['speed']);
		$statement->bindParam(':altitude', $blip['altitude']);
		$statement->bindParam(':bearing', $blip['bearing']);
		$statement->bindParam(':at_stop', $atStopId);

		$statement->execute();
	}
}


$db = new DB('pgsql:host=localhost user=tracker dbname=tracker password=tracker');

