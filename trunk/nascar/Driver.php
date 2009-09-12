<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/model/Driver.php';


/*
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';


LDS_Driver::initStaticVariables();


class LDS_Driver
{
    protected static $staticInitialized = false;

    protected static $cacheById;


    protected $id;
    protected $firstName;
    protected $lastName;

    protected $fontColor;
    protected $backgroundColor;
    protected $borderColor;


    protected function __construct()
    {
        $this->id = null;
        $this->firstName = null;
        $this->lastName = null;

        $this->fontColor = null;
        $this->backgroundColor = null;
        $this->borderColor = null;
    }


    public function getId()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getFontColor()
    {
        return $this->fontColor;
    }

    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    public function getBorderColor()
    {
        return $this->borderColor;
    }


    public static function getAll()
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $drivers = array();
        $query = new Query('SELECT driverId, firstName, lastName,'
            . ' color AS fontColor, background AS backgroundColor, border AS borderColor'
            . ' FROM ' . $db->getTable('Driver')
            . ' ORDER BY lastName ASC, firstName ASC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $drivers[$row['driverId']] = LDS_Driver::constructUsingRow($row);
        }

        return $drivers;
    }


    public static function constructUsingRow($row)
    {
        $id = Utility::getValueUsingKey($row, 'driverId');

        if(self::$cacheById->containsKey($id))
            return self::$cacheById->get($id);
        else if($row)
        {
            $obj = new LDS_Driver();
            $obj->initUsingRow($row);

            return $obj;
        }

        return null;
    }

    public function initUsingRow($row)
    {
        if($row)
        {
            $this->id = Utility::getValueUsingKey($row, 'driverId');
            self::$cacheById->add($this->getId(), $this);

            $this->firstName = Utility::getValueUsingKey($row, 'firstName');
            $this->lastName = Utility::getValueUsingKey($row, 'lastName');

            $this->fontColor = Utility::getValueUsingKey($row, 'fontColor');
            $this->backgroundColor = Utility::getValueUsingKey($row, 'backgroundColor');
            $this->borderColor = Utility::getValueUsingKey($row, 'borderColor');
        }
    }


    public static function initStaticVariables()
    {
        if(!self::$staticInitialized)
        {
            self::$cacheById = new MXT_InstanceCache();

            self::$staticInitialized = true;
        }
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
}
*/



?>
