<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/model/Track.php';


/*
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


LDS_Track::initStaticVariables();


class LDS_Track
{
    protected static $staticInitialized = false;

    protected static $cacheById;


    protected $id;
    protected $name;
    protected $shortName;
    protected $location;


    protected function __construct()
    {
        $this->id = 0;
        $this->name = '';
        $this->shortName = '';
        $this->location = '';
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

    public function getShortName()
    {
        if(is_null($this->shortName))
            $this->reinit();

        return $this->shortName;
    }

    public function getLocation()
    {
        if(is_null($this->location))
            $this->reinit();

        return $this->location;
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

            $query = new Query('SELECT trackId, name, shortName, location'
                . ' FROM ' . $db->getTable('Track') . ' WHERE trackId=' . $id);
            $result = $db->execQuery($query);
            $row = $db->getAssoc($result);

            return $row;
        }

        return null;
    }

    public static function constructUsingRow($row)
    {
        $id = Utility::getValueUsingKey($row, 'trackId');

        if(self::$cacheById->containsKey($id))
            return self::$cacheById->get($id);
        else if($row)
        {
            $obj = new LDS_Track();
            $obj->initUsingRow($row);

            return $obj;
        }

        return null;
    }

    public function initUsingRow($row)
    {
        if($row)
        {
            $this->id = Utility::getValueUsingKey($row, 'trackId');
            self::$cacheById->add($this->getId(), $this);

            $this->name = Utility::getValueUsingKey($row, 'name');
            $this->shortName = Utility::getValueUsingKey($row, 'shortName');
            $this->location = Utility::getValueUsingKey($row, 'location');
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
