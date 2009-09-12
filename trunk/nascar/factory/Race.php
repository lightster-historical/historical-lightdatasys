<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/factory/Season.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/factory/Track.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/factory/TvStation.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/model/Race.php';

require_once PATH_LIB . 'com/mephex/data-object/class/AbstractDatabaseDataClass.php';
require_once PATH_LIB . 'com/mephex/data-object/type/BooleanType.php';
require_once PATH_LIB . 'com/mephex/data-object/type/DataObjectType.php';
require_once PATH_LIB . 'com/mephex/data-object/type/ModificationTimeType.php';

require_once PATH_LIB . 'com/mephex/data-object/field/BooleanDataField.php';
require_once PATH_LIB . 'com/mephex/data-object/field/ForeignKeyDataField.php';
require_once PATH_LIB . 'com/mephex/data-object/field/ModifiedTimeDataField.php';

require_once PATH_LIB . 'com/mephex/db/Database.php';


class LDS_RaceClass extends MXT_AbstractDatabaseDataClass
{
    protected static $singleton = null;


    public static function getSingleton()
    {
        return self::getSingletonUsingClassName(__CLASS__);
    }


    public function getDataObjectName()
    {
        return 'LDS_Race';
    }


    public function getTableName()
    {
        return 'Race';
    }

    public function getDbConnection()
    {
        return Database::getConnection('com.lightdatasys.nascar');
    }


    public function getCacheableFields()
    {
        $fields = parent::getCacheableFields();

        $fields->addForeignObjectFields('season', 'seasonId', 'LDS_SeasonClass');
        $fields->addForeignObjectFields('track', 'trackId', 'LDS_TrackClass');
        $fields->addForeignObjectFields('station', 'stationId', 'LDS_TvStationClass');
        $fields->addModifiedTimeField('lastUpdated');
        $fields->addBooleanDataField('forPoints');

        return $fields;
    }


    public function getClassFileName()
    {
        return __FILE__;
    }


    public function getGeneralSelectSQL($where = '')
    {
        $tableName = $this->getTableName();
        $db = $this->getDbConnection();

        return 'SELECT mt.*,'
            . ' IFNULL(mt.pickStatus,'
                . '(SELECT IF(official=1,2,0) FROM ' . $db->getTable('Race')
                . ' WHERE seasonId=mt.seasonId AND date<mt.date'
                . ' ORDER BY date DESC LIMIT 1)'
                . ') AS pickStatus'
            . ' FROM ' . $db->getTable($tableName) . ' AS mt'
            . $where;
    }


    public function getUsingRaceNumAndSeasonId($raceNo, $seasonId)
    {
        $raceNo = intval($raceNo);
        $seasonId = intval($seasonId);

        /*if(self::$cacheByRaceNum->containsKey($seasonId . $raceNo))
            return self::$cacheByRaceNum->get($seasonId . $raceNo);
        else*/ if($raceNo > 0 && $seasonId > 0)
        {
            $db = $this->getDbConnection();

            $query = new Query
            (
                $this->getGeneralSelectSQL
                (
                    ' WHERE mt.raceNo=' . $raceNo . ' AND mt.seasonId=' . $seasonId
                )
            );
            return $this->getReader()->constructUsingQuery($query);
        }

        return null;
    }

    public function getUsingRaceNumYearAndSeriesId($raceNo, $year, $seriesId)
    {
        $raceNo = intval($raceNo);
        $year = intval($year);
        $seriesId = intval($seriesId);

        if($raceNo > 0 && $year > 0 && $seriesId > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query
            (
                $this->getGeneralSelectSQL
                (
                    ' INNER JOIN ' . $db->getTable('Season') . ' AS season'
                    . ' ON mt.seasonId=season.seasonId'
                    . ' WHERE mt.raceNo=' . $raceNo . ' AND season.year=' . $year
                    . ' AND season.seriesId=' . $seriesId
                )
            );
            return $this->getReader()->constructUsingQuery($query);
        }

        return null;
    }

    public function getLastCompletedUsingSeason(LDS_Season $season)
    {
        return $this->getLastCompletedUsingSeasonId($season->getId());
    }

    public function getLastCompletedUsingSeasonId($seasonId)
    {
        $seasonId = intval($seasonId);

        if($seasonId > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query
            (
                $this->getGeneralSelectSQL
                (
                    ' WHERE mt.seasonId=' . $seasonId
                    . ' AND mt.official IN (' . LDS_Race::RESULT_UNOFFICIAL . ','
                    . LDS_Race::RESULT_OFFICIAL . ')'
                )
                . ' ORDER BY mt.date DESC LIMIT 1'
            );
            return $this->getReader()->constructUsingQuery($query);
        }

        return null;
    }

    public function getCurrentUsingSeason(LDS_Season $season)
    {
        return $this->getCurrentUsingSeasonId($season->getId());
    }

    public function getCurrentUsingSeasonId($seasonId)
    {
        $seasonId = intval($seasonId);

        if($seasonId > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query
            (
                $this->getGeneralSelectSQL
                (
                    ' WHERE mt.seasonId=' . $seasonId
                )
                . ' ORDER BY mt.official IN (' . LDS_Race::RESULT_UNOFFICIAL . ','
                . LDS_Race::RESULT_OFFICIAL . ') ASC, mt.date ASC LIMIT 1'
            );
            return $this->getReader()->constructUsingQuery($query);
        }

        return null;
    }

    public function getAfterRace(LDS_Race $race, $maxCount = 0)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $maxCount = intval($maxCount);

        $limit = '';
        if($maxCount > 0)
            $limit = ' LIMIT ' . $maxCount;

        $races = array();
        $query = new Query
        (
            $this->getGeneralSelectSQL
            (
                ' WHERE mt.date>\'' . $race->getDate()->format('q Q') . '\''
                . ' AND mt.seasonId=' . $race->getSeason()->getId()
            )
            . ' ORDER BY mt.date ASC' . $limit
        );
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
            $races[] = $this->getReader()->constructUsingRow($row);

        return $races;
    }

    public function getBeforeRace(LDS_Race $race, $maxCount = 0)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $maxCount = intval($maxCount);

        $limit = '';
        if($maxCount > 0)
            $limit = ' LIMIT ' . $maxCount;

        $races = array();
        $query = new Query
        (
            $this->getGeneralSelectSQL
            (
                ' WHERE mt.date<\'' . $race->getDate()->format('q Q') . '\''
                . ' AND mt.seasonId=' . $race->getSeason()->getId()
            )
            . ' ORDER BY mt.date DESC' . $limit
        );
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
            $races[] = $this->getReader()->constructUsingRow($row);

        return $races;
    }

    public function getAllUsingSeason(LDS_Season $season)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $races = array();

        $query = new Query
        (
            $this->getGeneralSelectSQL
            (
                ' WHERE mt.seasonId=' . $season->getId()
            )
            . ' ORDER BY mt.forPoints DESC, mt.`date` ASC'
        );
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
            $races[$row['raceId']] = $this->getReader()->constructUsingRow($row);

        return $races;
    }
}



?>
