<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/aggregator/AggDatabaseParser.php';


class CacheFeedsResponder extends LightDataSysResponder
{
    public function get($args)
    {
        $db = Database::getConnection('com.mephex.aggregator');

        $force = false;
        if(array_key_exists('force', $_GET)
            && $_GET['force'] != '')
            $force = true;

        $query = new Query('SELECT feedId FROM ' . $db->getTable('Feed'));
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $rssParser = AggDatabaseParser::parseFeedById($row[0], $force);
        }
    }
}



?>
