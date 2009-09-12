<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';


LDS_FantasyPicks::initStaticVariables();


class LDS_FantasyPicks
{
    protected static $staticInitialized = false;

    protected static $cacheByRaceId;


    protected $race;
    protected $picks;
    protected $picksByUserId;


    protected function __construct()
    {
        $this->race = null;
        $this->picks = null;
        $this->picksByUserId = null;
    }


    public function getRace()
    {
        return $this->race;
    }

    public function getPicks()
    {
        return $this->picks;
    }

    public function getPicksByUserId()
    {
        return $this->picksByUserId;
    }


    public static function getUsingRace(LDS_Race $race)
    {
        if(self::$cacheByRaceId->containsKey($race->getId()))
        {
            return self::$cacheByRaceId->get($race->getId());
        }
        else
        {
            $picks = new LDS_FantasyPicks();
            $picks->race = $race;

            $db = Database::getConnection('com.lightdatasys.nascar');
            
            $driverClass = LDS_DriverClass::getSingleton();
            
            $picks->picks = array();
            $picks->picksByUserId = array();
            $query = new Query('SELECT fp.userId, driverId FROM nascarFantPick AS fp'
                . ' INNER JOIN nascarRace AS r ON fp.raceId=r.raceId'
                . ' INNER JOIN user AS u ON fp.userId=u.userId'
                . ' WHERE r.raceId=' . $race->getId()
                . ' AND fp.deletedTime IS NULL ORDER BY name ASC');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $picks->picks[$row[1]][] = $row[0];
                $picks->picksByUserId[$row[0]][] = $row[1];
                $driverClass->queueObjectUsingId($row[1]);
            }

            self::$cacheByRaceId->add($race->getId(), $picks);
            return $picks;
        }
    }

    public static function getAllUsingRaces($races)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $raceIds = array();
        foreach($races as $race)
        {
            if($race instanceof LDS_Race)
            {
                if(!self::$cacheByRaceId->containsKey($race->getId()))
                    $raceIds[] = $race->getId();
            }
        }
        
        $driverClass = LDS_DriverClass::getSingleton();
        
        $picksByDriverId = array();
        $picksByUserId = array();
        if(count($raceIds) > 0)
        {
            $query = new Query('SELECT fp.userId, driverId, fp.raceId FROM nascarFantPick AS fp'
                . ' INNER JOIN nascarRace AS r ON fp.raceId=r.raceId'
                . ' INNER JOIN user AS u ON fp.userId=u.userId'
                . ' WHERE r.raceId IN (' . implode(',', $raceIds) . ')'
                . ' AND fp.deletedTime IS NULL ORDER BY name ASC');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $picksByDriverId[$row[2]][$row[1]][] = $row[0];
                $picksByUserId[$row[2]][$row[0]][] = $row[1];
                $driverClass->queueObjectUsingId($row[1]);
            }
        }

        $fantasyPicks = array();
        foreach($races as $race)
        {
            if($race instanceof LDS_Race)
            {
                if(self::$cacheByRaceId->containsKey($race->getId()))
                    $fantasyPicks[$race->getId()] = self::$cacheByRaceId->get($race->getId());
                else
                {
                    $picks = new LDS_FantasyPicks();
                    $picks->race = $race;
                    if(array_key_exists($race->getId(), $picksByDriverId))
                    {
                        $picks->picks = $picksByDriverId[$race->getId()];
                        $picks->picksByUserId = $picksByUserId[$race->getId()];
                    }
                    else
                    {
                        $picks->picks = array();
                        $picks->picksByUserId = array();
                    }

                    $fantasyPicks[$race->getId()] = $picks;
                    self::$cacheByRaceId->add($race->getId(), $picks);
                }
            }
        }

        return $fantasyPicks;
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
