<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/factory/Result.php';

require_once PATH_LIB . 'com/mephex/data-object/DataObject.php';


class LDS_Result extends MXT_DataObject
{
    public function getCar()
    {
        return $this->getValue('car');
    }

    public function getDriver()
    {
        return $this->getValue('driver');
    }

    public function getStart()
    {
        return $this->getValue('start');
    }

    public function getFinish()
    {
        return $this->getValue('finish');
    }

    public function getLedLaps()
    {
        return $this->getValue('ledLaps');
    }

    public function getLedMostLaps()
    {
        return $this->getValue('ledMostLaps');
    }

    public function getPoints()
    {
        return $this->getValue('points');
    }

    public function getPenalties()
    {
        return $this->getValue('penalties');
    }


    public static function getUsingId($id)
    {
        return self::getUsingClassNameAndObjectId(__CLASS__ . 'Class', $id);
    }

    public static function constructUsingRow(array $row)
    {
        $class = LDS_ResultClass::getSingleton();
        return $class->constructUsingRow($row);
    }
}



?>
