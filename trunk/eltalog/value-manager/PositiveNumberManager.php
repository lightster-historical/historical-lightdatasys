<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/value-manager/SimpleTextManager.php';


class PositiveNumberManager extends SimpleTextManager
{
    public function isValid($value, Field $field = null)
    {
        $value = $this->getCanonicalValue($value, $field);

        if(1 <= $value)
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
}


?>
