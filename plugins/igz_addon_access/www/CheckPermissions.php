<?php
require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../../../src/www/include/utils.php';
require_once __DIR__ .'/../../../src/common/dao/include/DataAccessObject.class.php';

$um = UserManager::instance();
$user = $um->getCurrentUser();
$data = $user->getAllUgroups();
$permission = false;
$type = $_GET["type"];

while($row = $data->getRow())
{
    switch ($type)
    {
        case "General":
            if($row['name'] === "IGZ" || $row['name'] == "TestportalBetaTester")
            {
                $permission = true;
                break;
            }
            break;
        case "FullTextSearchAddon":
            if($row['name'] === "IGZ")
            {
                $permission = true;
                break;
            }
            break;
        case "CheckTcAddon":
            if($row['name'] === "IGZ")
            {
                $permission = true;
                break;
            }
            break;
    }
}

echo json_encode($permission);
