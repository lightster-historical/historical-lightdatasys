<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/factory/Driver.php';

require_once PATH_LIB . 'com/mephex/data-object/DataObject.php';


class LDS_Driver extends MXT_DataObject
{
    public function getId()
    {
        return $this->getValue('driverId');
    }

    public function getPairValue()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }


    public function getFirstName()
    {
        return $this->getValue('firstName');
    }

    public function getLastName()
    {
        return $this->getValue('lastName');
    }

    public function getFontColor()
    {
        return $this->getValue('color');
    }

    public function getBackgroundColor()
    {
        return $this->getValue('background');
    }

    public function getBorderColor()
    {
        return $this->getValue('border');
    }


    public function printCellSet($accessoryValue = '', $label = false)
    {
        $border = $this->getBorderColor();
        $background = $this->getBackgroundColor();
        $fontColor = $this->getFontColor();

        $secondaryStyle = array();
        $secondaryStyle[] = 'padding: 3px; color: #ffffff;';
        if(!empty($border))
            $secondaryStyle[] = 'background: ' . $border . ';';

        $primaryStyle = array();
        $primaryStyle[] = 'padding: 3px 6px;';
        $primaryStyle[] = 'text-align: right;';
        if(!empty($background))
            $primaryStyle[] = 'background: ' . $background . ';';
        if(!empty($fontColor))
            $primaryStyle[] = 'color: ' . $fontColor . ';';

        if(count($secondaryStyle) > 0)
            $secondaryStyle = ' style="' . implode(' ', $secondaryStyle) . '"';
        if(count($primaryStyle) > 0)
            $primaryStyle = ' style="' . implode(' ', $primaryStyle) . '"';

        ?>
          <td<?php echo $secondaryStyle; ?>><?php echo $accessoryValue; ?></td>
          <td<?php echo $primaryStyle; ?>>
        <?php
        if($label)
            echo '<label for="driver_' . $this->getId() . '">';
        echo $this->getFirstName() . ' ' . $this->getLastName();
        if($label)
            echo '</label>';
        ?>
          </td>
        <?php
    }

    public function printMiniCellSet($value)
    {
        $border = $this->getBorderColor();
        $background = $this->getBackgroundColor();
        $fontColor = $this->getFontColor();

        $secondaryStyle = array();
        $secondaryStyle[] = 'padding: 3px; color: #ffffff;';
        if(!empty($border))
            $secondaryStyle[] = 'background: ' . $border . ';';

        $primaryStyle = array();
        $primaryStyle[] = 'padding: 3px 6px;';
        if(!empty($background))
            $primaryStyle[] = 'background: ' . $background . ';';
        if(!empty($fontColor))
            $primaryStyle[] = 'color: ' . $fontColor . ';';

        if(count($secondaryStyle) > 0)
            $secondaryStyle = ' style="' . implode(' ', $secondaryStyle) . '"';
        if(count($primaryStyle) > 0)
            $primaryStyle = ' style="' . implode(' ', $primaryStyle) . '"';

        ?>
          <td<?php echo $primaryStyle; ?>><?php echo $value; ?></td>
        <?php
    }


    public static function getUsingId($id)
    {
        return self::getUsingClassNameAndObjectId(__CLASS__ . 'Class', $id);
    }

    public static function getAll()
    {
        return self::getAllUsingClassName(__CLASS__ . 'Class');
    }
}



?>
