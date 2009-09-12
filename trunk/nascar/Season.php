<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/model/Season.php';


/*
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPlayer.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Series.php';
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


LDS_Season::initStaticVariables();


class LDS_Season
{
    protected static $staticInitialized = false;

    protected static $cacheById;
    protected static $cacheBySeriesIdAndYear;
    protected static $cacheBySeriesId;


    protected $id;
    protected $year;
    protected $raceCount;

    protected $chaseRaceNo;
    protected $chaseDate;
    protected $chaseDriverCount;

    protected $series;

    protected $maxPickCount;

    protected $races;
    protected $fantasyPlayers;


    protected function __construct()
    {
        $this->id = 0;
        $this->year = 0;
        $this->raceCount = -1;

        $this->chaseRaceNo = -1;
        $this->chaseRaceDate = null;
        $this->chaseDriverCount = -1;

        $this->series = null;

        $this->maxPickCount = 0;

        $this->races = null;
        $this->fantasyPlayers = null;
    }


    public function getId()
    {
        return $this->id;
    }

    public function getYear()
    {
        if($this->year <= 0)
            $this->reinit();

        return $this->year;
    }

    public function getRaceCount()
    {
        if($this->raceCount <= -1)
            $this->reinit();

        return $this->raceCount;
    }

    public function getChaseRaceNo()
    {
        if($this->chaseRaceNo <= -1)
            $this->reinit();

        return $this->chaseRaceNo;
    }

    public function getChaseRaceDate()
    {
        if($this->getChaseRaceNo() <= 0)
            return null;
        else if(is_null($this->chaseRaceDate))
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
        }

        return $this->chaseRaceDate;
    }

    public function getChaseDriverCount()
    {
        if($this->chaseDriverCount <= -1)
            $this->reinit();

        return $this->chaseDriverCount;
    }

    public function getSeries()
    {
        if(is_null($this->series))
            $this->reinit();

        //echo '<pre>blah'; print_r($this->series); debug_print_backtrace(); echo '</pre>';
        return $this->series;
    }

    public function getMaxPickCount()
    {
        if($this->maxPickCount <= 0)
            $this->reinit();

        return $this->maxPickCount;
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


    public function reinit()
    {
        $this->initUsingRow(self::getRowUsingId($this->id));
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

    public static function getUsingSeriesAndYear(LDS_Series $series, $year)
    {
        return self::getUsingSeriesIdAndYear($series->getId(), $year);
    }

    public static function getUsingSeriesIdAndYear($seriesId, $year)
    {
        $seriesId = intval($seriesId);
        $year = intval($year);

        if(self::$cacheBySeriesIdAndYear->containsKey($seriesId . $year))
            return self::$cacheBySeriesIdAndYear->get($seriesId . $year);
        else if($seriesId > 0 && $year > 0)
        {
            return self::constructUsingRow(self::getRowUsingSeriesIdAndYear($seriesId, $year));
        }

        return null;
    }

    public static function getUsingSeriesId($seriesId)
    {
        $seriesId = intval($seriesId);

        if(self::$cacheBySeriesId->containsKey($seriesId))
            return self::$cacheBySeriesId->get($seriesId);
        else if($seriesId > 0)
        {
            return self::constructUsingRow(self::getRowUsingSeriesId($seriesId));
        }

        return null;
    }

    public static function getUsingSeries(LDS_Series $series)
    {
        return self::getUsingSeriesId($series->getId());
    }

    public static function getAll()
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $seasons = array();
        $query = new Query('SELECT season.*, COUNT(race.raceId) AS raceCount'
            . ' FROM ' . $db->getTable('Season') . ' AS season'
            . ' INNER JOIN ' . $db->getTable('Race') . ' AS race'
            . ' ON season.seasonId=race.seasonId AND race.forPoints=1'
            . ' GROUP BY season.seasonId'
            . ' ORDER BY season.year DESC, season.seriesId ASC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $seasons[] = self::constructUsingRow($row);
        }

        return $seasons;
    }


    public static function getRowUsingId($id)
    {
        $id = intval($id);

        if($id > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT season.*, COUNT(race.raceId) AS raceCount'
                . ' FROM ' . $db->getTable('Season') . ' AS season'
                . ' INNER JOIN ' . $db->getTable('Race') . ' AS race'
                . ' ON season.seasonId=race.seasonId AND race.forPoints=1'
                . ' WHERE season.seasonId=' . $id
                . ' GROUP BY season.seasonId');
            $result = $db->execQuery($query);
            $row = $db->getAssoc($result);

            return $row;
        }

        return null;
    }

    public static function getRowUsingSeriesIdAndYear($seriesId, $year)
    {
        $seriesId = intval($seriesId);
        $year = intval($year);

        if($seriesId > 0 && $year > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT season.*, COUNT(race.raceId) AS raceCount FROM '
                . $db->getTable('Season') . ' AS season'
                . ' INNER JOIN ' . $db->getTable('Race') . ' AS race'
                . ' ON season.seasonId=race.seasonId AND race.forPoints=1'
                . ' WHERE season.year=' . $year
                . ' AND season.seriesId=' . $seriesId
                . ' GROUP BY season.seasonId');
            $result = $db->execQuery($query);
            $row = $db->getAssoc($result);

            return $row;
        }

        return null;
    }

    public static function getRowUsingSeriesId($seriesId)
    {
        $seriesId = intval($seriesId);

        if($seriesId > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT season.*, COUNT(race.raceId) AS raceCount FROM '
                . $db->getTable('Season') . ' AS season'
                . ' INNER JOIN ' . $db->getTable('Race') . ' AS race'
                . ' ON season.seasonId=race.seasonId AND race.forPoints=1'
                . ' WHERE season.seriesId=' . $seriesId
                . ' GROUP BY season.seasonId ORDER BY season.year DESC LIMIT 1');
            $result = $db->execQuery($query);
            $row = $db->getAssoc($result);

            return $row;
        }

        return null;
    }


    public static function constructUsingRow($row)
    {
        $id = Utility::getValueUsingKey($row, 'seasonId');

        if($id > 0)
        {
            if(self::$cacheById->containsKey($id))
                return self::$cacheById->get($id);
            else if($row)
            {
                $obj = new LDS_Season();
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
            $this->id = Utility::getValueUsingKey($row, 'seasonId');
            self::$cacheById->add($this->getId(), $this);

            $this->year = Utility::getValueUsingKey($row, 'year');
            $this->raceCount = Utility::getValueUsingKey($row, 'raceCount');

            $this->chaseRaceNo = Utility::getValueUsingKey($row, 'chaseRaceNo');
            $this->chaseRaceDate = null;
            $this->chaseDriverCount = Utility::getValueUsingKey($row, 'chaseDriverCount');

            $this->series = LDS_Series::getUsingId(Utility::getValueUsingKey($row, 'seriesId'));
            /*
            $seriesRow = array
            (
                'seriesId' => Utility::getValueUsingKey($row, 'seriesId'),
                'keyname' => null,
                'name' => null,
                'shortName' => null,
                'feedName' => null
            );
            $this->series = LDS_Series::constructUsingRow($seriesRow);
            *//*

            $this->maxPickCount = Utility::getValueUsingKey($row, 'maxPickCount');

            self::$cacheBySeriesIdAndYear->add($this->getSeries()->getId() . $this->getYear(), $this);
            self::$cacheBySeriesId->add($this->getSeries()->getId(), $this);
        }
    }


    public static function initStaticVariables()
    {
        if(!self::$staticInitialized)
        {
            self::$cacheById = new MXT_InstanceCache();
            self::$cacheBySeriesIdAndYear = new MXT_InstanceCache();
            self::$cacheBySeriesId = new MXT_InstanceCache();

            self::$staticInitialized = true;
        }
    }
}
//*/



?>
