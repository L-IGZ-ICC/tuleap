<?php
require_once __DIR__ . '/../../../src/www/include/pre.php';
require_once __DIR__ . '/../../../src/www/include/utils.php';
require_once __DIR__ .'/../../../src/common/dao/include/DataAccessObject.class.php';
require_once (dirname(__FILE__).'/../../tracker/include/Tracker/Artifact/Tracker_ArtifactFactory.class.php');
require_once (dirname(__FILE__).'/../../tracker/include/Tracker/Artifact/dao/Tracker_ArtifactDao.class.php');



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
    $tid = $_GET["tid"];
    $searchtext = $_GET["st"];
    $results = getResults($gid,$tid,$searchtext);
    $filteredResult = array();

    foreach ($results as $result)
    {
        $aid = $result["ItemId"];
        if(TrackerFactory::instance()->getTrackerById($result["TrackerId"])->userCanView()) {
            if (Tracker_ArtifactFactory::instance()->getArtifactById($aid)->userCanView()) {
                if ($result["FeldId"] == 0 || Tracker_FormElementFactory::instance()->getFieldById($result["FeldId"])->userCanRead($user)) {
                    array_push($filteredResult, $result);
                }
            }
        }
    }

    echo json_encode($filteredResult);
}
else
{
    echo "Access Denied";
}

function getResults($gid,$tid,$searchtext)
{
    $results = array();
    $sqli = mysqli_connect(\ForgeConfig::get('sys_dbhost'),\ForgeConfig::get('sys_dbuser'),\ForgeConfig::get('sys_dbpasswd'), \ForgeConfig::get('sys_dbname'));
    $sqli->set_charset("utf8");
    $sqli->select_db("tuleap");
    if($sqli->multi_query("CALL FullTextSearch($gid,$tid,'$searchtext');"));
    {
        if ($result = $sqli->store_result()) {
            while ($row = $result->fetch_assoc()) {
                array_push($results,$row);
            }
        }
        if($result)
        {
            $result->close();
        }

        $sqli->next_result();
    }
    $sqli->close();
    return $results;
}