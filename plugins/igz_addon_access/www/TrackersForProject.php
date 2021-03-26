<?php
require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../../../src/www/include/utils.php';
require_once __DIR__ .'/../../../src/common/dao/include/DataAccessObject.class.php';

$gid = $_GET["gid"];
$trackers = TrackerFactory::instance()->getTrackersByGroupIdUserCanView($gid,UserManager::instance()->getCurrentUser());

$result = array();

foreach ($trackers as $tracker) {
    array_push($result, $tracker);
}


echo json_encode($result);
