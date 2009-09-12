<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/factory/Race.php';

//*
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
require_once PATH_LIB . 'com/mephex/db/Database.php';
//*/


require_once PATH_LIB . 'com/mephex/data-object/DataObject.php';


class LDS_Race extends MXT_DataObject
{
    const RESULT_OFFICIAL = 1;
    const RESULT_UNOFFICIAL = 2;
    const RESULT_LINEUP = 3;
    const RESULT_ENTRY_LIST = 4;




    protected $results;
    protected $driverStandings;
    protected $fantasyPicks;
    protected $fantasyResults;



    public function getId()
    {
        return $this->getValue('raceId');
    }

    public function getNumber()
    {
        return $this->getValue('raceNo');
    }

    public function getName()
    {
        return $this->getValue('name');
    }

    public function getDate()
    {
        return $this->getValue('date');
    }

    public function isChaseRace()
    {
        $chaseRaceDate = $this->getSeason()->getChaseRaceDate();
        if(is_null($chaseRaceDate))
            return false;

        return ($this->getDate()->compareTo($chaseRaceDate) > 0);
    }

    public function getSeason()
    {
        return $this->getValue('season');
    }

    public function getTrack()
    {
        return $this->getValue('track');
    }

    public function getTvStation()
    {
        return $this->getValue('station');
    }

    public function getNascarComId()
    {
        return $this->getValue('nascarComId');
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
        return $this->getValue('official');
    }

    public function isForPoints()
    {
        return $this->getValue('forPoints') == '1';
    }

    public function getPickStatus()
    {
        $status = $this->getValue('pickStatus');
        if($status == '2')
        {
            $picksDate = $this->getDate()->changeMinute(-5);
            if($picksDate->compareTo(new Date()) > 0)
                $status = 1;
            else
                $status = 0;
        }

        return $status;
    }

    public function getLastUpdated()
    {
        return $this->getValue('lastUpdated');
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
        return self::getUsingClassNameAndObjectId(__CLASS__ . 'Class', $id);
    }

    public static function getAll()
    {
        return self::getAllUsingClassName(__CLASS__ . 'Class');
    }



    public static function getUsingRaceNumAndSeasonId($raceNo, $seasonId)
    {
        $class = LDS_RaceClass::getSingleton();
        return $class->getUsingRaceNumAndSeasonId($raceNo, $seasonId);
    }

    public static function getUsingRaceNumYearAndSeriesId($raceNo, $year, $seriesId)
    {
        $class = LDS_RaceClass::getSingleton();
        return $class->getUsingRaceNumYearAndSeriesId($raceNo, $year, $seriesId);
    }

    public static function getLastCompletedUsingSeason(LDS_Season $season)
    {
        $class = LDS_RaceClass::getSingleton();
        return $class->getLastCompletedUsingSeason($season);
    }

    public static function getLastCompletedUsingSeasonId($seasonId)
    {
        $class = LDS_RaceClass::getSingleton();
        return $class->getLastCompletedUsingSeasonId($seasonId);
    }

    public static function getCurrentUsingSeason(LDS_Season $season)
    {
        $class = LDS_RaceClass::getSingleton();
        return $class->getCurrentUsingSeason($season);
    }

    public static function getCurrentUsingSeasonId($seasonId)
    {
        $class = LDS_RaceClass::getSingleton();
        return $class->getCurrentUsingSeasonId($seasonId);
    }

    public static function getAfterRace(LDS_Race $race, $maxCount = 0)
    {
        $class = LDS_RaceClass::getSingleton();
        return $class->getAfterRace($race, $maxCount);
    }

    public static function getBeforeRace(LDS_Race $race, $maxCount = 0)
    {
        $class = LDS_RaceClass::getSingleton();
        return $class->getBeforeRace($race, $maxCount);
    }

    public static function getAllUsingSeason(LDS_Season $season)
    {
        $class = LDS_RaceClass::getSingleton();
        return $class->getAllUsingSeason($season);
    }
}



?>
