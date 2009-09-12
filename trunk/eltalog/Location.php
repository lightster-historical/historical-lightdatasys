<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


class Location
{
    protected static $typeInstancesByTypeId = array();
    protected static $typeInstancesByCategoryId = array();

    // the auto-incremented unique id number as used in the database
    protected $id;

    protected $manager;

    protected $title;

    // has the object changed since being saved to the database?
    protected $changed;


    protected function __constuct()
    {
        $this->id = -1;

        $this->manager = null;

        $this->title = '';

        $this->changed = false;
    }


    public function setManager($manager)
    {
        if($manager instanceof Manager)
            $manager = $manager->getId();

        $manager = intval($manager);
        if(0 < $manager && $manager != $this->manager)
        {
            $this->manager = $manager;
            $this->changed = true;
        }
    }

    public function setTitle($title)
    {
        $this->title = $title;
        $this->changed = true;
    }


    public function getId() {return $this->id;}
    public function getManagerId() {return $this->manager;}
    public function getManager() {return Manager::getByManagerId($this->manager);}
    public function getTitle() {return $this->title;}


    public function save()
    {
    }


    public static function getByTypeId($id)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $id = intval($id);
        if($id > 0)
        {
            if(array_key_exists($id, self::$typeInstancesByTypeId))
            {
                return self::$typeInstancesByTypeId[$id];
            }
            else
            {
                $query = new Query('SELECT * FROM ' . $db->getTable('Type')
                    . ' WHERE typeId=' . $id);
                $result = $db->execQuery($query);
                if($row = $db->getAssoc($result))
                    return self::createFromAssocRow($row);
            }
        }

        return null;
    }

    public static function getByCategoryId($id)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $id = intval($id);
        if($id > 0)
        {
            if(array_key_exists($id, self::$typeInstancesByCategoryId))
            {
                return self::$typeInstancesByCategoryId[$id];
            }
            else
            {
                $types = array();

                $query = new Query('SELECT type.* FROM ' . $db->getTable('Type')
                    . ' AS type INNER JOIN ' . $db->getTable('Field')
                    . ' AS field ON type.typeId=field.typeId WHERE field.categoryId='
                    . $id . ' GROUP BY type.typeId');
                $result = $db->execQuery($query);
                while($row = $db->getAssoc($result))
                {
                    if(array_key_exists($row['typeId'], self::$typeInstancesByTypeId))
                    {
                        $types[] = self::$typeInstancesByTypeId[$row['typeId']];
                    }
                    else
                    {
                        $types[] = self::createFromAssocRow($row);
                    }
                }
                self::$typeInstancesByCategoryId[$id] = $types;
            }

            return $types;
        }

        return null;
    }

    public static function createFromAssocRow($row)
    {
        $type = new Type();

        $type->id = $row['typeId'];
        $type->setManager($row['managerId']);
        $type->setTitle($row['title']);

        self::$typeInstancesByTypeId[$row['typeId']] = $type;

        return $type;
    }
}


?>
