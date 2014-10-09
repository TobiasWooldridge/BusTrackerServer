<?php
require_once('db.php');

$sql = 'SELECT bus_id, at, ST_X(location::geometry) as longitude, ST_Y(location::geometry) as latitude, altitude, bearing FROM blip ORDER BY at, bus_id DESC';

echo select_as_json($db->prepare($sql));
echo "\n";

