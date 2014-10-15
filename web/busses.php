<?php
require_once('../db.php');

echo json_encode($db->getBusses());
echo "\n";

