<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPicks.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/facebook/NascarFacebook.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/facebook/NascarFacebookUpdater.php';

require_once PATH_LIB . 'com/mephex/core/DateRange.php';
require_once PATH_LIB . 'com/mephex/core/Ranker.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/cache/ContentCache.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class AuthorizeResponder extends NascarResponder
{
    public function post($args)
    {
        $this->get($args);
    }


    public function get($args)
    {
        $facebook = new LDS_NascarFacebook();
        $fbUserId = $facebook->require_login();

        $box = '<fb:ref handle="box-header" />'
            . '<fb:ref handle="box-' . $fbUserId . '" />'
            . '<fb:ref handle="box-footer" />';
        $mobile = '<fb:ref handle="mobile-header" />'
            . '<fb:ref handle="mobile-' . $fbUserId . '" />'
            . '<fb:ref handle="mobile-footer" />';
        $profile = '<fb:ref handle="profile-header" />'
            . '<fb:ref handle="profile-' . $fbUserId . '" />'
            . '<fb:ref handle="profile-footer" />';

        $facebook->api_client->profile_setFBML(NULL, $fbUserId, $box, NULL, $mobile, $profile);

        $facebook->api_client->fbml_setRefHandle('box-' . $fbUserId, '');
        $facebook->api_client->fbml_setRefHandle('mobile-' . $fbUserId, '');
        $facebook->api_client->fbml_setRefHandle('profile-' . $fbUserId, '');

        $userId = $facebook->getUserIdUsingFacebookId($fbUserId);
        if(!is_null($userId))
            LDS_NascarFacebookUpdater::updateBoxesUsingUserId($facebook, $userId);
    }
}









?>
