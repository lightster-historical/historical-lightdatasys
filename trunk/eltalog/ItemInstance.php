<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


class ItemInstance
{
    protected static $instancesByInstanceId = array();
    protected static $instancesByItemId = array();


    // the auto-incremented unique id number as used in the database
    protected $id;

    protected $item;
    protected $location;


    protected function __constuct()
    {
        $this->id = -1;

        $this->item = -1;
        $this->location = -1;
    }


    public function getId() {return $this->id;}
    public function getItemId() {return $this->item;}
    public function getItem() {return Item::getByItemId($this->item);}
    public function getLocationId() {return $this->location;}



    public static function create($categoryId, $itemId, $locationId, $allValues)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $errors = array();
        $canonicalValues = array();

        $itemId = intval($itemId);
        if($itemId <= 0)
        {
            $errors['item'][] = 'The instance must belong to an item.';
            $cleanedValues['item'] = $itemId;
            $canonicalValues['item'] = $itemId;
        }

        $locationId = intval($locationId);

        $fields = Field::getByCategoryId($categoryId);
        $types = Type::getByCategoryId($categoryId);
        $managers = Manager::getByCategoryId($categoryId);

        if(count($errors) > 0)
            throw new FormInputsException($errors, $cleanedValues);
        else
            $canonicalValues = Field::validateValues($categoryId, $allValues, ELTALOG_KIND_INSTANCE);

        $query = new Query('INSERT INTO ' . $db->getTable('Instance')
            . ' (`itemId`, `locationId`) VALUES (' . intval($itemId)
            . ', ' . intval($locationId) . ')');
        $db->execQuery($query);
        $instanceId = $db->getAutoIncrementId();

        $valueSets = Field::generateInsertValueSets($categoryId, $instanceId
            , $canonicalValues, ELTALOG_KIND_INSTANCE);

        if(count($valueSets) > 0)
        {
            $query = new Query('INSERT INTO ' . $db->getTable('InstanceValue')
                . ' (`instanceId`, `fieldId`, `valueId`) VALUES '
                . implode(',', $valueSets));
            $db->execQuery($query);
        }

        return $instanceId;
    }


    public function update($locationId, $allValues)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $item = $this->getItem();
        $categoryId = $item->getCategoryId();

        $locationId = intval($locationId);

        $canonicalValues = Field::validateValues($categoryId, $allValues, ELTALOG_KIND_INSTANCE);

        $query = new Query('UPDATE ' . $db->getTable('Instance')
            . ' SET `locationId`=' . intval($locationId)
            . ' WHERE `instanceId`=' . intval($this->getId()));
        $db->execQuery($query);

        $valueSets = Field::generateUpdateValueSets($categoryId, $this->getId(), $canonicalValues, ELTALOG_KIND_INSTANCE);

        if(count($valueSets) > 0)
        {
            $query = new Query('INSERT INTO ' . $db->getTable('InstanceValue')
                . ' (`instanceId`, `fieldId`, `valueId`) VALUES '
                . implode(',', $valueSets));
            $db->execQuery($query);
        }

        return true;
    }




    public static function getByInstanceId($id)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $id = intval($id);
        if($id > 0)
        {
            if(!array_key_exists($id, self::$instancesByInstanceId))
            {
                $query = new Query('SELECT * FROM ' . $db->getTable('Instance')
                    . ' WHERE instanceId=' . $id);
                $result = $db->execQuery($query);
                if($row = $db->getAssoc($result))
                    self::createFromAssocRow($row);
            }

            return self::$instancesByInstanceId[$id];
        }

        return null;
    }

    /*
    public static function getByCategoryId($id)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $id = intval($id);
        if($id > 0)
        {
            if(!array_key_exists($id, self::$itemsByCategoryId))
            {
                $items = array();

                $query = new Query('SELECT * FROM ' . $db->getTable('Field')
                    . ' AS field WHERE categoryId=' . $id . ' ORDER BY orderIndex');
                $result = $db->execQuery($query);
                while($row = $db->getAssoc($result))
                {
                    if(!array_key_exists($row['itemId'], self::$itemsByItemId))
                    {
                        $items[] = self::createFromAssocRow($row);
                    }
                    else
                    {
                        $items[] = self::$itemsByItemId[$row['itemId']];
                    }
                }

                self::$itemsByCategoryId[$id] = &$items;
            }

            return self::$itemsByCategoryId[$id];
        }

        return null;
    }
    */

    public static function createFromAssocRow($row)
    {
        $inst = new ItemInstance();

        $inst->id = $row['instanceId'];
        $inst->item = $row['itemId'];
        $inst->location = $row['locationId'];

        self::$instancesByInstanceId[$row['instanceId']] = $inst;

        return $inst;
    }
}


?>
