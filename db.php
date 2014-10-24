<?php

class DB {
	function __construct($dsn) {
		$this->db = new PDO($dsn);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	private function selectAsArray($statement) {
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		return $results;
	}

	function selectNearbyBlips() {
		$statement = $this->db->prepare('
			SELECT * FROM blip
			WHERE ST_DWithin(location, (SELECT location FROM blip ORDER BY id DESC LIMIT 1), 10)
			ORDER BY at ASC;
			');

		return $this->selectAsArray($statement);
	}

	function selectNearbyBlipsAtPlace() {
		$statement = $this->db->prepare("
			SELECT * FROM blip
			WHERE ST_DWithin(location, ST_GeographyFromText('SRID=4326;POINT(138.5700856 -35.023426)'), 50)
			ORDER BY at ASC;
			");

		return $this->selectAsArray($statement);
	}

	function loadTail($bus_id, $at, $interval) {
		$statement = $this->db->prepare("
			SELECT
				bus_id,
				at,
				ST_X(location::geometry) as longitude,
				ST_Y(location::geometry) as latitude,
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

	function loadHead($bus_id, $at, $interval) {
		$statement = $this->db->prepare("
			SELECT
				bus_id,
				at,
				ST_X(location::geometry) as longitude,
				ST_Y(location::geometry) as latitude,
				altitude,
				speed,
				bearing
			FROM blip
			WHERE bus_id = :bus_id
			AND at > :at::timestamp
			AND at <= (:at::timestamp + :interval::interval)
			ORDER BY at ASC; 
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
			SELECT bus.id, bus.name, blip.at as last_blip_at, blip.location, blip.speed, blip.altitude, blip.bearing FROM bus
			LEFT OUTER JOIN blip
			ON bus.id = blip.bus_id AND blip.at = (
						SELECT max(at) FROM blip
						WHERE blip.bus_id = bus.id
						AND EXTRACT(EPOCH FROM now() - blip.at) < 3600
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
				ST_X(location::geometry) as longitude,
				ST_Y(location::geometry) as latitude,
				altitude, bearing, name, note
				FROM stop ORDER BY id DESC
			");

		return $this->selectAsArray($statement);
	}

	function getBlipsFor($ids) {
		$statement = $this->db->prepare("
			SELECT 	bus_id,
				at,
				ST_X(location::geometry) as longitude,
				ST_Y(location::geometry) as latitude,
				altitude,
				speed,
				bearing
				FROM blip
				WHERE bus_id IN (:ids)
				AND ST_DWithin(location, ST_GeographyFromText('SRID=4326;POINT(138.5680449 -35.0262689)'), 75)
				ORDER BY at, bus_id DESC
			");

		$statement->bindParam(':ids', implode(',', $ids));

		return $this->selectAsArray($statement);
	}

	function saveStop($stop) {
		$statement = $this->db->prepare("
			INSERT INTO stop(location, altitude, bearing)
			VALUES (ST_MakePoint(:longitude, :latitude),
				:altitude,
				:bearing)");
		
		$statement->bindParam(':longitude', $stop['longitude']);
		$statement->bindParam(':latitude', $stop['latitude']);
		$statement->bindParam(':altitude', $stop['altitude']);
		$statement->bindParam(':bearing', $stop['bearing']);

		$statement->execute();
	}

	function saveBlip($blip) {
		$statement = $this->db->prepare("
			INSERT INTO blip(bus_id, at, location, speed, altitude, bearing)
			VALUES (:bus_id,
				to_timestamp(:at),
				ST_MakePoint(:longitude, :latitude),
				:speed,
				:altitude,
				:bearing)");
		
		$at = $blip['time'] / 1000;
		$statement->bindParam(':bus_id', $blip['bus_id']);
		$statement->bindParam(':at', $at);
		$statement->bindParam(':longitude', $blip['longitude']);
		$statement->bindParam(':latitude', $blip['latitude']);
		$statement->bindParam(':speed', $blip['speed']);
		$statement->bindParam(':altitude', $blip['altitude']);
		$statement->bindParam(':bearing', $blip['bearing']);

		$statement->execute();
	}
}


$db = new DB('pgsql:host=localhost user=tracker dbname=tracker password=tracker');
