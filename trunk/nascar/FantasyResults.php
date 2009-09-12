<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';


LDS_FantasyResults::initStaticVariables();


class LDS_FantasyResults
{
    protected static $staticInitialized = false;

    protected static $cacheByRaceId;


    protected $race;

    protected $forPoints;

    protected $driverPoints;
    protected $totalDriverPoints;
    protected $maxDriverPointsByUserId;
    protected $minDriverPointsByRaceId;
    protected $maxDriverPointsByRaceId;

    protected $fantasyPoints;
    protected $totalFantasyPoints;
    protected $minFantasyPointsByRaceId;
    protected $maxFantasyPointsByRaceId;


    protected function __construct()
    {
        $this->race = null;

        $this->forPoints = null;

        $this->driverPoints = null;
        $this->totalDriverPoints = null;
        $this->minDriverPointsByRaceId = null;
        $this->maxDriverPointsByRaceId = null;
        $this->maxDriverPointsByUserId = null;

        $this->fantasyPoints = null;
        $this->totalFantasyPoints = null;
        $this->minFantasyPointsByRaceId = null;
        $this->maxFantasyPointsByRaceId = null;
    }


    public function getRace()
    {
        return $this->race;
    }

    public function getDriverPoints()
    {
        return $this->driverPoints;
    }

    public function getDriverPointsUsingRace(LDS_Race $race)
    {
        $driverPoints = $this->getDriverPoints();
        if(array_key_exists($race->getId(), $this->driverPoints))
            return $this->driverPoints[$race->getId()];

        return null;
    }

    public function getDriverPointsUsingRaceAndPlayer(LDS_Race $race, LDS_FantasyPlayer $player)
    {
        $driverPoints = $this->getDriverPoints();
        if(array_key_exists($race->getId(), $this->driverPoints)
            && array_key_exists($player->getId(), $this->driverPoints[$race->getId()]))
            return $this->driverPoints[$race->getId()][$player->getId()];

        return null;
    }

    public function getTotalDriverPoints()
    {
        return $this->totalDriverPoints;
    }

    public function getTotalDriverPointsUsingPlayer(LDS_FantasyPlayer $player)
    {
        $totalDriverPoints = $this->getTotalDriverPoints();
        if(array_key_exists($player->getId(), $totalDriverPoints))
            return $totalDriverPoints[$player->getId()];

        return null;
    }

    public function getMinDriverPoints()
    {
        return $this->minDriverPointsByRaceId;
    }

    public function getMinDriverPointsUsingRace(LDS_Race $race)
    {
        $minPoints = $this->getMinDriverPoints();
        if(array_key_exists($race->getId(), $minPoints))
            return $minPoints[$race->getId()];

        return null;
    }

    public function getMaxDriverPoints()
    {
        return $this->maxDriverPointsByRaceId;
    }

    public function getMaxDriverPointsUsingRace(LDS_Race $race)
    {
        $maxPoints = $this->getMaxDriverPoints();
        if(array_key_exists($race->getId(), $maxPoints))
            return $maxPoints[$race->getId()];

        return null;
    }

    public function getFantasyPoints()
    {
        return $this->fantasyPoints;
    }

    public function getFantasyPointsUsingRace(LDS_Race $race)
    {
        $fantasyPoints = $this->getFantasyPoints();
        if(array_key_exists($race->getId(), $this->fantasyPoints))
            return $this->fantasyPoints[$race->getId()];

        return null;
    }

    public function getFantasyPointsUsingRaceAndPlayer(LDS_Race $race, LDS_FantasyPlayer $player)
    {
        $fantasyPoints = $this->getFantasyPoints();
        if(array_key_exists($race->getId(), $this->fantasyPoints)
            && array_key_exists($player->getId(), $this->fantasyPoints[$race->getId()]))
            return $this->fantasyPoints[$race->getId()][$player->getId()];

        return null;
    }

    public function getTotalFantasyPoints()
    {
        return $this->totalFantasyPoints;
    }

    public function getTotalFantasyPointsUsingPlayer(LDS_FantasyPlayer $player)
    {
        $totalFantasyPoints = $this->getTotalFantasyPoints();
        if(array_key_exists($player->getId(), $totalFantasyPoints))
            return $totalFantasyPoints[$player->getId()];

        return null;
    }

    public function getMinFantasyPoints()
    {
        return $this->minFantasyPointsByRaceId;
    }

    public function getMinFantasyPointsUsingRace(LDS_Race $race)
    {
        $minPoints = $this->getMinFantasyPoints();
        if(array_key_exists($race->getId(), $minPoints))
            return $minPoints[$race->getId()];

        return null;
    }

    public function getMaxFantasyPoints()
    {
        return $this->maxFantasyPointsByRaceId;
    }

    public function getMaxFantasyPointsUsingRace(LDS_Race $race)
    {
        $maxPoints = $this->getMaxFantasyPoints();
        if(array_key_exists($race->getId(), $maxPoints))
            return $maxPoints[$race->getId()];

        return null;
    }


    protected function initDriverPoints(LDS_Race $race)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $this->forPoints = array();
        $this->driverPoints = array();
        $this->totalDriverPoints = array();
        $this->minDriverPointsByRaceId = array();
        $this->maxDriverPointsByRaceId = array();
        $query = new Query('SELECT r.raceId, fp.userId, '
            . ' SUM(IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0)) AS points'
            . ', r.forPoints, r.official'
            . ' FROM nascarFantPick AS fp'
            . ' INNER JOIN nascarRace AS r ON fp.raceId=r.raceId'
            . ' INNER JOIN user AS u ON fp.userId=u.userId'
            . ' LEFT JOIN nascarResult AS re ON r.raceId=re.raceId AND fp.driverId=re.driverId'
            . ' WHERE r.date<=\'' . $race->getDate()->format('q Q') . '\' AND seasonId=' . $race->getSeason()->getId()
            . ' AND fp.deletedTime IS NULL'
            . ' GROUP BY r.raceId, fp.userId'
            . ' ORDER BY points DESC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            if(!array_key_exists($row['raceId'], $this->minDriverPointsByRaceId))
            {
                $this->minDriverPointsByRaceId[$row['raceId']] = 99999;
                $this->maxDriverPointsByRaceId[$row['raceId']] = 0;
            }

            if(!array_key_exists($row['userId'], $this->totalDriverPoints))
                $this->totalDriverPoints[$row['userId']] = 0;

            if($row['forPoints'] == '1')
            {
                $this->forPoints[$row['raceId']] = true;

                if($row['official'] == LDS_Race::RESULT_UNOFFICIAL
                    || $row['official'] == LDS_Race::RESULT_OFFICIAL)
                {
                    $this->driverPoints[$row['raceId']][$row['userId']] = $row['points'];
                    $this->totalDriverPoints[$row['userId']] += $row['points'];
                }
                else
                {
                    $this->driverPoints[$row['raceId']][$row['userId']] = 0;
                }

                $this->maxDriverPointsByRaceId[$row['raceId']] = max($this->maxDriverPointsByRaceId[$row['raceId']], $row['points']);
                if($row['points'] != 0)
                    $this->minDriverPointsByRaceId[$row['raceId']] = min($this->minDriverPointsByRaceId[$row['raceId']], $row['points']);
            }
            else
            {
                $this->forPoints[$row['raceId']] = false;
                $this->driverPoints[$row['raceId']][$row['userId']] = $row['points'];
            }
        }
    }

    protected function initFantasyPoints()
    {
        $this->fantasyPoints = array();
        $this->totalFantasyPoints = array();
        $this->minFantasyPointsByRaceId = array();
        $this->maxFantasyPointsByRaceId = array();
        foreach($this->driverPoints as $raceId => $driverPoints)
        {
            $this->minFantasyPointsByRaceId[$raceId] = 99999;
            $this->maxFantasyPointsByRaceId[$raceId] = 0;

            $points = -1;
            $rank = 0;
            $tied = 1;
            foreach($driverPoints as $userId => $result)
            {
                if($points == $result)
                    $tied++;
                else
                {
                    $rank += $tied;
                    $tied = 1;
                    $points = $result;
                }

                $this->fantasyPoints[$raceId][$userId] = $rank;
            }

            $max = 100;
            foreach($this->fantasyPoints[$raceId] as $userId => $rank)
            {
                //*
                $pts = 0;
                if($rank == 1 && $points <= 0)
                    $pts = 185 - $max;
                else if($rank == 1)
                    $pts = 185;
                else if($rank <= 6)
                    $pts = 150 + (6 - $rank) * 5;
                else if($rank <= 11)
                    $pts = 130 + (11 - $rank) * 4;
                else if($rank <= 43)
                    $pts = 34 + (43 - $rank) * 3;

                $pts -= 185 - $max;

                $this->maxFantasyPointsByRaceId[$raceId] = max($this->maxFantasyPointsByRaceId[$raceId], $pts);
                if($pts != 0)
                    $this->minFantasyPointsByRaceId[$raceId] = min($this->minFantasyPointsByRaceId[$raceId], $pts);

                $this->fantasyPoints[$raceId][$userId] = $pts;

                if(array_key_exists($raceId, $this->forPoints) && $this->forPoints[$raceId])
                {
                    if(!array_key_exists($userId, $this->totalFantasyPoints))
                        $this->totalFantasyPoints[$userId] = 0;
                    $this->totalFantasyPoints[$userId] += $pts;
                }
            }
            arsort($this->fantasyPoints[$raceId], SORT_NUMERIC);

            if($this->minFantasyPointsByRaceId[$raceId] == 99999)
                $this->minFantasyPointsByRaceId[$raceId] = 0;
        }

        arsort($this->totalFantasyPoints, SORT_NUMERIC);
    }


    public static function getUsingRace(LDS_Race $race)
    {
        if(self::$cacheByRaceId->containsKey($race->getId()))
            return self::$cacheByRaceId->get($race->getId());
        else
        {
            $results = new LDS_FantasyResults();
            $results->race = $race;

            $results->initDriverPoints($race);
            $results->initFantasyPoints();

            self::$cacheByRaceId->add($race->getId(), $results);
            return $results;
        }
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
