<?php
require_once('../db.php');

echo json_encode($db->getBlips());
echo "\n";

