<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/value-manager/SimpleTextManager.php';


class SetManager extends SimpleTextManager
{
    public function getValueIdsFor(Field $field, $values)
    {
        return $values;
    }

    public function isValid($value, Field $field = null)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $value = $this->getCanonicalValue($value, $field);

        if($value >= 0)
        {
            $query = new Query('SELECT valueId FROM ' . $db->getTable('TypeValue')
                . ' AS val'
                . ' INNER JOIN ' . $db->getTable('Type') . ' AS type'
                . ' ON val.typeId=type.typeId'
                . ' INNER JOIN ' . $db->getTable('ValueManager') . ' AS manager'
                . ' ON type.managerId=manager.managerId'
                . ' WHERE manager.keyname=\'set\' AND'
                . ' val.valueId=' . $value);
            $result = $db->execQuery($query);
            if($row = $db->getRow($result))
            {
                if($value == $row[0])
                    return true;
            }
        }

        return false;
    }

    public function getCanonicalValue($value, Field $field = null)
    {
        return intval($value);
    }

    public function printFormField(Field $field, $name, $id, $value, $count)
    {
        if(is_array($value) && count($value) >= 2)
            $value = $value[0];

        $valueSet = $field->getValues(ELTALOG_SORT_USAGE);
        ?>
         <select name="<?php printf($name, $count); ?>" id="<?php printf($id, $count); ?>">
        <?php
        if($count >= $field->getMinCount())
        {
            ?>
             <option value=""></option>
            <?php
        }

        foreach($valueSet as $valueId => $val)
        {
            $selected = '';
            if($value == $valueId)
                $selected = ' selected="selected"';
            ?>
             <option value="<?php echo $valueId; ?>"<?php echo $selected; ?>><?php echo $val; ?></option>
            <?php
        }
        ?>
         </select>
        <?php
    }
}


?>
