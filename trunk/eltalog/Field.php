<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/ItemCategory.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Type.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


/*
Some examples:
- Field 'Cast & Crew' of the 'Movie' Category allows from zero to an
  infinite number of type 'Person' to be assigned to an individual movie.
  Since two copies of the same movie (i.e. two physical discs) will have the
  same cast & crew, the field is associated with the ITEM.
- Field 'Format' of the 'Movie' Category allows zero or one number of type
  'Video Format' to be assigned to an individual movie. Since two copies of the
  same movie may be in different formats (e.g. one on DVD and one on Blu-ray),
  the field is associated with the INSTANCE of the object.
*/
class Field
{
    protected static $fieldInstancesByFieldId = array();
    protected static $fieldInstancesByCategoryId = array();

    // the auto-incremented unique id number as used in the database
    protected $id;

    // the category (type of item) the field belongs to
    protected $category;

    // the type of value this field holds (Person, Video Format, etc.)
    // - used to assist smart-text/data entry assistance
    protected $type;
    // should the value of the field be applied to items or instances of the items?
    // - SEE ALSO: EltalogConstants.Field.kind
    protected $kind;

    // the minimum and maximum number of values that can be assigned to an
    // item's instance of this field
    protected $minCount;
    protected $maxCount;

    // the keyname is used as the key in a hashmap to identify the field
    protected $keyname;
    // the display title and description of the field
    protected $title;
    protected $description;

    // the order the field should appear in
    protected $orderIndex;
    // bitwise system flags that allow for options such as display preferences
    // to be set
    // - SEE ALSO: EltalogConstants.Field.systemFlags
    protected $systemFlags;
    // bitwise flags customized to the specific field
    protected $fieldFlags;

    // has the object changed since being saved to the database?
    protected $changed;


    protected function __constuct()
    {
        $this->id = -1;

        $this->category = null;

        $this->type = null;
        $this->kind = -1;

        $this->minCount = -1;
        $this->maxCount = -1;

        $this->keyname = '';
        $this->title = '';
        $this->description = '';

        $this->orderIndex = -1;
        $this->systemFlags = 0;
        $this->fieldFlags = 0;

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

    public function setType($type)
    {
        if($type instanceof Type)
            $type = $type->getId();

        $type = intval($type);
        if(0 < $type && $type != $this->type)
        {
            $this->type = $type;
            $this->changed = true;
        }
    }

    public function setKind($kind)
    {
        $kind = strtolower($kind);
        if($kind == 'item')
            $kind = ELTALOG_KIND_ITEM;
        else if($kind == 'instance')
            $kind = ELTALOG_KIND_INSTANCE;

        $kind = intval($kind);

        if(ELTALOG_KIND_ITEM <= $kind || $kind <= ELTALOG_KIND_INSTANCE)
        {
            $this->kind = $kind;
            $this->changed = true;
        }
    }

    public function setMinCount($minCount)
    {
        $this->minCount = max(0, intval($minCount));
        $this->changed = true;
    }

    public function setMaxCount($maxCount)
    {
        $this->maxCount = max(0, intval($maxCount));
        $this->changed = true;
    }

    public function setKeyname($keyname)
    {
        $this->keyname = $keyname;
        $this->changed = true;
    }

    public function setTitle($title)
    {
        $this->title = $title;
        $this->changed = true;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        $this->changed = true;
    }

    public function setSystemFlags($systemFlags)
    {
        $this->systemFlags = max(0, intval($systemFlags));
        $this->changed = true;
    }

    public function addSystemFlag($systemFlag)
    {
        $this->systemFlags = $this->systemFlags | $systemFlag;
    }

    public function removeSystemFlag($systemFlag)
    {
        if($this->systemFlags & $systemFlag > 0)
        {
            $this->systemFlags -= $systemFlag;
        }
    }

    public function setFieldFlags($fieldFlags)
    {
        $this->fieldFlags = max(0, intval($fieldFlags));
        $this->changed = true;
    }


    public function getId() {return $this->id;}
    public function getCategoryId() {return $this->category;}
    public function getCategory() {return ItemCategory::getByCategoryId($this->category);}
    public function getTypeId() {return $this->type;}
    public function getType() {return Type::getByTypeId($this->type);}
    public function getManager()
    {
        $type = $this->getType();
        if(!is_null($type))
            return $type->getManager();
        return null;
    }
    public function getKind() {return $this->kind;}
    public function getMinCount() {return $this->minCount;}
    public function getMaxCount() {return $this->maxCount;}
    public function getKeyname() {return $this->keyname;}
    public function getTitle() {return $this->title;}
    public function getDescription() {return $this->description;}
    public function getOrderIndex() {return $this->orderIndex;}
    public function getSystemFlags() {return $this->systemFlags;}
    public function getFieldFlags() {return $this->fieldFlags;}


    public function getValues($sort = 0, $maxCount = 0, $start = 0, $where = '')
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $maxCount = intval($maxCount);
        $start = intval($start);

        if($start > 0)
        {
            $maxCount = max($maxCount, 1);
            $limit = ' LIMIT ' . $start . ',' . $maxCount;
        }
        else if($maxCount > 0)
        {
            $limit = ' LIMIT ' . $maxCount;
        }
        else
        {
            $limit = '';
        }

        $join = '';

        if(($sort & ELTALOG_SORT_USAGE) > 0)
        {
            $join .= ' LEFT JOIN ' . $db->getTable('InstanceValue')
                . ' AS instVal ON val.valueId=instVal.valueId'
                . ' LEFT JOIN ' . $db->getTable('ItemValue')
                . ' AS itemVal ON val.valueId=itemVal.valueId';
            $orderBy = ' ORDER BY COUNT(instVal.instanceId)+COUNT(itemVal.itemId) ';

            if(($sort & ELTALOG_SORT_ASC) > 0)
            {
                $orderBy .= 'ASC';
            }
            else
            {
                $orderBy .= 'DESC';
            }
        }
        else if(($sort & ELTALOG_SORT_DESC) > 0)
        {
            $orderBy = ' ORDER BY val.value DESC';
        }
        else
        {
            $orderBy = ' ORDER BY val.value ASC';
        }

        $values = array();
        $query = new Query('SELECT val.valueId, val.value FROM '
            . $db->getTable('TypeValue') . ' AS val' . $join
            . ' WHERE typeId=' . $this->getTypeId() . $where
            . ' GROUP BY val.valueId' . $orderBy . $limit);
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $values[$row[0]] = $row[1];
        }

        return $values;
    }


    public function printJavascriptInits($values = array(''), $name = null, $id = null)
    {
        $typeObj = $this->getType();

        if(!is_object($typeObj))
            throw new Exception('The field\'s type cannot be determined.');
        else
        {
            $manager = $typeObj->getManager();
            $obj = $manager->getInstance();

            if(is_null($name))
                $name = $this->getKeyname() . '[]';
            if(is_null($id))
                $id = $this->getKeyname() . '-%s';

            $obj->printJavascriptInits($this, $name, $id, $values);
        }
    }

    public function printFormFields($values = array(''), $name = null, $id = null)
    {
        $typeObj = $this->getType();

        if(!is_object($typeObj))
            throw new Exception('The field\'s type cannot be determined.');
        else
        {
            $manager = $typeObj->getManager();
            $obj = $manager->getInstance();

            if(is_null($name))
                $name = $this->getKeyname() . '[]';
            if(is_null($id))
                $id = $this->getKeyname() . '-%s';

            $obj->printFormFields($this, $name, $id, $values);
        }
    }


    public function save()
    {
    }


    public static function create($category, $type, $kind, $minCount, $maxCount
        , $keyname, $title, $description, $orderIndex, $systemFlags, $fieldFlags)
    {
    }


    public static function getByFieldId($fieldId, $categoryId = 0)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $categoryId = intval($categoryId);
        $fieldId = intval($fieldId);
        if($fieldId > 0)
        {
            if(array_key_exists($categoryId, self::$fieldInstancesByFieldId)
                && array_key_exists($fieldId, self::$fieldInstancesByFieldId[$categoryId]))
            {
                return self::$fieldInstancesByFieldId[$row['categoryId']][$row['fieldId']];
            }
            else
            {
                $query = new Query('SELECT *, field.fieldId AS fieldId FROM ' . $db->getTable('Field')
                    . ' AS field LEFT JOIN ' . $db->getTable('Category_Field')
                    . ' AS cat_field ON field.fieldId=cat_field.fieldId'
                    . ' AND cat_field.categoryId=' . $categoryId
                    . ' WHERE field.fieldId=' . $fieldId);
                $result = $db->execQuery($query);
                if($row = $db->getAssoc($result))
                {
                    return self::createFromAssocRow($row);
                }
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
            if(array_key_exists($id, self::$fieldInstancesByCategoryId))
            {
                return self::$fieldInstancesByCategoryId[$id];
            }
            else
            {
                $fields = array();

                $query = new Query('SELECT * FROM ' . $db->getTable('Category_Field')
                    . ' AS cat_field INNER JOIN ' . $db->getTable('Field')
                    . ' AS field ON cat_field.fieldId=field.fieldId'
                    . ' WHERE cat_field.categoryId=' . $id
                    . ' ORDER BY cat_field.orderIndex');
                $result = $db->execQuery($query);
                while($row = $db->getAssoc($result))
                {
                    if(array_key_exists($row['categoryId'], self::$fieldInstancesByFieldId)
                        && array_key_exists($row['fieldId'], self::$fieldInstancesByFieldId[$row['categoryId']]))
                    {
                        $fields[$row['fieldId']] = self::$fieldInstancesByFieldId[$row['categoryId']][$row['fieldId']];
                    }
                    else
                    {
                        $fields[$row['fieldId']] = self::createFromAssocRow($row);
                    }
                }
                self::$fieldInstancesByCategoryId[$id] = $fields;
            }

            return $fields;
        }

        return null;
    }

    public static function createFromAssocRow($row)
    {
        $field = new Field();

        $field->id = $row['fieldId'];
        $field->setCategory($row['categoryId']);
        $field->setType($row['typeId']);
        $field->setKind($row['kind']);
        $field->setMinCount($row['minCount']);
        $field->setMaxCount($row['maxCount']);
        $field->setKeyname($row['keyname']);
        $field->setTitle($row['title']);
        $field->setDescription($row['description']);
        $field->setSystemFlags($row['systemFlags']);
        $field->setFieldFlags($row['fieldFlags']);

        self::$fieldInstancesByFieldId[$row['fieldId']] = $field;

        return $field;
    }



    public static function validateValues($categoryId, $allValues
        , $kind)// = ELTALOG_KIND_ITEM | ELTALOG_KIND_INSTANCE)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $fields = Field::getByCategoryId($categoryId);
        $types = Type::getByCategoryId($categoryId);
        $managers = Manager::getByCategoryId($categoryId);

        $errors = array();
        $cleanedValues = array();
        $canonicalValues = array();

        foreach($fields as $field)
        {
            if(($field->getKind() & $kind) > 0)
            {
                $keyname = $field->getKeyname();

                $values = array();
                if(array_key_exists($keyname, $allValues))
                    $values = $allValues[$keyname];

                $cleanedValues[$keyname] = array();
                $canonicalValues[$keyname] = array();

                $manager = $field->getManager()->getInstance();
                if(is_null($manager))
                {
                    $errors[$keyname]['manager'] = 'The manager for this field could not be loaded, so the data cannot be saved.';
                }
                else
                {
                    $minCount = $field->getMinCount();
                    $maxCount = $field->getMaxCount();
                    $validCount = 0;
                    $invalidCount = 0;

                    foreach($values as $value)
                    {
                        $value = trim($value);

                        if($manager->isValid($value, $field))
                        {
                            $canonicalValue = $manager->getCanonicalValue($value, $field);

                            if($canonicalValue != '')
                            {
                                $validCount++;
                                $cleanedValues[$keyname][] = $value;
                                $canonicalValues[$keyname][] = $canonicalValue;
                            }
                        }
                        else if($value != '')
                        {
                            $cleanedValues[$keyname][] = $value;

                            $invalidCount++;

                            if(!array_key_exists($keyname, $errors))
                                $errors[$keyname] = array();

                            if(array_key_exists('invalid', $errors[$keyname]))
                                $errors[$keyname]['invalid'] .= ', ' . htmlentities($value);
                            else
                                $errors[$keyname]['invalid'] = 'The following value(s) are invalid: ' . htmlentities($value);
                        }
                    }

                    $totalCount = $validCount + $invalidCount;
                    if($totalCount < $minCount)
                    {
                        $errors[$keyname]['minCount'] = 'Too few ' . $field->getTitle() . ' values were provided. At least ' . $minCount . ' values are required.';
                    }
                    else if($maxCount > 0 && $totalCount > $maxCount)
                    {
                        $errors[$keyname]['maxCount'] = 'Too many ' . $field->getTitle() . ' values were provided. Up to ' . $maxCount . ' values are allowed.';
                    }
                }
            }
        }

        if(count($errors) > 0)
            throw new FormInputsException($errors, $cleanedValues);

        return $canonicalValues;
    }

    public static function generateInsertValueSets($categoryId, $iId, $canonicalValues, $kind)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $fields = Field::getByCategoryId($categoryId);
        $types = Type::getByCategoryId($categoryId);
        $managers = Manager::getByCategoryId($categoryId);

        $valueSets = array();
        foreach($fields as $field)
        {
            if(($field->getKind() & $kind) > 0)
            {
                $valueIdsUsed = array();

                $keyname = $field->getKeyname();
                $manager = $field->getManager()->getInstance();

                $values = $canonicalValues[$keyname];

                if(count($values) > 0)
                {
                    $ids = $manager->getValueIdsFor($field, $values);
                    foreach($ids as $id)
                    {
                        if(!array_key_exists($id, $valueIdsUsed))
                        {
                            $valueSets[] = '(' . intval($iId) . ','
                                . intval($field->getId()) . ','
                                . intval($id) . ')';
                            $valueIdsUsed[$id] = true;
                        }
                    }
                }
            }
        }

        return $valueSets;
    }

    public static function generateUpdateValueSets($categoryId, $iId, $canonicalValues, $kind)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $fields = Field::getByCategoryId($categoryId);
        $types = Type::getByCategoryId($categoryId);
        $managers = Manager::getByCategoryId($categoryId);

        $valueSets = array();
        foreach($fields as $field)
        {
            if($field->getKind() == $kind)
            {
                $keyname = $field->getKeyname();
                $manager = $field->getManager()->getInstance();

                $values = $canonicalValues[$keyname];

                if(count($values) > 0)
                {
                    if($field->getKind() == ELTALOG_KIND_INSTANCE)
                        $changes = $manager->getInstanceValueChanges($iId, $field, $values);
                    else
                        $changes = $manager->getItemValueChanges($iId, $field, $values);
                    $valuesToInsert = $changes['insert'];
                    $valueIdsUsed = $changes['keep'];
                    $valuesToDelete = $changes['delete'];
                    $count = $changes['count'];

                    if(count($valuesToInsert) > 0)
                    {
                        $ids = $manager->getValueIdsFor($field, $valuesToInsert);
                        foreach($ids as $id)
                        {
                            if(!array_key_exists($id, $valueIdsUsed))
                            {
                                $valueSets[] = '(' . intval($iId) . ','
                                    . intval($field->getId()) . ','
                                    . intval($id) . ')';
                                $valueIdsUsed[$id] = true;
                            }
                        }
                    }

                    if(count($valuesToDelete) > 0)
                    {
                        foreach($valuesToDelete as $id => $count)
                        {
                            if($field->getKind() == ELTALOG_KIND_INSTANCE)
                            {
                                $query = new Query('DELETE FROM ' . $db->getTable('InstanceValue')
                                    . ' WHERE instanceId=' . intval($iId)
                                    . ' AND fieldId=' . intval($field->getId())
                                    . ' AND valueId=' . intval($id)
                                    . ' LIMIT ' . intval($count));
                            }
                            else
                            {
                                $query = new Query('DELETE FROM ' . $db->getTable('ItemValue')
                                    . ' WHERE itemId=' . intval($iId)
                                    . ' AND fieldId=' . intval($field->getId())
                                    . ' AND valueId=' . intval($id)
                                    . ' LIMIT ' . intval($count));
                            }

                            $db->execQuery($query);
                        }
                    }
                }
            }
        }

        return $valueSets;
    }
}


?>
