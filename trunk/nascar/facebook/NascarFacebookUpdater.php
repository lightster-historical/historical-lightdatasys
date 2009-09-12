<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPicks.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/NewsCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/LineupPicksCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/ScheduleCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/cache/SeriesCacheableContent.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/facebook/NascarFacebook.php';

require_once PATH_LIB . 'com/mephex/core/DateRange.php';
require_once PATH_LIB . 'com/mephex/core/Ranker.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/cache/ContentCache.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class LDS_NascarFacebookUpdater
{
    protected static $updatersBySeriesId = array();


    protected $seriesId;
    protected $race;

    protected $seasonRanks;
    protected $seasonLeader;

    protected $racePositions;
    protected $raceLeader;


    protected function __construct($seriesId)
    {
        $this->seriesId = $seriesId;

        $this->seasonRanks = array();
        $this->seasonLeader = null;

        $this->racePositions = array();
        $this->raceLeader = null;

        $this->initRanks($seriesId);
    }


    protected function initRanks($seriesId)
    {
        $season = LDS_Season::getUsingSeriesId($seriesId);
        $race = LDS_Race::getLastCompletedUsingSeason($season);
        $this->race = $race;

        $users = $season->getFantasyPlayers();
        uasort($users, array($this, 'comparePlayersByFantasyPoints'));

        $ranks = array();
        $seasonLeader = null;
        $positions = array();
        $raceLeader = null;

        $fantasyResults = $race->getFantasyResults();
        $fantasyPoints = $fantasyResults->getTotalFantasyPoints();
        $driverPoints = $fantasyResults->getDriverPointsUsingRace($race);

        $ranker = new MXT_Ranker();
        foreach($fantasyPoints as $userId => $points)
        {
            $user = $users[$userId];

            if(is_null($seasonLeader))
                $seasonLeader = $user;

            $ranks[$userId] = $ranker->getRank($points);
        }

        $ranker = new MXT_Ranker();
        foreach($users as $userId => $user)
        {
            if(!array_key_exists($userId, $driverPoints))
                $driverPoints[$userId] = 0;

            if(is_null($raceLeader))
                $raceLeader = $user;

            $positions[$userId] = $ranker->getRank($driverPoints[$userId]);
        }

        $this->seasonRanks = $ranks;
        $this->seasonLeader = $seasonLeader;
        $this->racePositions = $positions;
        $this->raceLeader = $raceLeader;
    }


    public function getRace()
    {
        return $this->race;
    }


    public function getRankUsingUserId($userId)
    {
        if(array_key_exists($userId, $this->seasonRanks))
            return $this->seasonRanks[$userId];

        return null;
    }

    public function getSeasonLeader()
    {
        return $this->seasonLeader;
    }

    public function getPositionUsingUserId($userId)
    {
        if(array_key_exists($userId, $this->racePositions))
            return $this->racePositions[$userId];

        return null;
    }

    public function getRaceLeader()
    {
        return $this->raceLeader;
    }


    public static function getUpdaterUsingSeries(LDS_Series $series)
    {
        return self::getUpdaterUsingSeriesId($series->getId());
    }

    public static function getUpdaterUsingSeriesId($seriesId)
    {
        if(!array_key_exists($seriesId, self::$updatersBySeriesId))
            self::$updatersBySeriesId[$seriesId] = new LDS_NascarFacebookUpdater($seriesId);

        return self::$updatersBySeriesId[$seriesId];
    }


    public static function updateBoxesUsingUserId(LDS_NascarFacebook $fb, $userId)
    {
        ob_start();
        ?>
         <table class="stats">
          <thead>
           <fb:wide>
            <tr>
             <th rowspan="2">Series</th>
             <th colspan="2">Season</th>
             <th colspan="2">Race</th>
            </tr>
           </fb:wide>
           <tr>
            <fb:narrow>
             <th>Series</th>
            </fb:narrow>
            <th class="series">Rank</th>
            <th class="series">Points</th>
            <fb:wide>
             <th class="series">Finish</th>
             <th class="series">Points</th>
            </fb:wide>
           </tr>
          </thead>
          <tbody>
        <?php
        $allSeries = LDS_Series::getAll();
        foreach($allSeries as $series)
        {
            $updater = self::getUpdaterUsingSeries($series);

            $season = LDS_Season::getUsingSeries($series);
            $race = LDS_Race::getLastCompletedUsingSeason($season);

            $fantasyResults = $race->getFantasyResults();
            $fantasyPoints = $fantasyResults->getTotalFantasyPoints();
            $driverPoints = $fantasyResults->getDriverPointsUsingRace($race);

            $seasonLeader = $updater->getSeasonLeader();
            $seasonPoints = $fantasyPoints[$userId];
            $seasonBehind = $seasonPoints - $fantasyPoints[$seasonLeader->getId()];

            $raceLeader = $updater->getRaceLeader();
            $racePoints = $driverPoints[$userId];
            $raceBehind = $racePoints - $driverPoints[$raceLeader->getId()];

            ?>
             <tr>
              <td><a href="<?php echo $fb->getCanvasURL('index.php?series=' . $series->getId()); ?>"><?php echo $series->getShortName(); ?></a></td>
              <td><?php echo $updater->getRankUsingUserId($userId); ?></td>
              <td><?php echo $seasonBehind == 0 ? $seasonPoints : $seasonBehind; ?></td>
              <fb:wide>
               <td><?php echo $updater->getPositionUsingUserId($userId); ?></td>
               <td><?php echo $raceBehind == 0 ? $racePoints : $raceBehind; ?></td>
              </fb:wide>
             </tr>
            <?php
        }
        ?>
          </tbody>
         </table>
        <?php
        $contents = ob_get_contents();
        ob_end_clean();

        $fbId = $fb->getFacebookIdUsingUserId($userId);
        $fb->api_client->fbml_setRefHandle('box-' . $fbId, $contents);
        $fb->api_client->fbml_setRefHandle('mobile-' . $fbId, $contents);
        $fb->api_client->fbml_setRefHandle('profile-' . $fbId, $contents);
    }

    public static function updateBoxesForAllUsers(LDS_NascarFacebook $fb)
    {
        $userIds = $fb->getAllUserIds();
        foreach($userIds as $userId)
            self::updateBoxesUsingUserId($fb, $userId);
    }


    public function comparePlayersByFantasyPoints(LDS_FantasyPlayer $a, LDS_FantasyPlayer $b)
    {
        $race = $this->getRace();
        $fantasyResults = $race->getFantasyResults();

        $aPoints = $fantasyResults->getFantasyPointsUsingRaceAndPlayer($race, $a);
        $bPoints = $fantasyResults->getFantasyPointsUsingRaceAndPlayer($race, $b);

        if($aPoints > $bPoints)
            return -1;
        else if($aPoints < $bPoints)
            return 1;
        return 0;
    }
}



?>
