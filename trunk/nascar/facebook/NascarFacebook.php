<?php


require_once PATH_LIB . 'com/facebook/api/facebook.php';


class LDS_NascarFacebook extends Facebook
{
    protected $userIdsByFbId;
    protected $fbIdsByUserId;


    public function __construct()
    {
        $apiKey = '507b5119e5d9498b7c2cfe3d9f9d15ad';
        $secret = '3f6ada34d30fdf39877b25edeb9b5729';
        parent::__construct($apiKey, $secret);

        $this->userIdsByFbId = null;
        $this->fbIdsByUserId = null;
    }


    public function getCanvasURL($page = '')
    {
        return 'http://apps.facebook.com/lightdatasys-racing/' . $page;
    }


    public function getUserIdUsingFacebookId($fbId)
    {
        $this->initFacebookUserIds();
        if(array_key_exists($fbId, $this->userIdsByFbId))
            return $this->userIdsByFbId[$fbId];

        return null;
    }

    public function getAllUserIds()
    {
        $this->initFacebookUserIds();
        return $this->userIdsByFbId;
    }

    public function getFacebookIdUsingUserId($userId)
    {
        $this->initFacebookUserIds();
        if(array_key_exists($userId, $this->fbIdsByUserId))
            return $this->fbIdsByUserId[$userId];

        return null;
    }

    public function getAllFacebookIds()
    {
        $this->initFacebookUserIds();
        return $this->fbIdsByUserId;
    }

    protected function initFacebookUserIds()
    {
        if(is_null($this->userIdsByFbId))
        {
            $this->userIdsByFbId = array();
            $this->fbIdsByUserId = array();

            $db = Database::getConnection('com.lightdatasys');

            $query = new Query('SELECT facebookUserId, userId FROM '
                . $db->getTable('FacebookAccountLink'));
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                $this->userIdsByFbId[$row['facebookUserId']] = $row['userId'];
                $this->fbIdsByUserId[$row['userId']] = $row['facebookUserId'];
            }
        }
    }
}



?>
