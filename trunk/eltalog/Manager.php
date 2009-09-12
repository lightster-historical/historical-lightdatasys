<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Manager.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';


class Manager
{
    protected static $managerInstancesByManagerId = array();
    protected static $managerInstancesByCategoryId = array();
    protected static $managerInstancesByFieldKeyname = array();

    // the auto-incremented unique id number as used in the database
    protected $id;

    protected $keyname;

    protected $path;
    protected $className;

    protected $title;

    // has the object changed since being saved to the database?
    protected $changed;


    protected function __constuct()
    {
        $this->id = -1;

        $this->keyname = '';

        $this->path = '';
        $this->className = '';

        $this->changed = false;
    }


    public function setKeyname($keyname)
    {
        $this->keyname = $keyname;
        $this->changed = true;
    }

    public function setPath($path)
    {
        $path = Utility::verifyLibraryPath($path);

        if(!($path === false))
        {
            $this->path = $path;
            $this->changed = true;
        }
    }

    public function setClassName($className)
    {
        if(preg_match('/^[A-Za-z0-9_]+$/', $className))
        {
            $this->className = $className;
            //$this->path = 'com/lightdatasys/eltalog/value-manager/'
            //    . $className . '.php';
            $this->changed = true;
        }
    }


    public function getId() {return $this->id;}
    public function getKeyname() {return $this->keyname;}
    public function getPath() {return $this->path;}
    public function getClassName() {return $this->className;}
    public function getInstance()
    {
        require_once $this->getPath();

        $className = $this->getClassName();
        $obj = new $className;

        return $obj;
    }


    public function save()
    {
    }


    public static function getByManagerId($id)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $id = intval($id);
        if($id > 0)
        {
            if(array_key_exists($id, self::$managerInstancesByManagerId))
            {
                return self::$managerInstancesByManagerId[$id];
            }
            else
            {
                $query = new Query('SELECT * FROM ' . $db->getTable('ValueManager')
                    . ' WHERE managerId=' . $id);
                $result = $db->execQuery($query);
                if($row = $db->getAssoc($result))
                    return self::createFromAssocRow($row);
            }
        }

        return null;
    }

    public static function getByManagerKeyname($keyname)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

      /*      if(array_key_exists($id, self::$managerInstancesByManagerId))
            {
                return self::$managerInstancesByManagerId[$id];
            }
            else
            {*/
                $query = new Query('SELECT * FROM ' . $db->getTable('ValueManager')
                    . ' WHERE keyname=\'' . addslashes($keyname) . '\'');
                $result = $db->execQuery($query);
                if($row = $db->getAssoc($result))
                    return self::createFromAssocRow($row);
            //}

        return null;
    }

    public static function getByCategoryId($id)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $id = intval($id);
        if($id > 0)
        {
            if(array_key_exists($id, self::$managerInstancesByCategoryId))
            {
                return self::$managerInstancesByCategoryId[$id];
            }
            else
            {
                $managers = array();

                $query = new Query('SELECT manager.* FROM ' . $db->getTable('ValueManager')
                    . ' AS manager INNER JOIN ' . $db->getTable('Type')
                    . ' AS type ON manager.managerId=type.managerId'
                    . ' INNER JOIN ' . $db->getTable('Field')
                    . ' AS field ON type.typeId=field.typeId'
                    . ' INNER JOIN ' . $db->getTable('Category_Field')
                    . ' AS cat_field ON field.fieldId=cat_field.fieldId'
                    . ' WHERE cat_field.categoryId=' . $id
                    . ' GROUP BY manager.managerId');
                $result = $db->execQuery($query);
                while($row = $db->getAssoc($result))
                {
                    if(array_key_exists($row['managerId'], self::$managerInstancesByCategoryId))
                    {
                        $managers[$row['managerId']] = self::$managerInstancesByCategoryId[$row['managerId']];
                    }
                    else
                    {
                        $managers[$row['managerId']] = self::createFromAssocRow($row);
                    }
                }
                self::$managerInstancesByCategoryId[$id] = $managers;
            }

            return $managers;
        }

        return null;
    }

    public static function getByFieldKeyname($keyname)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        if(array_key_exists($keyname, self::$managerInstancesByFieldKeyname))
        {
            return self::$managerInstancesByFieldKeyname[$keyname];
        }
        else
        {
            $managers = array();

            $query = new Query('SELECT manager.* FROM ' . $db->getTable('ValueManager')
                . ' AS manager INNER JOIN ' . $db->getTable('Type')
                . ' AS type ON manager.managerId=type.managerId'
                . ' INNER JOIN ' . $db->getTable('Field')
                . ' AS field ON type.typeId=field.typeId'
                . ' WHERE field.keyname=\'' . addslashes($keyname) . '\''
                . ' GROUP BY manager.managerId');
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                if(array_key_exists($row['managerId'], self::$managerInstancesByManagerId))
                {
                    $managers[$row['managerId']] = self::$managerInstancesByManagerId[$row['managerId']];
                }
                else
                {
                    $managers[$row['managerId']] = self::createFromAssocRow($row);
                }
            }
            self::$managerInstancesByFieldKeyname[$keyname] = $managers;

            return $managers;
        }
    }

    public static function createFromAssocRow($row)
    {
        $manager = new Manager();

        $manager->id = $row['managerId'];
        $manager->setKeyname($row['keyname']);
        $manager->setClassName($row['className']);
        $manager->setPath($row['path']);

        self::$managerInstancesByManagerId[$row['managerId']] = $manager;

        return $manager;
    }
}


?>
