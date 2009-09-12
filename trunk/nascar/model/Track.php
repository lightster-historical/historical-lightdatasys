<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/factory/Track.php';

require_once PATH_LIB . 'com/mephex/data-object/DataObject.php';


class LDS_Track extends MXT_DataObject
{
    public function getId()
    {
        return $this->getValue('trackId');
    }

    public function getPairValue()
    {
        return $this->getShortName();
    }


    public function getName()
    {
        return $this->getValue('name');
    }

    public function getShortName()
    {
        return $this->getValue('shortName');
    }

    public function getLocation()
    {
        return $this->getValue('location');
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
