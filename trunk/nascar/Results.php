<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Result.php';
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


LDS_Results::initStaticVariables();


class LDS_Results
{
    protected static $staticInitialized = false;

    protected static $cacheByRaceId;


    protected $race;

    protected $resultsByRank;
    protected $resultsByDriverId;


    protected function __construct()
    {
        $this->race = null;

        $this->resultsByRank = null;
        $this->resultsByDriverId = null;
    }


    public function getRace()
    {
        return $this->race;
    }

    public function getByRank()
    {
        if(is_null($this->resultsByRank))
            $this->init();

        return $this->resultsByRank;
    }

    public function getByDriverId()
    {
        if(is_null($this->resultsByDriverId))
            $this->init();

        return $this->resultsByDriverId;
    }


    public function init()
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $this->resultsByRank = array();
        $this->resultsByDriverId = array();
        /*$query = new Query('SELECT re.raceId, re.resultId, re.car, re.driverId, '
            . 'd.firstName, d.lastName, d.color AS fontColor, d.background AS backgroundColor, d.border AS borderColor, '
            . 're.start, re.finish, re.ledLaps, re.ledMostLaps, '
            . 'IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0) AS points, '
            . 're.penalties FROM ' . $db->getTable('Result') . ' AS re '
            . 'INNER JOIN ' . $db->getTable('Driver') . ' AS d '
            . 'ON re.driverId=d.driverId '
            . 'WHERE raceId=' . $this->race->getId() . ' ORDER BY re.finish ASC');*/
        $query = new Query('SELECT re.raceId, re.resultId, re.car, re.driverId, '
        . 're.start, re.finish, re.ledLaps, re.ledMostLaps, '
        . 'IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0) AS points, '
        . 're.penalties FROM ' . $db->getTable('Result') . ' AS re '
        . 'INNER JOIN ' . $db->getTable('Driver') . ' AS d '
        . 'ON re.driverId=d.driverId '
        . 'WHERE raceId=' . $this->race->getId() . ' ORDER BY re.finish ASC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $raceResult = LDS_Result::constructUsingRow($row);

            $this->resultsByRank[] = $raceResult;
            $this->resultsByDriverId[$row['driverId']] = $raceResult;
        }
    }


    public static function getUsingRace(LDS_Race $race)
    {
        $raceId = intval($race->getId());

        if(self::$cacheByRaceId->containsKey($raceId))
            return self::$cacheByRaceId->get($raceId);
        else if($raceId > 0)
        {
            $obj = new LDS_Results();
            $obj->race = $race;

            self::$cacheByRaceId->add($raceId, $obj);

            return $obj;
        }

        return null;
    }


    public static function initStaticVariables()
    {
        if(!self::$staticInitialized)
        {
            self::$cacheByRaceId = new MXT_InstanceCache();

            self::$staticInitialized = true;
        }
    }
}



?>
