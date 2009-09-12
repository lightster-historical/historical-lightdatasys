<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Field.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/input/FormInputsException.php';


class Item
{
    protected static $itemsByItemId = array();
    protected static $itemsByCategoryId = array();


    // the auto-incremented unique id number as used in the database
    protected $id;

    // the category (type of item) the item belongs to
    protected $category;

    // the library the item belongs to
    protected $library;

    // the user who created the item
    protected $owner;

    // the title of the item
    protected $title;
    protected $titleId;

    protected $instances;

    // has the object changed since being saved to the database?
    protected $changed;


    protected function __constuct()
    {
        $this->id = -1;

        $this->category = null;

        $this->library = null;
        $this->owner = -1;

        $this->title = '';
        $this->title = 0;

        $this->instances = null;

        $this->changed = false;
    }


    public function setCategory($category)
    {
        if($category instanceof ItemCategory)
            $category = $category->getId();

        $category = intval($category);
        if(0 < $category && $category != $this->category)
        {
            $this->category = $category;
            $this->changed = true;
        }
    }

    public function setOwner($owner)
    {
        if($owner instanceof User)
            $owner = $owner->getId();

        $owner = intval($owner);
        if(0 < $owner && $owner != $this->type)
        {
            $this->owner = $owner;
            $this->changed = true;
        }
    }

    public function setTitle($title)
    {
        $this->title = $title;
        $this->changed = true;
    }


    public function getId() {return $this->id;}
    public function getCategoryId() {return $this->category;}
    public function getCategory() {return ItemCategory::getByCategoryId($this->category);}
    public function getTitle() {return $this->title;}
    public function getOwner() {return $this->owner;}
    public function getInstances() {return $this->instances;}


    public function save()
    {
    }


    public static function create($categoryId, $title, $allValues = array())
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $fields = Field::getByCategoryId($categoryId);
        $types = Type::getByCategoryId($categoryId);
        $managers = Manager::getByCategoryId($categoryId);

        $errors = array();
        $cleanedValues = array();
        $canonicalValues = array();

        $title = trim($title);
        if($title == '')
        {
            $errors['title'][] = 'A title is required.';
            $cleanedValues['title'] = $title;
            $canonicalValues['title'] = $categoryId;
        }

        $categoryId = intval($categoryId);
        if($categoryId <= 0)
        {
            $errors['category'][] = 'An item type is required.';
            $cleanedValues['category'] = $categoryId;
            $canonicalValues['category'] = $categoryId;
        }

        if(count($errors) > 0)
            throw new FormInputsException($errors, $cleanedValues);
        else
            $canonicalValues = Field::validateValues($categoryId, $allValues, ELTALOG_KIND_ITEM);

        $query = new Query('INSERT INTO ' . $db->getTable('TypeValue')
            . ' (`typeId`, `value`) VALUES (0, \'' . addslashes($title) . '\')');
        $db->execQuery($query);
        $titleId = $db->getAutoIncrementId();

        $query = new Query('INSERT INTO ' . $db->getTable('Item')
            . ' (`categoryId`, `titleId`) VALUES (' . intval($categoryId)
            . ', ' . intval($titleId) . ')');
        $db->execQuery($query);
        $itemId = $db->getAutoIncrementId();

        $valueSets = Field::generateInsertValueSets($categoryId, $itemId
            , $canonicalValues, ELTALOG_KIND_ITEM);

        if(count($valueSets) > 0)
        {
            $query = new Query('INSERT INTO ' . $db->getTable('ItemValue')
                . ' (`itemId`, `fieldId`, `valueId`) VALUES '
                . implode(',', $valueSets));
            $db->execQuery($query);
        }

        return $itemId;
    }


    public function update($title, $allValues)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $title = trim($title);
        if($title == '')
        {
            $errors['title'][] = 'A title is required.';
            $cleanedValues['title'] = $title;
            $canonicalValues['title'] = $categoryId;
        }

        $categoryId = $this->getCategoryId();

        $fields = Field::getByCategoryId($categoryId);
        $types = Type::getByCategoryId($categoryId);
        $managers = Manager::getByCategoryId($categoryId);

        $canonicalValues = Field::validateValues($categoryId, $allValues, ELTALOG_KIND_ITEM);

        $query = new Query('UPDATE ' . $db->getTable('TypeValue')
            . ' SET `value`=\'' . addslashes($title) . '\''
            . ' WHERE `valueId`=' . intval($this->titleId));
        $db->execQuery($query);

        $valueSets = Field::generateUpdateValueSets($categoryId, $this->getId(), $canonicalValues, ELTALOG_KIND_ITEM);

        if(count($valueSets) > 0)
        {
            $query = new Query('INSERT INTO ' . $db->getTable('ItemValue')
                . ' (`itemId`, `fieldId`, `valueId`) VALUES '
                . implode(',', $valueSets));
            $db->execQuery($query);
        }

        return true;
    }



    public static function getByItemId($id)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $id = intval($id);
        if($id > 0)
        {
            if(!array_key_exists($id, self::$itemsByItemId))
            {
                $query = new Query('SELECT item.*, value.valueId AS titleId, value.value AS title FROM '
                    . $db->getTable('Item') . ' AS item'
                    . ' INNER JOIN ' . $db->getTable('TypeValue') . ' AS value'
                    . ' ON item.titleId=value.valueId'
                    . ' WHERE itemId=' . $id);
                $result = $db->execQuery($query);
                if($row = $db->getAssoc($result))
                    self::createFromAssocRow($row);
            }

            return self::$itemsByItemId[$id];
        }

        return null;
    }

    public static function getByItemIds($ids)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        if(count($ids) > 0)
        {
            $notFound = array();
            foreach($ids as $id)
            {
                $id = intval($id);

                if(array_key_exists($id, self::$itemsByItemId))
                    $items[$id] = self::$itemsByItemId[$id];
                else
                    $notFound[] = $id;
            }

            $query = new Query('SELECT item.*, value.valueId AS titleId, value.value AS title FROM '
                . $db->getTable('Item') . ' AS item'
                . ' INNER JOIN ' . $db->getTable('TypeValue') . ' AS value'
                . ' ON item.titleId=value.valueId'
                . ' WHERE itemId IN (' . implode(',', $notFound) . ')');
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                $items[$row['itemId']] = self::createFromAssocRow($row);
            }

            return $items;
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
        $item = new Item();

        $item->id = $row['itemId'];
        $item->setCategory($row['categoryId']);
        $item->title = $row['title'];
        $item->titleId = $row['titleId'];

        self::$itemsByItemId[$row['itemId']] = $item;

        return $item;
    }
}


?>
