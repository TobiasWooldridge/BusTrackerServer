<?php
require_once('../db.php');

$bus = $db->getBusWithSecret($_POST['bus_id'], $_POST['secret_key']);

if ($bus == null) {
    header('HTTP/1.0 403 Forbidden');
    die('Illegal id/secret provided for bus.');
}

$db->saveStop($_POST);
