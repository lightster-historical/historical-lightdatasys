<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/Field.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


class SimpleTextManager
{
    public function getInstanceValueChanges($instanceId, Field $field, $newValues)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $ids = array();

        $oldValues = array();
        $query = new Query('SELECT value.valueId, value.value'
            . ' FROM ' . $db->getTable('InstanceValue') . ' AS instVal'
            . ' INNER JOIN ' . $db->getTable('TypeValue') . ' AS value'
            . ' ON instVal.valueId=value.valueId'
            . ' WHERE instVal.instanceId=' . intval($instanceId)
            . ' AND instVal.fieldId=' . intval($field->getId()));
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $oldValues[$row['value']][] = $row;
        }

        return $this->getValueChanges($field, $oldValues, $newValues);
    }

    public function getItemValueChanges($itemId, Field $field, $newValues)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $ids = array();

        $oldValues = array();
        $query = new Query('SELECT value.valueId, value.value'
            . ' FROM ' . $db->getTable('ItemValue') . ' AS itemVal'
            . ' INNER JOIN ' . $db->getTable('TypeValue') . ' AS value'
            . ' ON itemVal.valueId=value.valueId'
            . ' WHERE itemVal.itemId=' . intval($itemId)
            . ' AND itemVal.fieldId=' . intval($field->getId()));
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $oldValues[$row['value']][] = $row;
        }

        return $this->getValueChanges($field, $oldValues, $newValues);
    }

    protected function getValueChanges(Field $field, $oldValues, $newValues)
    {
        $valuesToInsert = array();
        $valuesToDelete = array();
        $valuesToKeep = array();
        $count = 0;

        foreach($newValues as $newValue)
        {
            if(array_key_exists($newValue, $oldValues))
            {
                $last = count($oldValues[$newValue]) - 1;
                $row = $oldValues[$newValue][$last];
                if(!array_key_exists($row['valueId'], $valuesToDelete))
                    $valuesToKeep[$row['valueId']] = 1;
                else
                    $valuesToKeep[$row['valueId']]++;

                unset($oldValues[$newValue][$last]);
            }
            else
            {
                $valuesToInsert[] = $newValue;
            }

            $count++;
        }

        foreach($oldValues as $oldValue => $rows)
        {
            foreach($rows as $row)
            {
                if(!array_key_exists($row['valueId'], $valuesToDelete))
                    $valuesToDelete[$row['valueId']] = 1;
                else
                    $valuesToDelete[$row['valueId']]++;
            }
        }

        return array('insert' => $valuesToInsert, 'keep' => $valuesToKeep
            , 'delete' => $valuesToDelete, 'count' => $count);
    }

    public function renameValue($valueId, $newValue)
    {
    }

    public function getValueIdsFor(Field $field, $values)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $valueSets = array();
        $ids = array();

        foreach($values as $value)
        {
            $valueSets[] = '(' . intval($field->getTypeId())
                . ', \'' . addslashes($value) . '\')';
        }

        if(count($valueSets) > 0)
        {
            $query = new Query('INSERT INTO ' . $db->getTable('TypeValue')
                . ' (`typeId`, `value`) VALUES ' . implode(',', $valueSets));
            $db->execQuery($query);

            $startId = $db->getAutoIncrementId();
            $ids = range($startId, $startId + count($valueSets) - 1, 1);
        }

        return $ids;
    }

    public function formatValue($value, Field $field = null)
    {
        return $value;
    }

    public function isValid($value, Field $field = null)
    {
        return true;
    }

    public function getCanonicalValue($value, Field $field = null)
    {
        return trim($value);
    }

    public function printFormField(Field $field, $name, $id, $value, $count)
    {
        if(is_array($value) && count($value) >= 2)
            $value = $value[1];

        $value = htmlentities($value);
        ?>
         <input type="text" name="<?php printf($name, $count); ?>" id="<?php printf($id, $count); ?>" value="<?php echo $value; ?>" />
        <?php
    }

    public function printFormFieldTemplate(Field $field, $name, $id, $count)
    {
        $this->printFormField($field, $field->getKeyname() . '-template', $id, '', 'template');
    }

    public function printAddFieldButton(Field $field, $name, $id, $count)
    {
        $maxCount = $field->getMaxCount();

        echo '<input type="submit" onclick="javascript: addField(' . $field->getId() . ',\''
            . $field->getKeyname() . '\',' . $count . ',' . $maxCount
            . '); return false; " name="add-' . $field->getKeyname()
            . '" id="add-' . $field->getKeyname()
            . '" class="item-add-field" value="+" />';
    }

    public function printFormFields(Field $field, $name, $id, $values)
    {
        if(!is_array($values))
            $values = array($values);

        $count = 0;
        $minCount = $field->getMinCount();
        $maxCount = $field->getMaxCount();

        echo '<ul id="field-list-' . $field->getKeyname() . '">';
        foreach($values as $value)
        {
            if($maxCount <= 0 || $count < $maxCount)
            {
                echo '<li id="field-item-' . $field->getKeyname() . '-' . $count . '">';
                $this->printFormField($field, $name, $id, $value, $count);
                echo '</li>';

                $count++;
            }
            else
            {
                break;
            }
        }

        for(; $count < $minCount; $count++)
        {
            echo '<li id="field-item-' . $field->getKeyname() . '-' . $count . '">';
            $this->printFormField($field, $name, $id, '', $count);
            echo '</li>';
        }

        if($maxCount <= 0 || $count < $maxCount)
        {
            echo '<li id="field-template-' . $field->getKeyname() . '" style="display: none; ">';
            $this->printFormFieldTemplate($field, $name, $id, $count);
            echo '</li>';
        }

        echo '</ul>';

        if($maxCount <= 0 || $count < $maxCount)
        {
            $this->printAddFieldButton($field, $name, $id, $count);
        }
    }


    public function printJavascriptInit(Field $field, $name, $id, $value, $count)
    {
    }

    public function printJavascriptInits(Field $field, $name, $id, $values)
    {
        if(!is_array($values))
            $values = array($values);

        $count = 0;
        $minCount = $field->getMinCount();
        $maxCount = $field->getMaxCount();

        foreach($values as $value)
        {
            if($maxCount <= 0 || $count < $maxCount)
            {
                $this->printJavascriptInit($field, $name, $id, $value, $count);

                $count++;
            }
            else
            {
                break;
            }
        }

        for(; $count < $minCount; $count++)
        {
            $this->printJavascriptInit($field, $name, $id, '', $count);
        }
    }
}


?>
