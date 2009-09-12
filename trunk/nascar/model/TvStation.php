<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/factory/TvStation.php';

require_once PATH_LIB . 'com/mephex/data-object/DataObject.php';


class LDS_TvStation extends MXT_DataObject
{
    public function getId()
    {
        return $this->getValue('stationId');
    }

    public function getPairValue()
    {
        return $this->getName();
    }


    public function getName()
    {
        return $this->getValue('name');
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
