<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/value-manager/SimpleTextManager.php';


class UPCManager extends SimpleTextManager
{
    public function formatValue($value, Field $field = null)
    {
        return substr($value, 0, 1) . '-' . substr($value, 1, 5)
            . '-' . substr($value, 6, 5) . '-' . substr($value, 11, 1);
    }

    public function isValid($value, Field $field = null)
    {
        $value = UPCManager::removeTrash($value);

        if(strlen($value) == 12)
        {
            if(preg_match('/[0-9]{12}/', $value) >= 1)
                return true;
        }

        return false;
    }

    public function getCanonicalValue($value, Field $field = null)
    {
        $value = UPCManager::removeTrash($value);

        return $value;
    }

    public function printJavascriptInit(Field $field, $name, $id, $value, $count)
    {
        ?>
         setValidator('<?php echo $field->getKeyname() . '-' . $count; ?>', validateUPCField);
        <?php
    }

    public static function removeTrash($value)
    {
        return preg_replace('/[^0-9]/', '', $value);
    }
}


?>
