<?php


class NFLTeam //extends AbstractFactory
{
    private $location;
    private $mascot;
    private $conference;
    private $division;



    protected function __construct($id, $location, $mascot, $conf, $div)
    {
        parent::__construct($id);

        $this->location = $location;
        $this->mascot = $mascot;
        $this->conference = $conf;
        $this->division = $div;
    }



    public function getLocation()
    {
        return $this->location;
    }

    public function getMascot()
    {
        return $this->mascot;
    }

    public function getConference()
    {
        return $this->conference;
    }

    public function getDivision()
    {
        return $this->division;
    }


    public function __toString()
    {
        return $this->location . ' ' . $this->mascot;
    }



    public static function getInstances($class = null)
    {
        return parent::getInstances(get_class());
    }

    public static function getById($id)
    {
        return parent::getObjectById(get_class(), $id);
    }

    public static function loadAll()
    {
        $db = Database::getConnection('com.lightdatasys.nfl');

        $teams = array();
        $query = new Query('SELECT teamId, location, mascot, conference, division FROM ' . $db->getTable('Team'));
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $teams[$row[0]] = new NFLTeam($row[0], $row[1], $row[2], $row[3], $row[4]);
        }
    }
}


?>
