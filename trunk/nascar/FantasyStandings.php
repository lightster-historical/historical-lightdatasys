<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


LDS_FantasyStandings::initStaticVariables();


class LDS_FantasyStandings
{
    protected static $staticInitialized = false;

    protected static $cacheByRaceId;
    protected static $cacheByRaceId;


    protected $race;

    protected $zeroPointDrivers;

    protected $standingsByRank;
    protected $standingsByDriverId;


    protected function __construct()
    {
        $this->race = null;
        $this->zeroPointDrivers = false;

        $this->standingsByRank = null;
        $this->standingsByDriverId = null;
    }


    public function getRace()
    {
        return $this->race;
    }

    public function getByRank()
    {
        if(is_null($this->standingsByRank))
            $this->init();

        return $this->standingsByRank;
    }

    public function getByDriverId()
    {
        if(is_null($this->standingsByDriverId))
            $this->init();

        return $this->standingsByDriverId;
    }

    public function getMaxPurchase(&$ranks)
    {
        $standings = $this->getByRank();

        $maxPurchase = 0;
        foreach($ranks as $rank)
        {
            if(array_key_exists($rank, $standings))
                $maxPurchase += $standings[$rank]->points;
        }

        return $maxPurchase;
    }


    public function init()
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $race = $this->getRace();
        $raceNo = $race->getNumber();
        $raceDate = $race->getDate();

        $season = $race->getSeason();
        $seasonId = $season->getId();
        $chaseDate = $season->getChaseRaceDate();

        $whereChase = '';
        if(!is_null($chaseDate))
            $whereChase = ' AND ra.date<=\'' . $chaseDate->format('q Q') . '\'';

        $this->standingsByRank = array();
        $query = new Query('SELECT d.driverId, d.firstName, d.lastName, '
            . 'd.color, d.background, d.border, COUNT(ra.raceId) starts, '
            . 'SUM(IF(finish=1,1,0)) wins, SUM(IF(finish<=5,1,0)) top5s, '
            . 'SUM(IF(finish<=10,1,0)) top10s, SUM(re.finish) AS totalFinish, '
            . 'SUM(IF(official=' . LDS_Race::RESULT_OFFICIAL . ' OR official=' . LDS_Race::RESULT_UNOFFICIAL . ',IF(finish<=0,0,IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0)+penalties), 0)) AS points, '
            . 'cp.penalty AS chasePenalties '
            . 'FROM nascarDriver AS d '
            . 'INNER JOIN nascarResult AS re ON d.driverId=re.driverId '
            . 'INNER JOIN nascarRace AS ra ON re.raceId=ra.raceId '
            . 'LEFT JOIN nascarChasePenalty AS cp ON cp.seasonId=ra.seasonId '
            . 'AND cp.driverId=d.driverId '
            . 'WHERE ra.seasonId=' . $seasonId . ' AND DATE(ra.date)<=\''
            . $raceDate->format('q Q') . '\' AND ra.forPoints=1' . $whereChase . ' '
            . 'GROUP BY d.driverId ORDER BY points DESC');
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $standing = new Standing($row[0], $row[1], $row[2], $row[3]
                , $row[4], $row[5], $row[6], $row[7], $row[8], $row[9]
                , $row[10], $row[11], $row[12]);
            $this->standingsByRank[$row[0]] = $standing;
            $driverClass = LDS_DriverClass::getSingleton();
            $driverClass->queueObjectUsingId($row[0]);
        }

        if($this->zeroPointDrivers)
        {
            $query = new Query('SELECT d.driverId, d.firstName, d.lastName, '
                . 'd.color, d.background, d.border FROM nascarDriver AS d ORDER BY lastName, firstName');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                if(!array_key_exists($row[0], $this->standingsByRank))
                {
                    $standing = new Standing($row[0], $row[1], $row[2], $row[3]
                        , $row[4], $row[5], 0, 0, 0, 0, 0, 0, 0);
                    $this->standingsByRank[$row[0]] = $standing;
                }
            }
        }

        if($raceNo >= $season->getChaseRaceNo() && !is_null($chaseDate))
        {
            $i = 0;
            foreach($this->standingsByRank as $driverId => $driver)
            {
                if($i < $season->getChaseDriverCount())
                    $this->standingsByRank[$driverId]->points =
                        5000 + (10 * $driver->wins) - $driver->chasePenalties;
                else
                    break;
                $i++;
            }

            $query = new Query('SELECT d.driverId, d.firstName, d.lastName, '
                . 'd.color, d.background, d.border, COUNT(ra.raceId) starts, '
                . 'SUM(IF(finish=1,1,0)) wins, SUM(IF(finish<=5,1,0)) top5s, '
                . 'SUM(IF(finish<=10,1,0)) top10s, SUM(re.finish) AS totalFinish, '
                . 'SUM(IF(official=' . LDS_Race::RESULT_OFFICIAL . ' OR official=' . LDS_Race::RESULT_UNOFFICIAL . ',IF(finish<=0,0,IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0)+penalties),0)) AS points '
                . 'FROM nascarDriver AS d '
                . 'INNER JOIN nascarResult AS re ON d.driverId=re.driverId '
                . 'INNER JOIN nascarRace AS ra ON re.raceId=ra.raceId '
                . 'WHERE ra.seasonId=' . $seasonId . ' AND DATE(ra.date)<=\''
                . $raceDate->format('q Q') . '\' AND ra.date>\'' . $chaseDate->format('q Q') . '\' AND ra.forPoints=1 '
                . 'GROUP BY d.driverId ORDER BY points DESC');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                if(array_key_exists($row[0], $this->standingsByRank))
                {
                    $standing = $this->standingsByRank[$row[0]];

                    $standing->starts += $row[6];
                    $standing->wins += $row[7];
                    $standing->top5s += $row[8];
                    $standing->top10s += $row[9];
                    $standing->totalFinish += $row[10];
                    $standing->points += $row[11];
                }
                else
                {
                    $standing = new Standing($row[0], $row[1], $row[2], $row[3]
                        , $row[4], $row[5], $row[6], $row[7], $row[8], $row[9]
                        , $row[10], $row[11], 0);
                    $this->standingsByRank[$row[0]] = $standing;
                }
            }
        }
        usort($this->standingsByRank, array('NascarData', 'compareStandings'));

        $lastPoints = -1;
        $tied = 0;
        $lastRank = 0;
        $this->standingsByDriverId = array();
        foreach($this->standingsByRank as $standing)
        {
            if($lastPoints == $standing->points)
            {
                $tied++;
            }
            else
            {
                $lastRank += $tied + 1;
                $lastPoints = $standing->points;
                $tied = 0;
            }

            $standing->rank = $lastRank;
            $this->standingsByDriverId[$standing->driverId] = $standing;
        }
    }


    public static function getUsingRace(LDS_Race $race, $zeroPointDrivers = false)
    {
        $raceId = intval($race->getId());

        if(self::$cacheByRaceId->containsKey($raceId))
            return self::$cacheByRaceId->get($raceId);
        else if($raceId > 0)
        {
            $obj = new LDS_DriverStandings();
            $obj->race = $race;
            $obj->zeroPointDrivers = $zeroPointDrivers;

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
