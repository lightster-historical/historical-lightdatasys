<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/Season.php';
require_once PATH_LIB . 'com/mephex/cache/InstanceCache.php';
require_once PATH_LIB . 'com/mephex/core/Utility.php';


LDS_FantasyPlayer::initStaticVariables();


class LDS_FantasyPlayer
{
    protected static $staticInitialized = false;

    protected static $cacheById;
    protected static $cacheBySeasonId;


    protected $id;
    protected $username;
    protected $name;
    protected $backgroundColor;


    protected function __construct()
    {
        $this->id = null;
        $this->username = null;
        $this->name = null;
        $this->backgroundColor = null;
    }


    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }


    public function printCellSet()
    {
        $name = explode(' ', $this->getName());

        ?>
         <td class="right" style="width: 65px; "><?php echo $name[0]; ?></td>
        <?php

        $this->printInitialsCell();
    }

    public function printInitialsCell()
    {
        $name = explode(' ', $this->getName());
        $initials = substr($name[0], 0, 1) . strtolower(substr($name[0], -1, 1));
        if(count($name) > 1)
            $initials .= substr($name[1], 0, 1);

        ?>
         <td class="center" style="color: #ffffff; background-color: #<?php echo $this->getBackgroundColor(); ?>; " title="<?php echo $this->getName(); ?>">
          <?php echo $initials; ?>
         </td>
        <?php
    }


    public static function getAll()
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $users = array();
        $query = new Query('SELECT u.userId, u.username, p.name, p.bgcolor AS backgroundColor FROM user AS u '
            . 'INNER JOIN player_user AS pu ON u.userId=pu.userId '
            . 'INNER JOIN player AS p ON pu.playerId=p.playerId '
            . ' GROUP BY userId ORDER BY name');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $users[$row['userId']] = self::constructUsingRow($row);
        }

        return $users;
    }


    public static function getAllUsingSeason(LDS_Season $season)
    {
        if(self::$cacheBySeasonId->containsKey($season->getId()))
            return self::$cacheBySeasonId->get($season->getId());
        else
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $users = array();
            $query = new Query('SELECT u.userId, u.username, p.name, p.bgcolor AS backgroundColor FROM user AS u '
                . 'INNER JOIN player_user AS pu ON u.userId=pu.userId '
                . 'INNER JOIN player AS p ON pu.playerId=p.playerId '
                . 'INNER JOIN nascarFantPick AS fp ON u.userId=fp.userId AND fp.deletedTime IS NULL '
                . 'INNER JOIN nascarRace AS ra ON fp.raceId=ra.raceId '
                . 'WHERE seasonId=' . $season->getId()
                . ' GROUP BY userId ORDER BY name');
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                $users[$row['userId']] = self::constructUsingRow($row);
            }

            self::$cacheBySeasonId->add($season->getId(), $users);
            return $users;
        }
    }


    public static function constructUsingRow($row)
    {
        $id = Utility::getValueUsingKey($row, 'userId');

        if(self::$cacheById->containsKey($id))
            return self::$cacheById->get($id);
        else if($row)
        {
            $obj = new LDS_FantasyPlayer();
            $obj->initUsingRow($row);

            return $obj;
        }

        return null;
    }

    public function initUsingRow($row)
    {
        if($row)
        {
            $this->id = Utility::getValueUsingKey($row, 'userId');
            self::$cacheById->add($this->getId(), $this);

            $this->username = Utility::getValueUsingKey($row, 'username');
            $this->name = Utility::getValueUsingKey($row, 'name');
            $this->backgroundColor = Utility::getValueUsingKey($row, 'backgroundColor');
        }
    }


    public static function initStaticVariables()
    {
        if(!self::$staticInitialized)
        {
            self::$cacheById = new MXT_InstanceCache();
            self::$cacheBySeasonId = new MXT_InstanceCache();

            self::$staticInitialized = true;
        }
    }
}



?>
