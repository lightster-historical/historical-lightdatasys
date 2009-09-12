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


class IndexResponder extends NascarResponder
{
    public function post($args)
    {
        $this->get($args);
    }


    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys');

        $facebook = new LDS_NascarFacebook();

        ob_start();
        ?>
         <style class="text/css">
          table.stats
          {
              border-collapse: collapse;
          }

          .stats th, .stats td
          {
              padding: 3px;
              text-align: center;
              vertical-align: bottom;
          }

          .stats td.player
          {
              text-align: right;
              width: 100px;
          }

          .stats th.table-title
          {
              color: #666666;
          }

          .stats th.series
          {
              width: 50px;
          }

          .stats th.series-b, .stats .row-a .series-b
          {
              background-color: #cccccc;
          }


          .stats .row-a
          {
              background-color: #eeeeee;
          }

          .stats .row-b
          {
              background-color: #cccccc;
          }

          .stats .row-b .series-b
          {
              background-color: #aaaaaa;
          }


          .stats .right
          {
              text-align: right;
          }

          .stats .center
          {
              text-align: center;
          }
         </style>
        <?php
        $contents = ob_get_contents();
        ob_end_clean();
        /*
        $facebook->api_client->fbml_setRefHandle('index-style', $contents);
        $facebook->api_client->fbml_setRefHandle('index-header', '<fb:ref handle="index-style" />');
        $facebook->api_client->fbml_setRefHandle('box-header', '<fb:ref handle="index-style" />');
        $facebook->api_client->fbml_setRefHandle('mobile-header', '<fb:ref handle="index-style" />');
        $facebook->api_client->fbml_setRefHandle('profile-header', '<fb:ref handle="index-style" />');
        */

        $currRace = $this->getRace();
        $season = $this->getSeason();
        $series = $this->getSeries();

        $users = $season->getFantasyPlayers();
        uasort($users, array($this, 'comparePlayersByFantasyPoints'));

        $fantasyResults = $currRace->getFantasyResults();

        $raceFinish = array();
        $raceLeader = null;
        $driverPoints = $fantasyResults->getDriverPointsUsingRace($currRace);
        $ranker = new MXT_Ranker();
        foreach($users as $userId => $user)
        {
            if(!array_key_exists($userId, $driverPoints))
                $driverPoints[$userId] = 0;

            if(is_null($raceLeader))
                $raceLeader = $user;

            $rank = $ranker->getRank($driverPoints[$userId]);
            $raceFinish[$userId] = $rank;
        }

        $totalDriverPoints = $fantasyResults->getTotalDriverPoints();
        $maxDriverPoints = $fantasyResults->getMaxDriverPoints();
        $minDriverPoints = $fantasyResults->getMinDriverPoints();

        $totalFantasyPoints = $fantasyResults->getTotalFantasyPoints();
        $maxFantasyPoints = $fantasyResults->getMaxFantasyPoints();
        $minFantasyPoints = $fantasyResults->getMinFantasyPoints();
        ?>
         <fb:ref handle="index-header" />
         <fb:if-is-app-user>
          <fb:else>
           <fb:dashboard>
            <fb:action href="<?php echo $facebook->get_add_url(); ?>">Add Application</fb:action>
           </fb:dashboard>
           <fb:explanation>
            <fb:message>Application Not Added</fb:message>
            <a href="<?php echo $facebook->get_add_url(); ?>">Add the application</a> in order to link your Lightdatasys and Facebook accounts.
            Linking your Lightdatasys and Facebook accounts allows you to receive customized statistics in Facebook boxes.
           </fb:explanation>
          </fb:else>
          <fb:dashboard>
           <fb:action href="<?php echo $facebook->getCanvasURL('account.php'); ?>">Options</fb:action>
          </fb:dashboard>
        <?php

        $fbUserId = 0;
        if($this->input->set('fb_sig_user', IntegerInput::getInstance()))
            $fbUserId = $this->input->get('fb_sig_user');

        $userIds = array();
        $fbUserIds = array();
        $query = new Query('SELECT facebookUserId, userId FROM '
            . $db->getTable('FacebookAccountLink'));
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $userIds[$row['facebookUserId']] = $row['userId'];
            $fbUserIds[$row['userId']] = $row['facebookUserId'];
        }

        if(array_key_exists($fbUserId, $userIds))
         {
             ?>
              <div style="float: right; margin: 3px 0 -5px 0; ">
               <fb:add-section-button section="profile" />
              </div>
             <?php
         }
         else
         {
             ?>
              <fb:explanation>
               <fb:message>Account Not Linked</fb:message>
               <a href="<?php echo $facebook->getCanvasURL('account.php'); ?>">Link your Lightdatasys and Facebook accounts</a> to receive customized statistics in Facebook boxes.
              </fb:explanation>
             <?php
         }
         ?>
         </fb:if-is-app-user>
         <fb:tabs>
          <fb:tab-item href="<?php echo $facebook->getCanvasURL('index.php?series=1'); ?>" title='Cup' <?php echo $series->getId() == 1 ? ' selected="true"' : ''; ?>/>
          <fb:tab-item href="<?php echo $facebook->getCanvasURL('index.php?series=2'); ?>" title='National' <?php echo $series->getId() == 2 ? ' selected="true"' : ''; ?>/>
          <fb:tab-item href="<?php echo $facebook->getCanvasURL('index.php?series=3'); ?>" title='Truck' <?php echo $series->getId() == 3 ? ' selected="true"' : ''; ?>/>
         </fb:tabs>
         <table class="stats">
          <thead>
           <tr>
            <th colspan="6" class="table-title">
             <?php echo $currRace->isOfficial() ? 'Official' : 'Unofficial'; ?>
             &#8212;
             <?php echo $currRace->getName(); ?>
             @
             <?php echo $currRace->getTrack()->getShortName(); ?>
            </th>
           </tr>
           <tr>
            <th rowspan="2" colspan="2" class="player">Player</th>
            <th colspan="2">Season</th>
            <th colspan="2">Race</th>
           </tr>
           <tr>
            <th class="series">Rank</th>
            <th class="series">Points</th>
            <th class="series">Finish</th>
            <th class="series">Points</th>
           </tr>
          </thead>
        <?php
        $fantasyLeader = null;
        $prev = null;
        $showedEliminated = false;
        $ranker = new MXT_Ranker();
        $rowStyle = new RolloverIterator(array('row-a', 'row-b'));
        foreach($totalFantasyPoints as $userId => $totalFantasyPoint)
        {
            $user = $users[$userId];

            if(is_null($fantasyLeader))
                $fantasyLeader = $user;

            $rank = $ranker->getRank($totalFantasyPoint);
            $seasonBehind = $totalFantasyPoint - $totalFantasyPoints[$fantasyLeader->getId()];
            $raceBehind = $driverPoints[$userId] - $driverPoints[$raceLeader->getId()];

            ?>
              <tr class="<?php echo $rowStyle; ?>">
               <td class="player">
            <?php
            if(array_key_exists($userId, $fbUserIds))
            {
                echo "<fb:name uid=\"$fbUserIds[$userId]\" useyou=\"false\" />";
            }
            else
            {
                echo substr($user->getName(), 0, strpos($user->getName(), ' '));
            }
            ?>
               </td>
               <?php $user->printInitialsCell(); ?>
               <td><?php echo $rank; ?></td>
               <td><?php echo $seasonBehind == 0 ? $totalFantasyPoint : $seasonBehind; ?></td>
               <td><?php echo $raceFinish[$userId]; ?></td>
               <td><?php echo $raceBehind == 0 ? $driverPoints[$userId] : $raceBehind; ?></td>
              </tr>
            <?php
        }

        ?>
         </table>
        <?php
        $this->printDebugInfo();
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
