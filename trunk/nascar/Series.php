<?php


require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/model/Series.php';


/*
LDS_Series::initStaticVariables();


class LDS_Series
{
    protected static $staticInitialized = false;
    protected static $allLoaded = false;

    protected static $cacheById;


    protected $id;
    protected $keyname;
    protected $name;
    protected $shortName;
    protected $feedName;


    protected function __construct()
    {
        $this->id = 0;
        $this->keyname = '';
        $this->name = '';
        $this->shortName = '';
        $this->feedName = '';
    }


    public function getId()
    {
        return $this->id;
    }

    public function getKeyname()
    {
        if(is_null($this->keyname))
            $this->reinit();

        return $this->keyname;
    }

    public function getName()
    {
        if(is_null($this->name))
            $this->reinit();

        return $this->name;
    }

    public function getShortName()
    {
        if(is_null($this->shortName))
            $this->reinit();

        return $this->shortName;
    }

    public function getFeedName()
    {
        if(is_null($this->feedName))
            $this->reinit();

        return $this->feedName;
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

    public static function getAll()
    {
        if(!self::$allLoaded)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT seriesId, keyname, name, shortName, feedName'
                . ' FROM ' . $db->getTable('Series'));
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
                self::constructUsingRow($row);

            self::$allLoaded = true;
        }

        return self::$cacheById->getAll();
    }

    public static function getRowUsingId($id)
    {
        $id = intval($id);

        if($id > 0)
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $query = new Query('SELECT seriesId, keyname, name, shortName, feedName'
                . ' FROM ' . $db->getTable('Series') . ' WHERE seriesId=' . $id);
            $result = $db->execQuery($query);
            $row = $db->getAssoc($result);

            return $row;
        }

        return null;
    }

    public static function constructUsingRow($row)
    {
        $id = Utility::getValueUsingKey($row, 'seriesId');

        if(self::$cacheById->containsKey($id))
            return self::$cacheById->get($id);
        else if($row)
        {
            $obj = new LDS_Series();
            $obj->initUsingRow($row);

            return $obj;
        }

        return null;
    }

    public function initUsingRow($row)
    {
        if($row)
        {
            $this->id = Utility::getValueUsingKey($row, 'seriesId');
            self::$cacheById->add($this->getId(), $this);

            $this->keyname = Utility::getValueUsingKey($row, 'keyname');
            $this->name = Utility::getValueUsingKey($row, 'name');
            $this->shortName = Utility::getValueUsingKey($row, 'shortName');
            $this->feedName = Utility::getValueUsingKey($row, 'feedName');
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
//*/



?>
