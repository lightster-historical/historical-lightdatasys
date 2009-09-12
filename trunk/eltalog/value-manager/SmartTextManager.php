<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/value-manager/SimpleTextManager.php';


class SmartTextManager extends SimpleTextManager
{
    public function getValueIdsFor(Field $field, $values)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $valueSets = array();
        $ids = array();
        $insertedValues = array();

        foreach($values as $value)
        {
            $valueSets[] = '\'' . addslashes($value) . '\'';
        }

        if(count($valueSets) > 0)
        {
            $tempIds = array();

            $query = new Query('SELECT valueId, value FROM ' . $db->getTable('TypeValue')
                . ' WHERE typeId=' . intval($field->getTypeId()) . ' AND'
                . ' value IN (' . implode(',', $valueSets) . ')');
            $result = $db->execQuery($query);
            while($row = $db->getRow($result))
            {
                $tempIds[$row[1]] = $row[0];
            }

            $valueSets = array();
            foreach($values as $value)
            {
                if(!array_key_exists($value, $tempIds))
                {
                    $insertedValues[$value] = $value;
                    $valueSets[$value] = '(' . intval($field->getTypeId())
                        . ', \'' . addslashes($value) . '\')';
                }
            }

            if(count($valueSets) > 0)
            {
                $query = new Query('INSERT INTO ' . $db->getTable('TypeValue')
                    . ' (`typeId`, `value`) VALUES ' . implode(',', $valueSets));
                $db->execQuery($query);

                $startId = $db->getAutoIncrementId();
                $i = 0;
                foreach($insertedValues as $value)
                {
                    $tempIds[$value] = $startId + $i;
                    $i++;
                }
            }

            foreach($values as $value)
            {
                $ids[] = $tempIds[$value];
            }
        }

        return $ids;
    }

    public function printAddFieldButton(Field $field, $name, $id, $count)
    {
        $maxCount = $field->getMaxCount();

        echo '<input type="submit" onclick="javascript: addSmartTextField(' . $field->getId() . ',\''
            . $field->getKeyname() . '\',' . $count . ',' . $maxCount
            . '); return false; " name="add-' . $field->getKeyname()
            . '" id="add-' . $field->getKeyname()
            . '" class="item-add-field" value="+" />';
    }

    public function printFormField(Field $field, $name, $id, $value, $count)
    {
        if(is_array($value) && count($value) >= 2)
            $value = $value[1];

        //onkeydown="javascript: return navigateSearch(this.parentNode.id, event); " onkeyup="javascript: doSearch(this.parentNode.id, event);"
        $value = htmlentities($value);
        ?>
         <input type="text" autocomplete="off" name="<?php printf($name, $count); ?>" id="<?php printf($id, $count); ?>" value="<?php echo $value; ?>" style="width: 165px; " />
         <div id="value-div-<?php printf($id, $count); ?>" class="value-div" style="display: none; overflow: visible; position: absolute; left: 175px; top: 0; min-width: 150px; border: 1px solid #000000; background: #ffffff;">
         </div>
        <?php
    }

    public function printJavascriptInit(Field $field, $name, $id, $value, $count)
    {
        ?>
         createSearch('<?php echo $field->getKeyname() . '-' . $count; ?>', 'value-div-<?php echo $field->getKeyname() . '-' . $count; ?>', 'xml/smart-text-search.php', 'fieldId=<?php echo $field->getId(); ?>');
        <?php
    }
}


?>
