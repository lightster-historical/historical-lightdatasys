<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/model/TvStation.php';


/*
require_once PATH_LIB . 'com/lightdatasys/nascar/Series.php';
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


LDS_TvStation::initStaticVariables();


class LDS_TvStation
{
    protected static $staticInitialized = false;

    protected static $cacheById;


    protected $id;
    protected $name;


    protected function __construct()
    {
        $this->id = 0;
        $this->name = '';
    }


    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        if(is_null($this->name))
            $this->reinit();

        return $this->name;
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

    public static function getRowUsingId($id)
    {
        $id = intval($id);

        if($id > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT stationId, name'
                . ' FROM ' . $db->getTable('TvStation') . ' WHERE stationId=' . $id);
            $result = $db->execQuery($query);
            $row = $db->getAssoc($result);

            return $row;
        }

        return null;
    }

    public static function constructUsingRow($row)
    {
        $id = Utility::getValueUsingKey($row, 'stationId');

        if(self::$cacheById->containsKey($id))
            return self::$cacheById->get($id);
        else if($row)
        {
            $obj = new LDS_TvStation();
            $obj->initUsingRow($row);

            return $obj;
        }

        return null;
    }

    public function initUsingRow($row)
    {
        if($row)
        {
            $this->id = Utility::getValueUsingKey($row, 'stationId');
            self::$cacheById->add($this->getId(), $this);

            $this->name = Utility::getValueUsingKey($row, 'name');
        }
    }


    public static function initStaticVariables()
    {
        if(!self::$staticInitialized)
        {
            self::$cacheById = new MXT_InstanceCache();

            self::$staticInitialized = true;
        }
    }
}
*/



?>
