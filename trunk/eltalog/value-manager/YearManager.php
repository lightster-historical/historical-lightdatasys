<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/value-manager/SmartTextManager.php';


class YearManager extends SmartTextManager
{
    public function isValid($value, Field $field = null)
    {
        $value = $this->getCanonicalValue($value, $field);

        if(1900 <= $value && $value <= 2100)
            return true;

        return false;
    }

    public function getCanonicalValue($value, Field $field = null)
    {
        return intval($value);
    }

    public function printFormField(Field $field, $name, $id, $value, $count)
    {
        if(is_array($value) && count($value) >= 2)
            $value = $value[1];

        $value = htmlentities($value);
        ?>
        <input type="text" name="<?php printf($name, $count); ?>" id="<?php printf($id, $count); ?>" value="<?php echo $value; ?>" style="width: 50px; " />
        <?php
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

    public function printJavascriptInit(Field $field, $name, $id, $value, $count)
    {
    }
}


?>
