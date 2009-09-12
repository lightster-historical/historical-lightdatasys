<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/DriverStanding.php';
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


LDS_DriverStandings::initStaticVariables();


class LDS_DriverStandings
{
    protected static $staticInitialized = false;

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

    public function getMaxPurchase()
    {
        $maxPicks = intval($this->getRace()->getSeason()->getMaxPickCount());
        if($this->getRace()->isForPoints())
        {
            $ranks = range(1, $maxPicks - 1);
            $ranks[] = 21;
        }
        else
        {
            $ranks = range(1, $maxPicks);
        }

        $standings = $this->getByRank();

        $maxPurchase = 0;
        foreach($ranks as $rank)
        {
            if(array_key_exists($rank - 1, $standings))
            {
                $maxPurchase += $standings[$rank - 1]->getPoints();
            }
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
            . 'd.color AS fontColor, d.background AS backgroundColor, d.border AS borderColor, COUNT(ra.raceId) starts, '
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
        while($row = $db->getAssoc($result))
        {
            $standing = LDS_DriverStanding::constructUsingRow($row);
            $this->standingsByRank[$row['driverId']] = $standing;
        }

        if($this->zeroPointDrivers)
        {
            $query = new Query('SELECT d.driverId, d.firstName, d.lastName, '
                . 'd.color, d.background, d.border FROM nascarDriver AS d ORDER BY lastName, firstName');
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                if(!array_key_exists($row['driverId'], $this->standingsByRank))
                {
                    $row['starts'] = 0;
                    $row['wins'] = 0;
                    $row['top5s'] = 0;
                    $row['top10s'] = 0;
                    $row['totalFinish'] = 0;
                    $row['points'] = 0;
                    $row['chasePenalties'] = 0;

                    $standing = LDS_DriverStanding::constructUsingRow($row);
                    $this->standingsByRank[$row['driverId']] = $standing;
                }
            }
        }

        if($raceNo >= $season->getChaseRaceNo() && !is_null($chaseDate))
        {
            $i = 0;
            foreach($this->standingsByRank as $driverId => $driver)
            {
                if($i < $season->getChaseDriverCount())
                    $this->standingsByRank[$driverId]->setPoints(
                        5000 + (10 * $driver->getWins()) - $driver->getChasePenalties());
                else
                    break;
                $i++;
            }

            $query = new Query('SELECT d.driverId, d.firstName, d.lastName, '
                . 'd.color AS fontColor, d.background AS backgroundColor, d.border AS borderColor, COUNT(ra.raceId) starts, '
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
            while($row = $db->getAssoc($result))
            {
                if(array_key_exists($row['driverId'], $this->standingsByRank))
                {
                    $standing = $this->standingsByRank[$row['driverId']];

                    $standing->addStarts($row['starts']);
                    $standing->addWins($row['wins']);
                    $standing->addTop5s($row['top5s']);
                    $standing->addTop10s($row['top10s']);
                    $standing->addTotalFinish($row['totalFinish']);
                    $standing->addPoints($row['points']);
                }
                else
                {
                    $row['chasePenalties'] = 0;

                    $standing = LDS_DriverStanding::constructUsingRow($row);
                    $this->standingsByRank[$row['driverId']] = $standing;
                }
            }
        }
        usort($this->standingsByRank, array('self', 'compareByPoints'));

        $lastPoints = -1;
        $tied = 0;
        $lastRank = 0;
        $this->standingsByDriverId = array();
        foreach($this->standingsByRank as $standing)
        {
            if($lastPoints == $standing->getPoints())
            {
                $tied++;
            }
            else
            {
                $lastRank += $tied + 1;
                $lastPoints = $standing->getPoints();
                $tied = 0;
            }

            $standing->setRank($lastRank);
            $this->standingsByDriverId[$standing->getDriver()->getId()] = $standing;
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


    public static function compareByPoints($a, $b)
    {
        if($a->getPoints() == $b->getPoints())
            return 0;
        else
            return $a->getPoints() < $b->getPoints() ? 1 : -1;
    }
}



?>
