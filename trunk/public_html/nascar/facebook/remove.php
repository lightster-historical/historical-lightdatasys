<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPicks.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/NewsCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/LineupPicksCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/ScheduleCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/SeriesCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/facebook/NascarFacebook.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/facebook/NascarFacebookUpdater.php';

require_once PATH_LIB . 'com/mephex/core/DateRange.php';
require_once PATH_LIB . 'com/mephex/core/Ranker.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/cache/ContentCache.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class RemoveResponder extends NascarResponder
{
    public function post($args)
    {
    }


    public function get($args)
    {
    }
}



?>
