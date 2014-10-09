<?php

$db = new PDO('pgsql:user=tracker dbname=tracker password=tracker');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


function select_as_json($statement) {
	$statement->execute();
	$results = $statement->fetchAll(PDO::FETCH_ASSOC);
	return json_encode($results);
}
