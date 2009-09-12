<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/model/Race.php';


/*
require_once PATH_LIB . 'com/lightdatasys/nascar/DriverStanding.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/DriverStandings.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPicks.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyResults.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Result.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Results.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Season.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Series.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Track.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/TvStation.php';
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/core/Input.php';
require_once PATH_LIB . 'com/mephex/core/Pair.php';
require_once PATH_LIB . 'com/mephex/core/Range.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/form/Form.php';
require_once PATH_LIB . 'com/mephex/form/FormError.php';
require_once PATH_LIB . 'com/mephex/form/field/BooleanField.php';
require_once PATH_LIB . 'com/mephex/form/field/DateField.php';
require_once PATH_LIB . 'com/mephex/form/field/HiddenIdField.php';
require_once PATH_LIB . 'com/mephex/form/field/InputField.php';
require_once PATH_LIB . 'com/mephex/form/field/IntegerField.php';
require_once PATH_LIB . 'com/mephex/form/field/SetField.php';
require_once PATH_LIB . 'com/mephex/form/field/SubmitField.php';
require_once PATH_LIB . 'com/mephex/form/field/TimeField.php';
require_once PATH_LIB . 'com/mephex/form/field/constraint/DefaultConstraint.php';
require_once PATH_LIB . 'com/mephex/form/fieldset/Fieldset.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


LDS_Race::initStaticVariables();


class LDS_Race
{
    const RESULT_OFFICIAL = 1;
    const RESULT_UNOFFICIAL = 2;
    const RESULT_LINEUP = 3;
    const RESULT_ENTRY_LIST = 4;


    protected static $staticInitialized = false;

    protected static $cacheById;
    protected static $cacheByRaceNum;


    protected $id;
    protected $name;
    protected $date;

    protected $seasonId;
    protected $trackId;
    protected $tvId;

    protected $season;
    protected $track;
    protected $tv;

    protected $nascarComId;

    protected $resultsType;
    protected $forPoints;
    protected $pickStatus;

    protected $lastUpdated;

    protected $results;
    protected $driverStandings;
    protected $fantasyPicks;
    protected $fantasyResults;


    protected function __construct()
    {
        $this->id = 0;
        $this->number = 0;
        $this->name = '';
        $this->date = null;

        $this->seasonId = 0;
        $this->trackId = 0;
        $this->tvId = 0;

        $this->season = null;
        $this->track = null;
        $this->tv = null;

        $this->official = false;
        $this->resultsType = null;
        $this->forPoints = true;

        $this->lastUpdated = null;

        $this->results = null;
        $this->driverStandings = null;
        $this->fantasyPicks = null;
        $this->fantasyResults = null;
    }


    public function getId()
    {
        return $this->id;
    }

    public function getNumber()
    {
        return $this->number;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getDate()
    {
        return new Date($this->date);
    }

    public function getSeason()
    {
        if(is_null($this->season))
            $this->season = LDS_Season::getUsingId($this->seasonId);

        return $this->season;
    }

    public function getTrack()
    {
        if(is_null($this->track))
            $this->track = LDS_Track::getUsingId($this->trackId);

        return $this->track;
    }

    public function getTvStation()
    {
        if(is_null($this->tv))
            $this->tv = LDS_TvStation::getUsingId($this->tvId);

        return $this->tv;
    }

    public function getNascarComId()
    {
        return $this->nascarComId;
    }

    public function isOfficial()
    {
        return ($this->getResultsType() == self::RESULT_OFFICIAL);
    }

    public function hasResults()
    {
        return ($this->getResultsType() == self::RESULT_OFFICIAL
            || $this->getResultsType() == self::RESULT_UNOFFICIAL);
    }

    public function hasLineup()
    {
        return ($this->getResultsType() == self::RESULT_LINEUP);
    }

    public function hasEntryList()
    {
        return ($this->getResultsType() == self::RESULT_ENTRY_LIST);
    }

    public function getResultsType()
    {
        return $this->resultsType;
    }

    public function isForPoints()
    {
        return $this->forPoints;
    }

    public function getPickStatus()
    {
        return $this->pickStatus;
    }

    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }


    public function getResults()
    {
        if(is_null($this->results))
            $this->results = LDS_Results::getUsingRace($this);

        return $this->results;
    }

    public function getDriverStandings()
    {
        if(is_null($this->driverStandings))
            $this->driverStandings = LDS_DriverStandings::getUsingRace($this);

        return $this->driverStandings;
    }

    public function getFantasyPicks()
    {
        if(is_null($this->fantasyPicks))
            $this->fantasyPicks = LDS_FantasyPicks::getUsingRace($this);

        return $this->fantasyPicks;
    }

    public function getFantasyResults()
    {
        if(is_null($this->fantasyResults))
            $this->fantasyResults = LDS_FantasyResults::getUsingRace($this);

        return $this->fantasyResults;
    }

    public function getMinFantasyPoints()
    {
        return $this->getFantasyResults()->getMinFantasyPointsUsingRace($this);
    }

    public function getMaxFantasyPoints()
    {
        return $this->getFantasyResults()->getMaxFantasyPointsUsingRace($this);
    }

    public function getMinFantasyDriverPoints()
    {
        return $this->getFantasyResults()->getMinDriverPointsUsingRace($this);
    }

    public function getMaxFantasyDriverPoints()
    {
        return $this->getFantasyResults()->getMaxDriverPointsUsingRace($this);
    }


    public static function getUsingId($id)
    {
        $id = intval($id);

        if(self::$cacheById->containsKey($id))
            return self::$cacheById->get($id);
        else if($id > 0)
        {
            return self::constructUsingRow(self::getRowUsingId($id));
        }

        return null;
    }

    public static function getUsingRaceNumAndSeasonId($raceNo, $seasonId)
    {
        $raceNo = intval($raceNo);
        $seasonId = intval($seasonId);

        if(self::$cacheByRaceNum->containsKey($seasonId . $raceNo))
            return self::$cacheByRaceNum->get($seasonId . $raceNo);
        else if($raceNo > 0 && $year > 0)
        {
            return self::constructUsingRow(self::getRowUsingRaceNumAndSeasonId($raceNo, $seasonId));
        }

        return null;
    }

    public static function getRowUsingRaceNumAndSeason($raceNo, LDS_Season $season)
    {
        return self::getRowUsingRaceNumAndSeasonId($raceNo, $season->getId());
    }

    public static function getUsingRaceNumYearAndSeriesId($raceNo, $year, $seriesId)
    {
        $raceNo = intval($raceNo);
        $year = intval($year);
        $seriesId = intval($seriesId);

        return self::constructUsingRow(self::getRowUsingRaceNumYearAndSeriesId($raceNo, $year, $seriesId));
    }

    public static function getLastCompletedUsingSeason(LDS_Season $season)
    {
        return self::getLastCompletedUsingSeasonId($season->getId());
    }

    public static function getLastCompletedUsingSeasonId($seasonId)
    {
        $seasonId = intval($seasonId);

        if($seasonId > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT r.raceId, r.name, date, t.name AS trackName,'
                . ' r.seasonId, t.trackId, '
                . ' raceNo, official, forPoints, lastUpdated,'
                . ' tv.stationId AS tvId, tv.name AS tvName, season.*,'
                . ' IFNULL(r.pickStatus,0) AS pickStatus'
                . ' FROM ' . $db->getTable('Race') . ' AS r LEFT JOIN '
                . $db->getTable('Track') . ' AS t ON r.trackId=t.trackId '
                . ' INNER JOIN ' . $db->getTable('Season') . ' AS season'
                . ' ON r.seasonId=season.seasonId'
                . ' LEFT JOIN ' . $db->getTable('TvStation') . ' AS tv'
                . ' ON r.stationId=tv.stationId'
                . ' WHERE r.seasonId=' . $seasonId
                . ' AND r.official IN (' . self::RESULT_UNOFFICIAL . ','
                . self::RESULT_OFFICIAL . ') ORDER BY r.date DESC LIMIT 1');
            $result = $db->execQuery($query);
            return self::constructUsingRow($db->getAssoc($result));
        }

        return null;
    }

    public static function getCurrentUsingSeason(LDS_Season $season)
    {
        return self::getCurrentUsingSeasonId($season->getId());
    }

    public static function getCurrentUsingSeasonId($seasonId)
    {
        $seasonId = intval($seasonId);

        if($seasonId > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT r.raceId, r.name, date, t.name AS trackName,'
                . ' r.seasonId, t.trackId, '
                . ' raceNo, official, forPoints, lastUpdated,'
                . ' tv.stationId AS tvId, tv.name AS tvName, season.*,'
                . ' IFNULL(r.pickStatus,0) AS pickStatus'
                . ' FROM ' . $db->getTable('Race') . ' AS r LEFT JOIN '
                . $db->getTable('Track') . ' AS t ON r.trackId=t.trackId '
                . ' INNER JOIN ' . $db->getTable('Season') . ' AS season'
                . ' ON r.seasonId=season.seasonId'
                . ' LEFT JOIN ' . $db->getTable('TvStation') . ' AS tv'
                . ' ON r.stationId=tv.stationId'
                . ' WHERE r.seasonId=' . $seasonId
                . ' ORDER BY r.official IN (' . self::RESULT_UNOFFICIAL . ','
                . self::RESULT_OFFICIAL . ') ASC, r.date ASC LIMIT 1');
            $result = $db->execQuery($query);
            $row = $db->getAssoc($result);
            return self::constructUsingRow($row);
        }

        return null;
    }

    public static function getAfterRace(LDS_Race $race, $maxCount = 0)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $maxCount = intval($maxCount);

        $limit = '';
        if($maxCount > 0)
            $limit = ' LIMIT ' . $maxCount;

        $races = array();
        $query = new Query('SELECT r.raceId, r.name, date, t.name AS trackName,'
            . ' r.seasonId, t.trackId, '
            . ' raceNo, official, forPoints, lastUpdated, tv.stationId AS tvId,'
            . ' tv.name AS tvName, season.*,'
            . ' IFNULL(r.pickStatus,(SELECT IF(official=1,2,0) FROM ' . $db->getTable('Race')
            . ' WHERE seasonId=r.seasonId AND date<r.date ORDER BY date DESC LIMIT 1)) AS pickStatus'
            . ' FROM '
            . $db->getTable('Race') . ' AS r LEFT JOIN '
            . $db->getTable('Track') . ' AS t ON r.trackId=t.trackId '
            . ' INNER JOIN ' . $db->getTable('Season') . ' AS season'
            . ' ON r.seasonId=season.seasonId'
            . ' LEFT JOIN ' . $db->getTable('TvStation') . ' AS tv'
            . ' ON r.stationId=tv.stationId'
            . ' WHERE r.date>\'' . $race->getDate()->format('q Q') . '\''
            . ' AND r.seasonId=' . $race->getSeason()->getId()
            . ' ORDER BY r.date ASC'
            . $limit);
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
            $races[] = self::constructUsingRow($row);

        return $races;
    }

    public static function getBeforeRace(LDS_Race $race, $maxCount = 0)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $maxCount = intval($maxCount);

        $limit = '';
        if($maxCount > 0)
            $limit = ' LIMIT ' . $maxCount;

        $races = array();
        $query = new Query('SELECT r.raceId, r.name, date, t.name AS trackName,'
            . ' r.seasonId, t.trackId, '
            . ' raceNo, official, forPoints, lastUpdated, tv.stationId AS tvId,'
            . ' tv.name AS tvName, season.*,'
            . ' IFNULL(r.pickStatus,(SELECT IF(official=1,2,0) FROM ' . $db->getTable('Race')
            . ' WHERE seasonId=r.seasonId AND date<r.date ORDER BY date DESC LIMIT 1)) AS pickStatus'
            . ' FROM '
            . $db->getTable('Race') . ' AS r LEFT JOIN '
            . $db->getTable('Track') . ' AS t ON r.trackId=t.trackId '
            . ' INNER JOIN ' . $db->getTable('Season') . ' AS season'
            . ' ON r.seasonId=season.seasonId'
            . ' LEFT JOIN ' . $db->getTable('TvStation') . ' AS tv'
            . ' ON r.stationId=tv.stationId'
            . ' WHERE r.date<\'' . $race->getDate()->format('q Q') . '\''
            . ' AND r.seasonId=' . $race->getSeason()->getId()
            . ' ORDER BY r.date DESC'
            . $limit);
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
            $races[] = self::constructUsingRow($row);

        return $races;
    }

    public static function getAllUsingSeason(LDS_Season $season)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $races = array();

        $query = new Query('SELECT r.raceId, r.name, date, t.name AS trackName,'
            . ' r.seasonId, t.trackId, '
            . ' raceNo, official, forPoints, lastUpdated, tv.stationId AS tvId,'
            . ' tv.name AS tvName, '
            . ' IFNULL(r.pickStatus,(SELECT IF(official=1,2,0) FROM ' . $db->getTable('Race')
            . ' WHERE seasonId=r.seasonId AND date<r.date ORDER BY date DESC LIMIT 1)) AS pickStatus'
            . ' FROM '
            . $db->getTable('Race') . ' AS r LEFT JOIN '
            . $db->getTable('Track') . ' AS t ON r.trackId=t.trackId '
            . ' LEFT JOIN ' . $db->getTable('TvStation') . ' AS tv'
            . ' ON r.stationId=tv.stationId'
            . ' WHERE r.seasonId=' . $season->getId()
            . ' ORDER BY r.forPoints DESC, `date` ASC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $row['chaseRaceNo'] = -1;
            $row['chaseDriverCount'] = -1;
            $row['seriesId'] = -1;
            $row['maxPickCount'] = -1;
            //$row['seasonId'] = -1;

            $races[$row['raceId']] = self::constructUsingRow($row);
        }

        return $races;
    }


    public static function getRowUsingId($id)
    {
        $id = intval($id);

        if($id > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT r.raceId, r.name, date, t.name AS trackName,'
                . ' r.seasonId, t.trackId, '
                . ' raceNo, official, forPoints, lastUpdated, tv.stationId AS tvId,'
                . ' tv.name AS tvName, season.*,'
                . ' IFNULL(r.pickStatus,(SELECT IF(official=1,2,0) FROM ' . $db->getTable('Race')
                . ' WHERE seasonId=r.seasonId AND date<r.date ORDER BY date DESC LIMIT 1)) AS pickStatus'
                . ' FROM '
                . $db->getTable('Race') . ' AS r LEFT JOIN '
                . $db->getTable('Track') . ' AS t ON r.trackId=t.trackId '
                . ' INNER JOIN ' . $db->getTable('Season') . ' AS season'
                . ' ON r.seasonId=season.seasonId'
                . ' LEFT JOIN ' . $db->getTable('TvStation') . ' AS tv'
                . ' ON r.stationId=tv.stationId'
                . ' WHERE raceId=' . $id);
            $result = $db->execQuery($query);
            $row = $db->getAssoc($result);

            return $row;
        }

        return null;
    }

    public static function getRowUsingRaceNumAndSeasonId($raceNo, $seasonId)
    {
        $raceNo = intval($raceNo);
        $seasonId = intval($seasonId);

        if($raceNo > 0 && $seasonId > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT r.raceId, r.name, date, t.name AS trackName,'
                . ' r.seasonId, t.trackId, '
                . ' raceNo, official, forPoints, lastUpdated,'
                . ' tv.stationId AS tvId, tv.name AS tvName, season.*,'
                . ' IFNULL(r.pickStatus,(SELECT IF(official=1,2,0) FROM ' . $db->getTable('Race')
                . ' WHERE seasonId=r.seasonId AND date<r.date ORDER BY date DESC LIMIT 1)) AS pickStatus'
                . ' FROM '
                . $db->getTable('Race') . ' AS r LEFT JOIN '
                . $db->getTable('Track') . ' AS t ON r.trackId=t.trackId '
                . ' INNER JOIN ' . $db->getTable('Season') . ' AS season'
                . ' ON r.seasonId=season.seasonId'
                . ' LEFT JOIN ' . $db->getTable('TvStation') . ' AS tv'
                . ' ON r.stationId=tv.stationId'
                . ' WHERE raceNo=' . $raceNo . ' AND seasonId=' . $seasonId);
            $result = $db->execQuery($query);
            $row = $db->getAssoc($result);

            return $row;
        }

        return null;
    }

    public static function getRowUsingRaceNumYearAndSeriesId($raceNo, $year, $seriesId)
    {
        $raceNo = intval($raceNo);
        $year = intval($year);
        $seriesId = intval($seriesId);

        if($raceNo > 0 && $year > 0 && $seriesId)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT r.raceId, r.name, date, t.name AS trackName,'
                . ' r.seasonId, t.trackId, '
                . ' raceNo, official, forPoints, lastUpdated,'
                . ' tv.stationId AS tvId, tv.name AS tvName, season.*,'
                . ' IFNULL(r.pickStatus,(SELECT IF(official=1,2,0) FROM ' . $db->getTable('Race')
                . ' WHERE seasonId=r.seasonId AND date<r.date ORDER BY date DESC LIMIT 1)) AS pickStatus'
                . ' FROM '
                . $db->getTable('Race') . ' AS r LEFT JOIN '
                . $db->getTable('Track') . ' AS t ON r.trackId=t.trackId '
                . ' INNER JOIN ' . $db->getTable('Season') . ' AS season'
                . ' ON r.seasonId=season.seasonId'
                . ' LEFT JOIN ' . $db->getTable('TvStation') . ' AS tv'
                . ' ON r.stationId=tv.stationId'
                . ' WHERE raceNo=' . $raceNo . ' AND season.year=' . $year
                . ' AND season.seriesId=' . $seriesId);
            $result = $db->execQuery($query);
            $row = $db->getAssoc($result);

            return $row;
        }

        return null;
    }

    public static function constructUsingRow($row)
    {
        if($row)
        {
            $id = Utility::getValueUsingKey($row, 'raceId');

            if(self::$cacheById->containsKey($id))
                return self::$cacheById->get($id);
            else if($row)
            {
                $obj = new LDS_Race();
                $obj->initUsingRow($row);

                return $obj;
            }
        }

        return null;
    }

    public function initUsingRow($row)
    {
        if($row)
        {
            $this->id = Utility::getValueUsingKey($row, 'raceId');
            self::$cacheById->add($this->getId(), $this);

            $this->number = Utility::getValueUsingKey($row, 'raceNo');
            $this->name = Utility::getValueUsingKey($row, 'name');
            $this->date = new Date(Utility::getValueUsingKey($row, 'date'));

            $this->seasonId = Utility::getValueUsingKey($row, 'seasonId');
            $this->trackId = Utility::getValueUsingKey($row, 'trackId');
            $this->tvId = Utility::getValueUsingKey($row, 'tvId');

            $seasonRow = array
            (
                'seasonId' => Utility::getValueUsingKey($row, 'seasonId'),
                'year' => $this->date->format('Y'),
                'raceCount' => -1,
                'chaseRaceNo' => Utility::getValueUsingKey($row, 'chaseRaceNo'),
                'chaseDriverCount' => Utility::getValueUsingKey($row, 'chaseDriverCount'),
                'seriesId' => Utility::getValueUsingKey($row, 'seriesId'),
                'maxPickCount' => Utility::getValueUsingKey($row, 'maxPickCount')
            );
            //$this->season = LDS_Season::constructUsingRow($seasonRow);

            $trackRow = array
            (
                'trackId' => Utility::getValueUsingKey($row, 'trackId'),
                'name' => Utility::getValueUsingKey($row, 'trackName'),
                'shortName' => null,
                'location' => null
            );
            //$this->track = LDS_Track::constructUsingRow($trackRow);

            $tvRow = array
            (
                'stationId' => Utility::getValueUsingKey($row, 'tvId'),
                'name' => Utility::getValueUsingKey($row, 'tvName')
            );
            //$this->tv = LDS_TvStation::constructUsingRow($tvRow);

            $this->resultsType = Utility::getValueUsingKey($row, 'official');
            $this->forPoints = (Utility::getValueUsingKey($row, 'forPoints') == '1');

            $this->pickStatus = Utility::getValueUsingKey($row, 'pickStatus');
            if($this->pickStatus == '2')
            {
                $picksDate = $this->getDate()->changeMinute(-5);
                if($picksDate->compareTo(new Date()) > 0)
                    $this->pickStatus = 1;
                else
                    $this->pickStatus = 0;
            }

            $this->lastUpdated = new Date(Utility::getValueUsingKey($row, 'lastUpdated'));

            self::$cacheByRaceNum->add($this->getSeason()->getYear() . $this->getNumber(), $this);
        }
    }


    public function updateUsingForm(LDS_RaceForm $form)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        if($form->isValidated())
        {
            $raceId = $form->getValue('submit', 'raceId');

            $trackId = $form->getValue('race_info', 'track')->left;
            $seasonId = $form->getValue('race_info', 'season')->left;
            $raceNo = $form->getValue('race_info', 'race_no');
            $name = $form->getValue('race_info', 'name');
            $stationId = $form->getValue('race_info', 'tv_station')->left;
            $date = $form->getValue('race_info', 'date');
            $time = $form->getValue('race_info', 'time');
            $datetime = new Date($date->format('q', 0) . ' ' . $time->format('Q', 0));
            $forPoints = $form->getValue('race_info', 'for_points') ? '1' : '0';

            $query = new Query("UPDATE " . $db->getTable('Race') . " SET"
                . " `trackId`=$trackId, `seasonId`=$seasonId, `raceNo`=$raceNo,"
                . " `name`='" . addslashes($name) . "', `stationId`=$stationId,"
                . " `date`='" . $datetime->format('q Q') . "',"
                . " `forPoints`=$forPoints, `lastUpdated`='"
                . Date::now('q Q') . "' WHERE `raceId`=$raceId");
            if($db->execQuery($query))
            {
                $this->initUsingRow(self::getRowUsingId($raceId));
                return $this;
            }
        }

        return false;
    }

    public static function createUsingForm(LDS_RaceForm $form)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        if($form->isValidated())
        {
            $trackId = $form->getValue('race_info', 'track')->left;
            $seasonId = $form->getValue('race_info', 'season')->left;
            $raceNo = $form->getValue('race_info', 'race_no');
            $name = $form->getValue('race_info', 'name');
            $stationId = $form->getValue('race_info', 'tv_station')->left;
            $date = $form->getValue('race_info', 'date');
            $time = $form->getValue('race_info', 'time');
            $datetime = new Date($date->format('q', 0) . ' ' . $time->format('Q', 0));
            $forPoints = $form->getValue('race_info', 'for_points') ? '1' : '0';

            $query = new Query('INSERT INTO ' . $db->getTable('Race')
                . ' (`trackId`, `seasonId`, `raceNo`, `name`, `stationId`, `date`,'
                . ' `forPoints`, `lastUpdated`) VALUES ('
                . $trackId . ', ' . $seasonId . ',' . $raceNo . ', \'' . addslashes($name)
                . '\', ' . $stationId . ', \'' . $datetime->format('q Q')
                . '\', ' . $forPoints . ', \'' . Date::now('q Q') . '\')');
            if($db->execQuery($query))
            {
                return self::getUsingId($db->getAutoIncrementId());
            }
        }

        return false;
    }


    public static function initStaticVariables()
    {
        if(!self::$staticInitialized)
        {
            self::$cacheById = new MXT_InstanceCache();
            self::$cacheByRaceNum = new MXT_InstanceCache();

            self::$staticInitialized = true;
        }
    }
}



class LDS_RaceForm extends MXT_Form
{
    protected $input;
    protected $validated;

    protected $race;


    public function __construct($action, Input $input, $timezone = 0)
    {
        parent::__construct($action, 'post');

        $this->input = $input;
        $this->validated = false;

        $input->set('raceId', IntegerInput::getInstance());
        $this->race = LDS_Race::getUsingId($input->get('raceId'));

        $this->initFields($timezone);
        $this->setInputs($input);
    }


    public function submit()
    {
        $race = null;

        $this->setValuesUsingInput($this->input);
        if($this->validate())
        {
            $this->validated = true;

            if($this->getValue('submit', 'submit_save') && !is_null($this->race))
            {
                // update race
                $race = $this->race->updateUsingForm($this);
                if($race)
                    $this->validated = true;
                else
                {
                    $this->addError(new MXT_FormError($this, 0, 'Technical issues prevented the update of the race. Please try again later.'));
                    $this->validated = false;
                }
            }
            else if($this->getValue('submit', 'submit_create') && is_null($this->race))
            {
                $race = LDS_Race::createUsingForm($this);
                if($race)
                    $this->validated = true;
                else
                {
                    $this->addError(new MXT_FormError($this, 0, 'Technical issues prevented the creation of the race. Please try again later.'));
                    $this->validated = false;
                }
            }
            else
            {
                $this->addError(new MXT_FormError($this, 0, 'The form seems to be corrupt (invalid race id).'));
                $this->validated = false;
            }
        }
        else
            $this->validated = false;

        if($this->validated)
            return $race;
        else
            return false;
    }

    public function isValidated()
    {
        return $this->validated;
    }


    public function initFields($timezone)
    {
        if(!is_null($this->race))
        {
            $race = $this->race;

            $raceId = $race->getId();
            $seasonId = $race->getSeason()->getId();
            $name = $race->getName();
            $track = $race->getTrack()->getId();
            $date = $race->getDate();
            $tv = $race->getTvStation()->getId();
            $forPoints = $race->isForPoints();
            $raceNo = $race->getNumber();
        }
        else
        {
            $raceId = 0;
            $seasonId = '';
            $name = '';
            $track = '';
            $date = null;
            $tv = '';
            $forPoints = true;
            $raceNo = '';
        }

        $fieldset = new MXT_Fieldset('race_info', 'Race Information');
            $fieldset->addField
            (
                new MXT_SetField
                (
                    'season',
                    new MXT_DefaultConstraint($seasonId),
                    $this->getSeasons()
                )
            );
            $fieldset->addField
            (
                new MXT_IntegerField
                (
                    'race_no',
                    new MXT_DefaultConstraint($raceNo),
                    new MXT_Range(1, 200)
                )
            );
            $fieldset->addField
            (
                new MXT_InputField
                (
                    'name',
                    new MXT_DefaultConstraint($name)
                )
            );
            $fieldset->addField
            (
                new MXT_SetField
                (
                    'track',
                    new MXT_DefaultConstraint($track),
                    $this->getTracks()
                )
            );
            $dateField =
                new MXT_DateField
                (
                    'date',
                    new MXT_DefaultConstraint($date)
                );
            $timeField =
                new MXT_TimeField
                (
                    'time',
                    new MXT_DefaultConstraint($date),
                    $timezone
                );
            $timeField->setDateField($dateField);
            $fieldset->addField
            (
                $dateField
            );
            $fieldset->addField
            (
                $timeField
            );
            $fieldset->addField
            (
                new MXT_SetField
                (
                    'tv_station',
                    new MXT_DefaultConstraint($tv),
                    $this->getTvStations()
                )
            );
            $fieldset->addField
            (
                new MXT_BooleanField
                (
                    'for_points',
                    new MXT_DefaultConstraint($forPoints)
                )
            );
        $this->addFieldset($fieldset);

        $fieldset = new MXT_Fieldset('submit');
            if(is_null($this->race))
            {
                $fieldset->addField
                (
                    new MXT_SubmitField
                    (
                        'submit_create'
                    )
                );
            }
            else
            {
                $fieldset->addField
                (
                    new MXT_SubmitField
                    (
                        'submit_save'
                    )
                );
            }
            $fieldset->addField
            (
                new MXT_HiddenIdField
                (
                    'raceId',
                    new MXT_DefaultConstraint($raceId)
                )
            );
        $this->addFieldset($fieldset);
    }


    public function getSeasons()
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $seasons = array(null);
        $query = new Query('SELECT season.seasonId, season.year, series.shortName FROM '
            . $db->getTable('Season') . ' AS season'
            . ' INNER JOIN ' . $db->getTable('Series') . ' AS series'
            . ' ON season.seriesId=series.seriesId'
            . ' ORDER BY season.year DESC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $seasons[] = new MXT_Pair($row['seasonId'], $row['year'] . ' ' . $row['shortName']);
        }

        return $seasons;
    }

    public function getTracks()
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $tracks = array(null);
        $query = new Query('SELECT trackId, shortName FROM '
            . $db->getTable('Track')
            . ' ORDER BY shortName ASC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $tracks[] = new MXT_Pair($row['trackId'], $row['shortName']);
        }

        return $tracks;
    }

    public function getTvStations()
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $stations = array(null);
        $query = new Query('SELECT stationId, name FROM '
            . $db->getTable('TvStation')
            . ' ORDER BY name ASC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $stations[] = new MXT_Pair($row['stationId'], $row['name']);
        }

        return $stations;
    }
}
*/



?>
