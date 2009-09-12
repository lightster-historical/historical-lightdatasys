<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/value-manager/SimpleTextManager.php';


class ISBNManager extends SimpleTextManager
{
    public function formatValue($value, Field $field = null)
    {
        return substr($value, 0, 3) . '-' . substr($value, 3, 1)
            . '-' . substr($value, 4, 6) . '-' . substr($value, 10, 2)
            . '-' . substr($value, 12, 1);
    }

    public function isValid($value, Field $field = null)
    {
        $value = ISBNManager::removeTrash($value);

        if(strlen($value) == 10)
        {
            if(preg_match('/[0-9]{9}[0-9xX]/', $value) >= 1)
                return true;
        }
        else if(strlen($value) == 13)
        {
            if(preg_match('/[0-9]{13}/', $value) >= 1)
                return true;
        }

        return false;
    }

    public function getCanonicalValue($value, Field $field = null)
    {
        $value = ISBNManager::removeTrash($value);

        if(strlen($value) == 10)
        {
            $value = '978' . substr($value, 0, 9);

            $result = 0;

            for($i = 0; $i < 12; $i++)
            {
                $x = substr($value, $i, 1);
                if(!is_numeric($x))
                    return false;
                $x = intval($x);
                if(!(0 <= $x && $x <= 9))
                    return false;

                if(($i + 1) % 2 == 1)
                    $result += $x * 1;
                else
                    $result += $x * 3;
            }
            $result = (10 - ($result % 10)) % 10;

            $value .= $result;
        }

        return $value;
    }

    public function printFormField(Field $field, $name, $id, $value, $count)
    {
        if(is_array($value) && count($value) >= 2)
            $value = $value[1];

        $value = htmlentities($value);
        ?>
         <input type="text" autocomplete="off" name="<?php printf($name, $count); ?>" id="<?php printf($id, $count); ?>" value="<?php echo $value; ?>" />
        <?php
    }

    public function printJavascriptInit(Field $field, $name, $id, $value, $count)
    {
        ?>
         setValidator('<?php echo $field->getKeyname() . '-' . $count; ?>', validateISBNField);
        <?php
    }

    public static function removeTrash($value)
    {
        return preg_replace('/[^0-9xX]/', '', $value);
    }
}


?>
