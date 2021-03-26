<?php
require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../../../src/www/include/utils.php';
require_once __DIR__ .'/../../../src/common/dao/include/DataAccessObject.class.php';

$um = UserManager::instance();
$user = $um->getCurrentUser();
$data = $user->getAllUgroups();
$permission = false;

while($row = $data->getRow())
{
   if($row['name'] === "IGZ")
   {
       $permission = true;
       break;
   }
}
if($permission) {
    $gid = $_GET["gid"];
    $dao = new DataAccessObject();
    $rows = $dao->retrieve("CALL GetOPTestCaseConflicts($gid)");
    $result = array();

    while ($row = $rows->getRow()) {
        array_push($result, $row);
    }


    echo json_encode($result);
}
else
{
    echo "Access Denied";
}