<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPicks.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/Race.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/facebook/NascarFacebook.php';

require_once PATH_LIB . 'com/mephex/core/DateRange.php';
require_once PATH_LIB . 'com/mephex/core/Ranker.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/cache/ContentCache.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class AccountResponder extends NascarResponder
{
    public function post($args)
    {
        $this->get($args);
    }


    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys');

        $facebook = new LDS_NascarFacebook();
        $fbUserId = $facebook->require_login();

        ?>
          <fb:dashboard>
           <fb:action href="<?php echo $facebook->getCanvasURL('index.php'); ?>">Back to <fb:application-name linked="false" /></fb:action>
          </fb:dashboard>
        <?php

        $query = new Query('SELECT u.userId, u.username FROM '
            . $db->getTable('FacebookAccountLink') . ' AS accountLink'
            . ' INNER JOIN '. $db->getTable('user') . ' AS u'
            . ' ON accountLink.userId=u.userId'
            . ' WHERE facebookUserId=' . $fbUserId);
        $result = $db->execQuery($query);
        if($user = $db->getAssoc($result))
        {
            ?>
             <fb:explanation>
              <fb:message>Account Linked</fb:message>
              <p>
               Your Facebook account is currently linked with the <em><?php echo $user['username']; ?></em> Lightdatasys account.
              </p>
             </fb:explanation>
             <fb:explanation>
              <fb:message>Box Options</fb:message>
              <fb:if-section-not-added section="profile">
               <p>
                Add a box to your profile or boxes page to display your fantasy rank(s) to your friends.
                <fb:add-section-button section="profile" />
               </p>
              </fb:if-section-not-added>
              <p>
               To adjust where the box appears or to remove the box altogether, use the options icon at the top of the box.
              </p>
             </fb:explanation>
            <?php
        }
        else
        {
            ?>
             <form action="http://lightdatasys.com/facebook/account.php" method="post">
              <fb:explanation>
               <fb:message>Link Accounts</fb:message>
               <p style="width: 400px; ">
                This process will link your Facebook and Lightdatasys accounts
                in a few simple steps. The first step forwards you to the
                Lightdatasys web site to verify or register for a Lightdatasys account.
               </p>
               <p>
                <input type="submit" value="Get Started" class="inputsubmit" />
               </p>
              </fb:explanation>
             </form>
            <?php
        }
    }
}









?>
