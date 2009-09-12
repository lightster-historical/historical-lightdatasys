<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/factory/Season.php';

require_once PATH_LIB . 'com/mephex/data-object/DataObject.php';

require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPlayer.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Series.php';
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


class LDS_Season extends MXT_DataObject
{
    protected $chaseRaceDateLoaded;
    protected $chaseRaceDate;

    protected $races;
    protected $fantasyPlayers;


    public function __construct(MXT_DataClass $class)
    {
        parent::__construct($class);

        $this->chaseRaceDateLoaded = false;
        $this->chaseRaceDate = null;
        $this->races = null;
        $this->fantasyPlayers = null;
    }


    public function getId()
    {
        return $this->getValue('seasonId');
    }

    public function getPairValue()
    {
        return $this->getYear() . ' ' . $this->getSeries()->getName();
    }


    public function getYear()
    {
        return $this->getValue('year');
    }

    public function getRaceCount()
    {
        return $this->getValue('raceCount');
    }

    public function getChaseRaceNo()
    {
        return $this->getValue('chaseRaceNo');
    }

    public function getChaseRaceDate()
    {
        if($this->getChaseRaceNo() <= 0)
            return null;
        else if(!$this->chaseRaceDateLoaded)
        {
            $num = intval($this->getChaseRaceNo()) - 1;
            if($num < 0)
                $num = 0;

            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT date FROM ' . $db->getTable('Race')
                . ' WHERE seasonId=' . $this->getId() . ' AND forPoints=1'
                . ' ORDER BY date ASC LIMIT ' . $num . ',1');
            $result = $db->execQuery($query);
            if($row = $db->getAssoc($result))
                $this->chaseRaceDate = new Date($row['date']);

            $this->chaseRaceDateLoaded = true;
        }

        return $this->chaseRaceDate;
    }

    public function getChaseDriverCount()
    {
        return $this->getValue('chaseDriverCount');
    }

    public function getSeries()
    {
        return $this->getValue('series');
    }

    public function getMaxPickCount()
    {
        return $this->getValue('maxPickCount');
    }

    public function getRaces()
    {
        if(is_null($this->races))
            $this->races = LDS_Race::getAllUsingSeason($this);

        return $this->races;
    }

    public function getFantasyPlayers()
    {
        if(is_null($this->fantasyPlayers))
            $this->fantasyPlayers = LDS_FantasyPlayer::getAllUsingSeason($this);

        return $this->fantasyPlayers;
    }


    public static function getUsingId($id)
    {
        return self::getUsingClassNameAndObjectId(__CLASS__ . 'Class', $id);
    }

    public static function getAll()
    {
        return self::getAllUsingClassName(__CLASS__ . 'Class');
    }



    public static function getUsingSeriesAndYear(LDS_Series $series, $year)
    {
        $class = LDS_SeasonClass::getSingleton();
        return $class->getUsingSeriesAndYear($series, $year);
    }

    public static function getUsingSeriesIdAndYear($seriesId, $year)
    {
        $class = LDS_SeasonClass::getSingleton();
        return $class->getUsingSeriesIdAndYear($seriesId, $year);
    }

    public static function getUsingSeriesId($seriesId)
    {
        $class = LDS_SeasonClass::getSingleton();
        return $class->getUsingSeriesId($seriesId);
    }

    public static function getUsingSeries(LDS_Series $series)
    {
        $class = LDS_SeasonClass::getSingleton();
        return $class->getUsingSeries($series);
    }
}


?>
