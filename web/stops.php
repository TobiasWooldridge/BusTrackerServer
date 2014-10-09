<?php
require_once('db.php');

$sql = 'SELECT ST_X(location::geometry) as longitude, ST_Y(location::geometry) as latitude, altitude, bearing, name, note FROM stop ORDER BY id DESC';

echo select_as_json($db->prepare($sql));
echo "\n";

