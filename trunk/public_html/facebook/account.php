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


class AccountResponder extends NascarResponder
{
    protected $cache;

    protected $lastUpdated;

    protected $upcoming;
    protected $pickCounts;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, null);

    }


    public function post($args)
    {
        $this->get($args);
    }


    public function get($args)
    {
        $this->printHeader();

        $facebook = new LDS_NascarFacebook();
        try
        {
            $fbUserId = $facebook->require_login();
            $values = $facebook->api_client->users_getInfo($fbUserId, array('name'));
            $fbName = $values[0]['name'];
        }
        catch(FacebookRestClientException $ex)
        {
            $fbUserId = 0;
            $fbName = '';
            debug_print_backtrace();
        }

        if($fbUserId > 0)
        {
            if(!$this->isLoggedIn())
            {
                $error = '';

                if($this->input->set('signIn'))
                {
                    $this->input->set('username');
                    $this->input->set('password');
                    $this->input->set('clientTime', IntegerInput::getInstance());

                    $username = $this->input->get('username');
                    $password = $this->input->get('password');
                    $clientTime = $this->input->get('clientTime');

                    $user = User::setActiveUser($username, $password);
                    if(!is_null($user))
                    {
                        $this->printConfirmation($args, $user, $facebook, $fbUserId, $fbName);
                        $this->printFooter();
                        exit;
                    }
                    else
                    {
                        $error = 'An invalid username or password was provided. ';
                        $error .= 'Note that passwords are case-sensitive.';
                    }
                }

                ?>
                 <form action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form-default" method="post">
                <?php
                if($error != '')
                {
                    ?>
                     <div class="error-message">
                      <?php echo $error; ?>
                     </div>
                    <?php
                }
                ?>
                  <fieldset>
                   <legend>Sign In</legend>
                   <div class="field">
                    <input type="text" class="text" name="username" id="username" />
                    <label><em>Username</em></label>
                   </div>
                   <div class="field">
                    <input type="password" class="text" name="password" />
                    <label><em>Password</em></label>
                   </div>
                  </fieldset>
                  <fieldset class="submit">
                   <input type="submit" class="submit" name="signIn" value="Sign In" />
                <?php
                $exclude = array('username', 'password', 'signIn');
                foreach($args as $key => $val)
                {
                    if(!in_array($key, $exclude))
                    {
                        ?>
                         <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlentities($val); ?>" />
                        <?php
                    }
                }
                ?>
                  </fieldset>
                 </form>
                <?php
            }
            else
            {
                $this->printConfirmation($args, $this->getUser(), $facebook, $fbUserId, $fbName);
            }
        }
        else
        {
            print_r($args);
            ?>
             <div class="error-message">
              Oops! Something went wrong. Please <a href="<?php echo $facebook->getCanvasURL('account.php'); ?>">go back to Facebook</a> and try again.
             </div>
            <?php
        }

        $this->printFooter();
    }


    protected function printConfirmation($args, $user, $facebook, $fbUserId, $fbName)
    {
        $db = Database::getConnection('com.lightdatasys');

        $query = new Query('SELECT * FROM ' . $db->getTable('FacebookAccountLink')
            . ' WHERE userId=' . intval($user->getId())
            . ' OR facebookUserId=' . $fbUserId);
        $result = $db->execQuery($query);

        if($db->getRow($result))
        {
            ?>
             <div class="error-message">
              Oops! Your Lightdatasys and/or Facebook account is already linked. <a href="<?php echo $facebook->getCanvasURL('account.php'); ?>">Return to Facebook</a>.
             </div>
            <?php
        }
        else if($this->input->set('submit_confirm'))
        {
            $query = new Query('INSERT INTO ' . $db->getTable('FacebookAccountLink')
                . ' (`userId`, `facebookUserId`) VALUES (' . intval($this->user->getId())
                . ', ' . $fbUserId . ')');
            if($db->execQuery($query))
            {
                LDS_NascarFacebookUpdater::updateBoxesUsingUserId($facebook, $this->user->getId());

                HttpHeader::forwardTo($facebook->getCanvasURL('account.php'));
            }
        }
        else if($this->input->set('submit_cancel'))
        {
            HttpHeader::forwardTo($facebook->getCanvasURL('account.php'));
        }
        else
        {
            ?>
             <form action="<?php echo $_SERVER['PHP_SELF']; ?>" class="form-default" method="post">
              <fieldset class="submit">
               <legend>Confirmation</legend>
               <div class="field">
                Please confirm that you want your <em><?php echo $fbName; ?></em>
                Facebook account and <em><?php echo $user->getUsername(); ?></em>
                Lightdatasys account to be linked.
               </div>
               <div class="field">
                <input type="submit" name="submit_confirm" value="Confirm" />
                <input type="submit" name="submit_cancel" value="Cancel" />
        <?php
        $exclude = array('submit_confirm', 'submit_cancel');
        foreach($args as $key => $val)
        {
            if(!in_array($key, $exclude))
            {
                ?>
                 <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlentities($val); ?>" />
                <?php
            }
        }
        ?>
               </div>
             </form>
            <?php
        }
    }
}









?>
