<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Result.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/DriverStanding.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class NascarData
{
    const RESULT_OFFICIAL = 1;
    const RESULT_UNOFFICIAL = 2;
    const RESULT_LINEUP = 3;
    const RESULT_ENTRY_LIST = 4;


    protected $seasons;

    protected $races;
    protected $completedRaceNo;

    protected $series;
    protected $season;
    protected $race;
    protected $driverStandings;

    protected $raceId;

    protected $seasonYear;
    protected $raceNo;

    protected $resultsType;

    protected $chaseRaceNo;
    protected $chaseDate;

    protected $standingsByRank;
    protected $standingsByDriverId;

    protected $users;

    protected $fantasyPicks;
    protected $totalDriverPoints;

    protected $weeklyResults;

    protected $points;
    protected $maxPoints;
    protected $minPoints;

    protected $maxPointsPerRace;
    protected $minPointsPerRace;

    protected $maxPickCount;
    protected $maxPurchase;


    public function __construct($seriesId, $seasonYear, $raceNo)
    {
        $this->series = null;
        $this->loadSeriesInformation($seriesId);

        $this->seasons = null;

        $this->races = null;
        $this->completedRaceNo = 0;

        $this->season = null;
        $this->race = null;
        $this->driverStandings = null;

        $this->raceId = -1;

        $this->seasonYear = $seasonYear;
        $this->raceNo = $raceNo;

        $this->resultsType = null;

        $this->chaseRaceNo = -1;
        $this->chaseDate = null;

        $this->standingsByRank = null;
        $this->standingsByDriverId = null;

        $this->users = null;

        $this->fantasyPicks = null;
        $this->totalDriverPoints = null;

        $this->weeklyResults = null;

        $this->points = null;
        $this->maxPoints = null;
        $this->minPoints = null;

        $this->maxPointsPerRace = -99999;
        $this->minPointsPerRace = 99999;

        $this->maxPurchase = -1;
    }


    public function getAllSeries()
    {
        return LDS_Series::getAll();
    }


    public function getSeries()
    {
        if(is_null($this->series))
            $this->loadSeriesInformation();

        return $this->series;
    }

    public function getSeriesId()
    {
        return $this->getSeries()->getId();
    }

    public function getSeriesName()
    {
        return $this->getSeries()->getName();
    }

    public function getSeriesShortName()
    {
        return $this->getSeries()->getShortName();
    }

    public function getSeriesFeedName()
    {
        return $this->getSeries()->getFeedName();
    }

    public function loadSeriesInformation($seriesId = 0)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        if($seriesId <= 0)
            $seriesId = 1;

        if($seriesId > 0 && is_null($this->series))
        {
            $this->series = LDS_Series::getUsingId($seriesId);
        }
    }


    public function getSeasons()
    {
        if(is_null($this->seasons))
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $this->seasons = array();
            $query = new Query('SELECT season.seasonId, season.year FROM '
                . $db->getTable('Season') . ' AS season'
                . ' INNER JOIN ' . $db->getTable('Race') . ' AS race'
                . ' ON season.seasonId=race.seasonId'
                . ' WHERE season.seriesId=' . $this->getSeriesId()
                . ' ORDER BY year DESC');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $this->seasons[$row[0]] = $row[1];
            }
        }

        return $this->seasons;
    }


    public function getSeason()
    {
        if(is_null($this->season))
            $this->loadSeasonInformation();

        return $this->season;
    }

    public function getSeasonId()
    {
        return $this->getSeason()->getId();
    }

    public function getSeasonYear()
    {
        return $this->getSeason()->getYear();
    }

    public function getMaxPickCount()
    {
        return $this->getSeason()->getMaxPickCount();
    }

    public function getSeasonRaceCount()
    {
        return $this->getSeason()->getRaceCount();
    }

    protected function loadSeasonInformation()
    {
        if($this->seasonYear > 0)
        {
            $this->season = LDS_Season::getUsingSeriesIdAndYear($this->getSeriesId(), $this->seasonYear);
        }

        if(is_null($this->season))
        {
            $this->season = LDS_Season::getUsingSeriesId($this->getSeriesId());
        }
    }


    public function getRaceNumber()
    {
        if(is_null($this->races))
        {
            $this->loadRaces();
        }

        return $this->raceNo;
    }

    public function getCompletedRaceNumber()
    {
        if($this->completedRaceNo <= 0)
        {
            $this->loadRaces();
        }

        return $this->completedRaceNo;
    }

    public function getRaces()
    {
        if(is_null($this->races))
        {
            $this->loadRaces();
        }

        return $this->races;
    }


    public function getRace()
    {
        if(is_null($this->race))
            $this->loadRaceInformation();

        return $this->race;
    }

    public function getRaceId()
    {
        if($this->raceId == 0)
            $this->loadRaces();

        return $this->raceId;
    }

    public function getRaceName()
    {
        return $this->getRace()->getName();
    }

    public function getRaceDate()
    {
        return $this->getRace()->getDate();
    }

    public function getTrackName()
    {
        return $this->getRace()->getTrack()->getShortName();
    }

    public function getNascarComId()
    {
        return $this->getRace()->getNumber();
    }

    public function isRaceOfficial()
    {
        return $this->getRace()->isOfficial();
    }

    public function isRaceForPoints()
    {
        return $this->getRace()->isForPoints();
    }

    public function getRaceLastUpdated()
    {
        return $this->getRace()->getLastUpdated();
    }

    public function getRaceTVBroadcast()
    {
        return $this->getRace()->getTvStation()->getName();
    }

    protected function getResultsType()
    {
        return $this->getRace()->getResultsType();
    }

    public function hasResults()
    {
        if($this->getResultsType() == NascarData::RESULT_UNOFFICIAL
            || $this->getResultsType() == NascarData::RESULT_OFFICIAL)
        {
            return true;
        }

        return false;
    }

    public function hasLineup()
    {
        if($this->getResultsType() == NascarData::RESULT_LINEUP)
            return true;

        return false;
    }

    public function hasEntryList()
    {
        if($this->getResultsType() == NascarData::RESULT_ENTRY_LIST)
            return true;

        return false;
    }

    public function getRaceResults()
    {
        return $this->getRace()->getResults();
    }

    public function loadRaceInformation()
    {
        $this->race = LDS_Race::getUsingId($this->getRaceId());
    }



    public function getDriverStandings($blah = null)
    {
        is_null($blah) or die('check getDriverStandings()');

        if(is_null($this->driverStandings))
            $this->loadDriverStandings();

        return $this->driverStandings->getByRank();
    }

    public function getDriverStandingsByDriverId()
    {
        if(is_null($this->driverStandings))
            $this->loadDriverStandings();

        return $this->driverStandings->getByDriverId();
    }

    public function getMaxPurchase()
    {
        if($this->maxPurchase < 0)
            $this->loadDriverStandings();

        return $this->maxPurchase;
    }

    public function loadDriverStandings($zeroPointDrivers = false)
    {
        $this->driverStandings = $race->getDriverStandings();
    }


    public function getFantasyPicks()
    {
        if(is_null($this->fantasyPicks))
            $this->loadFantasyPicks();

        return $this->fantasyPicks;
    }

    public function getTotalDriverPoints()
    {
        if(is_null($this->totalDriverPoints))
            $this->loadFantasyPicks();

        return $this->totalDriverPoints;
    }

    public function loadFantasyPicks($overall = true, $officialOnly = true)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        if($overall)
            $whereRace = ' r.date<=\'' . $this->getRaceDate()->format('q Q') . '\' ';
        else
            $whereRace = ' r.raceId=' . $this->getRaceId() . ' ';

        $this->fantasyPicks = array();
        $this->totalDriverPoints = array();
        $query = new Query('SELECT r.raceId, fp.userId, '
            . ' SUM(IF(finish=1,185,IF(finish<=6, 150+(6-finish)*5,IF(finish<=11, 130+(11-finish)*4,IF(finish<=43, 34+(43-finish)*3,0))))+IF(ledLaps>=1,5,0)+IF(ledMostLaps>=1,5,0)) AS points'
            . ', r.forPoints, r.official'
            . ' FROM nascarFantPick AS fp'
            . ' INNER JOIN nascarRace AS r ON fp.raceId=r.raceId'
            . ' INNER JOIN user AS u ON fp.userId=u.userId'
            . ' LEFT JOIN nascarResult AS re ON r.raceId=re.raceId AND fp.driverId=re.driverId'
            . ' WHERE ' . $whereRace . ' AND seasonId=' . $this->getSeasonId()
            . ' AND fp.deletedTime IS NULL'
            . ' GROUP BY r.raceId, fp.userId'
            . ' ORDER BY points DESC'
            );
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            if(!array_key_exists($row['userId'], $this->totalDriverPoints))
                $this->totalDriverPoints[$row['userId']] = 0;

            if(!$officialOnly || $row['forPoints'] == '1')
            {
                if($row['official'] == NascarData::RESULT_UNOFFICIAL
                    || $row['official'] == NascarData::RESULT_OFFICIAL)
                {
                    $this->fantasyPicks[$row['raceId']][$row['userId']] = $row['points'];
                    $this->totalDriverPoints[$row['userId']] += $row['points'];
                }
                else
                {
                    $this->fantasyPicks[$row['raceId']][$row['userId']] = 0;
                }

            }
        }
    }


    public function getMaxPointsPerRace()
    {
        if(is_null($this->weeklyResults))
            $this->loadFantasyResults(true);

        return $this->maxPointsPerRace;
    }

    public function getMinPointsPerRace()
    {
        if(is_null($this->weeklyResults))
            $this->loadFantasyResults(true);

        return $this->minPointsPerRace;
    }

    public function &getFantasyResults()
    {
        if(is_null($this->weeklyResults))
            $this->loadFantasyResults(true);

        return $this->weeklyResults;
    }

    public function loadFantasyResults()
    {
        $fantasyPicks = $this->getFantasyPicks();
        $this->weeklyResults = array();
        foreach($fantasyPicks as $raceId => $fantasyPick)
        {
            $points = -1;
            $rank = 0;
            $tied = 1;
            foreach($fantasyPick as $userId => $result)
            {

                if($points == $result)
                {
                    $tied++;
                }
                else
                {
                    $rank += $tied;
                    $tied = 1;
                    $points = $result;
                }

                $this->weeklyResults[$raceId][$userId] = $rank;
            }

            $max = 100;
            foreach($this->weeklyResults[$raceId] as $userId => $rank)
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

                $this->maxPointsPerRace = max($this->maxPointsPerRace, $pts);
                if($pts != 0)
                    $this->minPointsPerRace = min($this->minPointsPerRace, $pts);

                $this->weeklyResults[$raceId][$userId] = $pts;
            }
        }

        if($this->minPointsPerRace == 99999)
            $this->minPointsPerRace = 0;
    }



    public function getFantasyPoints()
    {
        if(is_null($this->points))
            $this->loadFantasyPoints();

        return $this->points;
    }

    public function &getFantasyMaxPoints()
    {
        if(is_null($this->maxPoints))
            $this->loadFantasyPoints();

        return $this->maxPoints;
    }

    public function &getFantasyMinPoints()
    {
        if(is_null($this->minPoints))
            $this->loadFantasyPoints();

        return $this->minPoints;
    }

    public function loadFantasyPoints()
    {
        $this->points = array();
        $this->maxPoints = array();
        $this->minPoints = array();

        $this->getFantasyResults();
        foreach($this->weeklyResults as $raceId => $results)
        {
            $this->maxPoints[$raceId] = max($results);
            $this->minPoints[$raceId] = min($results);

            foreach($this->weeklyResults[$raceId] as $userId => $pts)
            {
                if(!array_key_exists($userId, $this->points))
                {
                    $this->points[$userId] = array();
                }

                if($this->maxPoints[$raceId] == 0)
                    $this->points[$userId][$raceId] = 0;
                else
                    $this->points[$userId][$raceId] = $pts;
            }
        }

        $this->getFantasyPlayers();
        foreach($this->users as $userId => $user)
        {
            $this->users[$userId][4] = 0;
            if(array_key_exists($userId, $this->points))
                $this->users[$userId][4] = array_sum($this->points[$userId]);
        }

        function compareUsersByPoints($a, $b)
        {
            $aSum = $a[4];
            $bSum = $b[4];

            if($aSum < $bSum)
                return 1;
            else if($aSum > $bSum)
                return -1;
            else
                return 0;
        }
        uasort($this->users, 'compareUsersByPoints');
    }


    public function &getFantasyPlayers()
    {
        if(is_null($this->users))
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $this->users = array();
            $query = new Query('SELECT u.userId, u.username, p.name, p.bgcolor FROM user AS u '
                . 'INNER JOIN player_user AS pu ON u.userId=pu.userId '
                . 'INNER JOIN player AS p ON pu.playerId=p.playerId '
                . 'INNER JOIN nascarFantPick AS fp ON u.userId=fp.userId AND fp.deletedTime IS NULL '
                . 'INNER JOIN nascarRace AS ra ON fp.raceId=ra.raceId '
                . 'WHERE seasonId=' . $this->getSeasonId()
                . ' GROUP BY userId ORDER BY name');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $this->users[$row[0]] = $row;
            }
        }

        return $this->users;
    }




    public static function getUpcomingRaces($seasonYear, $count, $date = null)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $now = new Date();
        $races = array();

        $where  = ' WHERE (race.date>=\'' . Date::now('q Q') . '\''
            . (!is_null($date) ? (' OR race.date>\'' . $date->format('q Q') . '\'') : '')
            . ') AND season.year=' . intval($seasonYear);

        $lastOfficialBySeries = array();
        $pointsCountBySeries = array();
        $nonPointsCountBySeries = array();
        $lastRace = null;
        $query = new Query('(SELECT race.*, track.shortName AS trackName, season.year, season.maxPickCount, series.shortName, series.seriesId, COUNT(race.raceId) AS raceNo, 1 AS forPoints FROM '
            . $db->getTable('Race') . ' AS race'
            . ' INNER JOIN ' . $db->getTable('Track')
            . ' AS track ON race.trackId=track.trackId'
            . ' INNER JOIN ' . $db->getTable('Season')
            . ' AS season ON race.seasonId=season.seasonId'
            . ' INNER JOIN ' . $db->getTable('Series')
            . ' AS series ON season.seriesId=series.seriesId'
            . ' WHERE race.date<=\'' . Date::now('q Q') . '\' AND'
            . (!is_null($date) ? (' race.date<=\'' . $date->format('q Q') . '\' AND') : '')
            . ' season.year=' . intval($seasonYear) . ' AND race.forPoints=1'
            . ' GROUP BY season.seriesId)'
            . ' UNION'
            . '(SELECT race.*, track.shortName AS trackName, season.year, season.maxPickCount, series.shortName, series.seriesId, 1+SUM(IF((race.forPoints=\'1\' AND race.official=\'1\') OR (race.forPoints=\'0\' AND race.date<=\'' . Date::now('q Q') . '\'),1,0)) AS raceNo, 0 AS forPoints FROM '
            . $db->getTable('Race') . ' AS race'
            . ' INNER JOIN ' . $db->getTable('Track')
            . ' AS track ON race.trackId=track.trackId'
            . ' INNER JOIN ' . $db->getTable('Season')
            . ' AS season ON race.seasonId=season.seasonId'
            . ' INNER JOIN ' . $db->getTable('Series')
            . ' AS series ON season.seriesId=series.seriesId'
            . (!is_null($date) ? (' AND race.date<=\'' . $date->format('q Q') . '\'') : '')
            . ' WHERE season.year=' . intval($seasonYear)
            . ' GROUP BY season.seriesId)'
           //*
            . ' UNION'
            . '(SELECT race.*, track.shortName AS trackName, season.year, season.maxPickCount, series.shortName, series.seriesId, -1 AS raceNo, race.forPoints AS forPoints FROM '
            . $db->getTable('Race') . ' AS race'
            . ' INNER JOIN ' . $db->getTable('Track')
            . ' AS track ON race.trackId=track.trackId'
            . ' INNER JOIN ' . $db->getTable('Season')
            . ' AS season ON race.seasonId=season.seasonId'
            . ' INNER JOIN ' . $db->getTable('Series')
            . ' AS series ON season.seriesId=series.seriesId'
            . $where
            . ' GROUP BY race.raceId'
            . ' ORDER BY `date` ASC'
            . ' LIMIT ' . max(1, intval($count)) . ')'
            //*/
            );
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            if($row['raceNo'] <= -1)
            {
                if($row['pickStatus'] == '')
                {
                    if(array_key_exists($row['seriesId'], $lastOfficialBySeries)
                        && ($lastOfficialBySeries[$row['seriesId']] === false))
                        $row['pickStatus'] = '0';
                    else
                        $row['pickStatus'] = '1';
                }

                if($row['forPoints'] == '1')
                {
                    if(!array_key_exists($row['seriesId'], $pointsCountBySeries))
                        $pointsCountBySeries[$row['seriesId']] = 0;
                    $pointsCountBySeries[$row['seriesId']]++;
                    $row['raceNo'] = $pointsCountBySeries[$row['seriesId']];
                }
                else
                {
                    if(!array_key_exists($row['seriesId'], $pointsCountBySeries))
                        $pointsCountBySeries[$row['seriesId']] = 0;
                    if(!array_key_exists($row['seriesId'], $nonPointsCountBySeries))
                        $nonPointsCountBySeries[$row['seriesId']] = 0;
                    $nonPointsCountBySeries[$row['seriesId']]++;
                    $row['raceNo'] = $nonPointsCountBySeries[$row['seriesId']];
                }

                $races[$row['raceId']] = $row;

                if($row['forPoints'] == '0')
                    $lastOfficialBySeries[$row['seriesId']] = true;
                else if($row['official'] != '1')
                    $lastOfficialBySeries[$row['seriesId']] = false;
            }
            else
            {
                if($row['forPoints'] == '1')
                {
                    $pointsCountBySeries[$row['seriesId']] = $row['raceNo'];
                }
                else
                {
                    $nonPointsCountBySeries[$row['seriesId']] = $row['raceNo'];
                }
            }
        }

        return $races;
    }

    public static function getPickCountByRaceIds($raceIds, $userId)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $raceIds = IntegerInput::getInstance()->parseValue($raceIds);
        $userId = IntegerInput::getInstance()->parseValue($userId);

        $counts = array();

        if(is_array($raceIds) && count($raceIds) > 0)
        {
            $query = new Query('SELECT raceId, COUNT(pickId) FROM '
                . $db->getTable('FantPick')
                . ' WHERE userId=' . $userId . ' AND raceId IN ('
                . implode(',', $raceIds) . ')'
                . ' AND deletedTime IS NULL GROUP BY raceId');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $counts[$row[0]] = $row[1];
            }
        }

        return $counts;
    }


}



?>
