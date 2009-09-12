<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/factory/Series.php';

require_once PATH_LIB . 'com/mephex/data-object/DataObject.php';


class LDS_Series extends MXT_DataObject
{
    public function getId()
    {
        return $this->getValue('seriesId');
    }

    public function getPairValue()
    {
        return $this->getSeries()->getName();
    }


    public function getKeyname()
    {
        return $this->getValue('keyname');
    }

    public function getName()
    {
        return $this->getValue('name');
    }

    public function getShortName()
    {
        return $this->getValue('shortName');
    }

    public function getFeedName()
    {
        return $this->getValue('feedName');
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
