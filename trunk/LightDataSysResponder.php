<?php


require_once PATH_LIB . 'com/lightdatasys/LdsNavIterator.php';
require_once PATH_LIB . 'com/lightdatasys/LdsPermissions.php';
require_once PATH_LIB . 'com/lightdatasys/help/Help.php';
require_once PATH_LIB . 'com/mephex/core/Input.php';
require_once PATH_LIB . 'com/mephex/core/SmtpMail.php';
require_once PATH_LIB . 'com/mephex/db/MySQL.php';
require_once PATH_LIB . 'com/mephex/framework/CacheableResponder.php';
require_once PATH_LIB . 'com/mephex/nav/Navigation.php';
require_once PATH_LIB . 'com/mephex/nav/NavItem.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/user/MephexPermissions.php';
require_once PATH_LIB . 'com/mephex/util/context/DefaultContext.php';
require_once PATH_LIB . 'com/mephex/language/Language.php';
require_once PATH_LIB . 'com/mephex/dev/TickLogger.php';


class LightDataSysResponder extends CacheableResponder
{
    protected $bodyAttributes;
    protected $onLoad;

    protected $startTime;

    protected $user;
    protected $cookie;

    protected $playerName;
    protected $playerShortName;
    protected $playerColor;

    protected $navIter;
    protected $navMain;
    protected $navGlobal;
    protected $navFooter;

    protected $relatedNavLinks;

    protected $mailer;

    protected $pageTitle;

    protected $tickLogger;
    protected $tickLoggingStatus;


    public function init($args, $cacheDir = null)
    {
        $this->tickLoggingStatus = array_key_exists('debug', $_REQUEST);
        $this->tickLogger = null;
        if($this->isTickLoggingEnabled())
            $this->getTickLogger()->start();

        parent::init($args, $cacheDir);

        MXT_Debug::setDebugPassword('neverguess');
        MXT_Debug::setVisibilityUsingRequestVars();

        MXT_Language::pushLanguage('en-us');

        #error_reporting(E_ALL | E_STRICT);
        #set_error_handler(array($this, 'translateErrorToException'));

        $this->bodyAttributes = array();
        $this->onLoad = '';

        $this->startTime = microtime(true);

        $this->connectToDbServer();

        $this->cookie = Cookie::getInstance('lds_', '', 60*24*365, '/');
        $this->user = User::getActiveUser();

        $user = $this->getUser();
        if(!is_null($user))
        {
            $context = MXT_DefaultContext::getDefaultContext();
            $context->set('timezone', $user->getTimezone());
        }

        //$this->checkLoggedIn($args);

        PermissionDomain::loadAll();

        MephexPermissions::getInstance();
        LdsPermissions::getInstance();

        $this->loadNavigation();

        $this->relatedNavLinks = array();

        $this->mailer = null;

        $this->pageTitle = null;
    }


    public function isTickLoggingEnabled()
    {
        return $this->tickLoggingStatus;
    }

    public function getTickLogger()
    {
        if(is_null($this->tickLogger))
            $this->tickLogger = new MXT_TickLogger();

        return $this->tickLogger;
    }


    public function connectToDbServer()
    {
        $db = new MySQL('localhost', 'litesign_mlight', '***REMOVED***'
            , 'litesign_alpha2');
        $conn = Database::setHash($db, 'com.lightdatasys');
        $conn = Database::setHash($db, 'com.mephex.user');
        $conn = Database::setHash($db, 'com.mephex.nav');
        $conn = Database::setHash($db, 'com.mephex.aggregator');
        $conn->setTablePrefix('agg');
        $conn = Database::setHash($db, 'com.mephex.captcha');
        $conn = Database::setHash($db, 'com.mattlight.fitness');
        $conn->setTablePrefix('fitness');
    }


    public function checkPermissions()
    {
        $this->checkPermission('com.lightdatasys', 'read');
    }


    public function printHeader()
    {
        $db = Database::getConnection('com.lightdatasys');
        $responder = $this;

        include PATH_ROOT . 'shared/header.php';
    }

    public function printExtendedHTMLHead()
    {
    }

    public function printOpenBodyTag()
    {
        $bodyAttr = '';
        if(count($this->bodyAttributes) > 0)
            $bodyAttr = implode(' ', $this->bodyAttributes);

        ?>
         <body onload="<?php echo $this->onLoad; ?>" <?php echo $bodyAttr; ?>>
        <?php
    }

    public function printWelcomeMessage()
    {
        if($this->isLoggedIn())
        {
            /*<a href="/user/profile.php?user=<?php echo $this->user->getId(); ?>"></a>*/
            ?>
             Welcome back, <?php echo $this->getUser()->getUsername(); ?>!
             <!--You have <a href="/messenger">no</a> new messages.-->
            <?php
        }
        else
        {
            ?>
             Please <a href="/user/sign-in.php">sign in</a> <!--or <a href="">register</a>--> to fully experience Lightdatasys.
            <?php
        }
    }

    public function printNavHierarchy()
    {
        $selectedItem = $this->getSelectedNavItem();

        if(!is_null($selectedItem))
        {
            $hierarchy = '';
            $base = basename($_SERVER['PHP_SELF']);
            if($selectedItem->getKeyName() == $base || $base == 'index.php')
                $parent = $selectedItem->getParent();
            else
                $parent = $selectedItem;

            while(!is_null($parent))
            {
                $hierarchy = ' &raquo; <a href="' . $parent->getURL() . '">' . $parent->getTitle() . '</a>' . $hierarchy;
                $parent = $parent->getParent();
            }
            if($selectedItem->getTitle() != 'Home')
                $hierarchy = '<a href="/">Lightdatasys</a>' . $hierarchy;

            echo '<h2 style="display: inline;">' . $hierarchy . '</h2>';
        }
    }

    public function printPageTitle()
    {
        if($this->getPageTitle() != '')
        {
            ?>
             <h3><?php echo $this->getPageTitle(); ?></h3>
            <?php
        }
    }

    public function printHelpNavItem()
    {
        $helpStyle = $this instanceof Help ? 'help' : 'help-disabled';

        /*
        ?>
         <li class="<?php echo $helpStyle; ?>"><a href="/help/index.php?page=<?php echo urlencode($_SERVER['PHP_SELF']); ?>">Help</a></li>
        <?php
        */
    }

    public function printFooter()
    {
        $responder = $this;
        include PATH_ROOT . 'shared/footer.php';
    }

    public function printDebugInfo()
    {
        if(array_key_exists('debug', $_REQUEST)
            && true)//Permission::getPermission($this->user, 'com.mattlight', 'admin'))
        {
            ?>
            <div style="margin: 10px; padding: 5px; background: #ccffcc; overflow: scroll; ">
             <h4 style="margin: 3px; padding: 2px; ">Debug Information</h4>
             <h5 style="margin: 10px 3px 3px 3px; padding: 2px; ">$_SERVER</h5>
             <table>
             <?php
             foreach($_SERVER as $key => $value)
             {
                 echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
             }
             ?>
              </table>
              <h5 style="margin: 10px 3px 3px 3px; padding: 2px; ">SQL Queries</h5>
              <table>
            <?php
            $queries = Query::getHistory();
            foreach($queries as $query)
            {
                echo '<tr><td style="font-size: .8em; ">';
                printf('%.1fms', round(1000*($query->getTime()), 1));
                echo '</td><td>';
                echo '<span style="font-family: monospace; font-size: 1.0em; ">' . htmlentities($query->getQuery()) . '</span>';
                $backtrace = $query->getDebugBacktrace();
                echo $backtrace[1]['class'] , '->' , $backtrace[1]['function'];
                echo '</td></tr>';
            }
            ?>
              </table>
            <?php
            if($this->isTickLoggingEnabled())
            {
                ?>
                 <h5 style="margin: 10px 3px 3px 3px; padding: 2px; ">
                  Deepest Backtrace (<?php echo $this->getTickLogger()->getDeepestBacktraceLength(); ?>)
                 </h5>
                 <pre><?php
                 $backtrace = $this->getTickLogger()->getDeepestBacktrace();
                 //*
                 foreach($backtrace as $level)
                 {
                     if(array_key_exists('class', $level))
                         echo htmlentities($level['class'] . '->');
                     echo htmlentities($level['function']) . "()\n";
                 }//*/
                 ?></pre>
                  <h5 style="margin: 10px 3px 3px 3px; padding: 2px; ">Tick Counts by Class/Method</h5>
                  <div class="table-default">
                  <table>
                  <?php
                  $methodTicks = $this->getTickLogger()->getMethodTickCount();
                  $methodTimes = $this->getTickLogger()->getMethodTimes();
                  $classTicks = $this->getTickLogger()->getClassTickCount();
                  $classTimes = $this->getTickLogger()->getClassTimes();

                  $totalMethodTime = 0;
                  $totalClassTime = 0;
                  //*
                  foreach($methodTicks as $class => $funcTicks)
                  {
                      $firstRowOfClass = true;
                      foreach($funcTicks as $method => $count)
                      {
                          if(array_key_exists($class, $methodTimes)
                              && array_key_exists($method, $methodTimes[$class]))
                              $methodTime = $methodTimes[$class][$method];
                          else
                              $methodTime = 0;
                          $totalMethodTime += $methodTime;

                          if($firstRowOfClass)
                          {
                              if(array_key_exists($class, $classTicks))
                                  $classCount = $classTicks[$class];
                              else
                                  $classCount = 0;

                              if(array_key_exists($class, $classTimes))
                                  $classTime = $classTimes[$class];
                              else
                                  $classTime = 0;
                              $totalClassTime += $classTime;

                              $methodCount = count($funcTicks);
                              ?>
                               <tr>
                                <td rowspan="<?php echo $methodCount; ?>"><?php echo $class; ?></td>
                                <td rowspan="<?php echo $methodCount; ?>"><?php echo $classCount; ?></td>
                                <td rowspan="<?php echo $methodCount; ?>"><?php printf('%.1fms', round(1000*$classTime, 1)); ?></td>
                                <td><?php echo $method; ?></td>
                                <td><?php echo $count; ?></td>
                                <td><?php printf('%.1fms', round(1000*$methodTime, 1)); ?></td>
                               </tr>
                              <?php
                              $firstRowOfClass = false;
                          }
                          else
                          {
                              ?>
                               <tr>
                                <td><?php echo $method; ?></td>
                                <td><?php echo $count; ?></td>
                                <td><?php printf('%.1fms', round(1000*$methodTime, 1)); ?></td>
                               </tr>
                              <?php
                          }
                      }
                  }//*/
                  ?>
                   <tr>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                    <th><?php printf('%.1fms', round(1000*$totalClassTime, 1)); ?></th>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                    <th><?php printf('%.1fms', round(1000*$totalMethodTime, 1)); ?></th>
                   </tr>
                  </table>
                  </div>
                  <h5 style="margin: 10px 3px 3px 3px; padding: 2px; ">Function Ticks</h5>
                  <pre><?php print_r($this->getTickLogger()->getFunctionTickCount()); ?></pre>
                <?php
            }
            ?>
              <h5 style="margin: 10px 3px 3px 3px; padding: 2px; ">Defined Classes</h5>
            <?php
            MXT_Debug::output('classes', get_declared_classes());
            ?>
             </div>
            <?php
        }
    }

    public function printDebugStats()
    {
        if(defined('START_TIME')
            && Permission::getPermission($this->getUser(), 'com.lightdatasys', 'admin'))
        {
            $keyVals = array();
            foreach($_GET as $key => $val)
            {
                if($key != 'debug')
                    $keyVals[] = $key . '=' . $val;
            }

            if(!array_key_exists('debug', $_REQUEST))
                $keyVals[] = 'debug=1';

            if(count($keyVals) > 0)
            {
                $qs = '?' . implode('&amp;', $keyVals);
            }
            else
            {
                $qs = '';
            }
            ?>
             <div style="text-align: right; ">
              <?php printf('%.1fms', round(1000*(microtime(true) - START_TIME), 1)); ?> overall,
            <?php
            if($this->isTickLoggingEnabled())
                echo $this->getTickLogger()->getTickCount() , ' ticks, ';
            ?>
              <?php echo Database::getQueryCount(); ?> queries,
              <?php printf('%.1fms', round(1000*(Database::getTime()), 1)); ?> db,
              <?php echo number_format(ob_get_length() / 1024, 2); ?> bytes,
              <a href="<?php echo $_SERVER['PHP_SELF'] . $qs; ?>">debug</a>
            <?php
            if($this instanceof CacheableResponder)
            {
                $lastUpdated = $this->getCacheLastUpdated();
                if(!is_null($lastUpdated))
                    echo '<br /><em>Last cached ' . $this->getDate($lastUpdated, 'F j, Y, g:i a') . '</em>';
            }
            ?>
             </div>
             &nbsp;
            <?php
        }
    }

    public function getTheme()
    {
        return '2009';
    }


    public function printDate(Date $date, $format = null, $relative = false)
    {
        echo $this->getDate($date, $format, $relative);
    }

    public function getDate(Date $date, $format = null, $relative = false)
    {
        $timeZone = $this->getTimezone();

        $lastWeek = new Date();
        $lastWeek->changeDay(-7);

        if(is_null($format))
            $format = 'F j, Y, g:i a';

        if($relative && $lastWeek->compareTo($date) < 0)
        {
            $yesterday = new Date();
            $yesterday->changeDay(-1);
            $today = new Date();
            $lastHour = new Date();
            $lastHour->changeHour(-1);

            if($lastHour->compareTo($date) < 0)
                return abs($date->getMinute() - $lastHour->getMinute()) . ' minutes ago';
            else if($today->format('Ymd', $timeZone) == $date->format('Ymd', $timeZone))
                return 'Today, ' . $date->format('g:i a', $timeZone);
            else if($yesterday->format('Ymd', $timeZone) == $date->format('Ymd', $timeZone))
                return 'Yesterday, ' . $date->format('g:i a', $timeZone);
            else
                return $date->format('l, g:i a', $timeZone);
        }
        else
        {
            return $date->format($format, $timeZone);
        }
    }

    public function getTimezone()
    {
        $timeZone = -7;
        $user = $this->getUser();
        if(!is_null($user))
            $timeZone = $user->getTimeZone();

        return $timeZone;
    }


    public function getUser()
    {
        return $this->user;
    }

    public function isLoggedIn()
    {
        return !is_null($this->user);
    }

    public function checkLoggedIn($args)
    {
        if(is_null($this->user))
        {
           $responder = new SignInFormResponder();
           $responder->init($args);
           if(count($_POST) > 0)
               $responder->post($args);

            ?>
             <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
             <html>
              <head>
              <title>Light Data Sys</title>
               <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
               <link rel="stylesheet" href="/shared/2008/style.css" type="text/css" />
              </head>
              <body onLoad="document.getElementById('username').select(); document.getElementById('username').focus(); ">
               <div id="container">
                <div id="header">
                 <div id="logo"><!--Light Data Sys--><img src="/shared/2008/logo.gif" alt="LightDataSys" /></div>
                 <br style="clear: both; " />
                </div>
                <div style="background: #0066cc; height: 15px; "></div>
                <div class="lower-container" style="margin: 0 auto; ">
                 <div id="body-container">
                  <div id="content">
           <?php
           $responder->get($args);
           ?>
                  </div>
                 </div>
                </div>
                <div style="background: #0066cc; height: 15px; "></div>
               </div>
              </body>
             </html>
            <?php

            exit;
        }
    }

    public function requireSignIn($args)
    {
        if(!$this->isLoggedIn())
        {
            require_once PATH_LIB . 'com/lightdatasys/public_html/user/sign-in.php';

            $responder = new SignInResponder(false);
            $responder->init($args);
            $responder->post($args);

            exit;
        }
    }

    public function checkPermission($domain, $keyName)
    {
        if(!$this->getPermission($domain, $keyName))
        {
            $this->printHeader();
            echo 'You do not have permission to view this page.';
            $this->printFooter();

            exit;
        }
    }

    public function getPermission($domain, $keyname)
    {
        return Permission::getPermission($this->getUser(), $domain, $keyname);
    }


    public function getSelectedNavItem()
    {
        $fileName = $_SERVER['PHP_SELF'];

        $selectedItem = $this->getGlobalNav()->getItemFromFileName($fileName, false);
        if(is_null($selectedItem))
            $selectedItem = $this->getMainNav()->getItemFromFileName($fileName, false);
        if(is_null($selectedItem))
            $selectedItem = $this->getFooterNav()->getItemFromFileName($fileName, false);

        return $selectedItem;
    }

    public function printNav(Navigation $nav)
    {
        $this->getNavIterator()->printNavigation($nav, Navigation::getKeysFromFileName($_SERVER['PHP_SELF']));
    }

    public function getNavIterator()
    {
        return $this->navIter;
    }

    public function loadNavigation()
    {
        $this->navIter = new LdsNavIterator();
        $this->navMain = Navigation::getNavigation('lds-main');
        $this->navGlobal = Navigation::getNavigation('lds-global');
        $this->navFooter = Navigation::getNavigation('lds-footer');
    }

    public function getGlobalNav()
    {
        return $this->navGlobal;
    }

    public function getMainNav()
    {
        return $this->navMain;
    }

    public function getFooterNav()
    {
        return $this->navFooter;
    }



    public function getPlayerName()
    {
        if(is_null($this->playerName))
            $this->loadPlayerInformation();

        return $this->playerName;
    }

    public function getPlayerShortName()
    {
        if(is_null($this->playerShortName))
            $this->loadPlayerInformation();

        return $this->playerShortName;
    }

    public function getPlayerColor()
    {
        if(is_null($this->playerColor))
            $this->loadPlayerInformation();

        return $this->playerColor;
    }

    public function loadPlayerInformation()
    {
        $db = Database::getConnection('com.lightdatasys');

        $query = new Query('SELECT u.username, p.name, p.bgcolor FROM '
            . $db->getTable('user') . ' AS u INNER JOIN '
            . $db->getTable('player_user') . ' AS pu ON u.userId=pu.userId '
            . 'INNER JOIN ' . $db->getTable('player')
            . ' AS p ON pu.playerId=p.playerId '
            . 'WHERE u.userId=' . $this->user->getId());
        $result = $db->execQuery($query);
        if($row = $db->getRow($result))
        {
            $name = explode(' ', $row[1]);

            $this->playerName = $row[0];
            $this->playerShortName = substr($name[0], 0, 1)
                . strtolower(substr($name[0], -1, 1));
            $this->playerColor = $row[2];
        }
    }


    public function printPlayerCell()
    {
        $style = ' style="background-color: #' . $this->getPlayerColor() . '; "';
        $title = ' title="' . $this->getPlayerName() . '"';

        echo '<span class="player-cell"' . $style . $title . '>';
        echo $this->getPlayerShortName();
        echo '</span>';
    }



    public function getDocumentTitle()
    {
        $selectedItem = $this->getSelectedNavItem();

        $title = 'Lightdatasys';
        if(!is_null($selectedItem))
        {
            $pageTitle = $this->getPageTitle();
            $item = $this->getSelectedNavItem();
            $base = basename($_SERVER['PHP_SELF']);
            if($item->getKeyName() == $base || $base == 'index.php')
                $item = $item->getParent();
            while(!is_null($item))
            {
                $pageTitle = $item->getTitle() . ' : ' . $pageTitle;
                //$delim = ' : ';
                $item = $item->getParent();
            }

            if($pageTitle != '')
                $title .= ' | ' . $pageTitle;
            else
                $title .= ' Home';
        }
        else
        {
            $title .= ' Home';
        }

        return $title;
    }


    public function setPageTitle($title)
    {
        $this->pageTitle = $title;
    }

    public function getPageTitle()
    {
        if(is_null($this->pageTitle))
        {
            $selectedItem = $this->getSelectedNavItem();

            if(!is_null($selectedItem))
                $this->pageTitle = $selectedItem->getTitle();
            else
                $this->pageTitle = '';
        }

        return $this->pageTitle;
    }

    public function printPageNav()
    {
        $selectedItem = $this->getSelectedNavItem();

        if(!is_null($selectedItem))
        {
            $minCount = 0;
            $children = $selectedItem->getChildren();
            if($selectedItem->getKeyName() == 'index.php')
            {
                $parent = $selectedItem->getParent();
                if(!is_null($parent))
                {
                    $children = $parent->getChildren();
                    $minCount = 1;
                }
            }

            $relatedNavLinks = $this->getRelatedNavLinks();

            $hierItem = $selectedItem;
            while(count($children) <= $minCount && !is_null($hierItem))
            {
                $hierItem = $selectedItem->getParent();
                if(!is_null($hierItem))
                    $children = $hierItem->getChildren();
                $minCount = 0;
            }

            $base = basename($_SERVER['PHP_SELF']);
            if(!is_null($selectedItem) && ($selectedItem->getKeyName() == $base || $base == 'index.php') && (count($children) > $minCount || count($relatedNavLinks) > 0))
            {
                ?>
                 <div id="child-nav">
                <?php
                if(count($children) > $minCount)
                {
                    ?>
                      <ul>
                    <?php
                    foreach($children as $child)
                    {
                        $permissionDomain = $child->getPermissionDomain();
                        $permission = $child->getPermission();

                        if($permissionDomain == '' || $permission == ''
                            || Permission::getPermission(User::getActiveUser(), $permissionDomain, $permission))
                        {
                        //if($child->getKeyName() != ''
                        //    && $child->getId() != $selectedItem->getId())
                        //{
                            ?>
                             <li><a href="<?php echo $child->getURL(); ?>"><?php echo $child->getTitle(); ?></a></li>
                            <?php
                        //}
                        }
                    }
                    ?>
                      </ul>
                    <?php
                }

                if(count($relatedNavLinks) > 0)
                {
                    ?>
                     <ul style="float: right; ">
                    <?php
                    foreach($relatedNavLinks as $linkUrl => $linkTitle)
                    {
                        ?>
                         <li><a href="<?php echo $linkUrl; ?>"><?php echo $linkTitle; ?></a></li>
                        <?php
                    }
                    ?>
                     </ul>
                    <?php
                }
                ?>
                  <br style="clear: both; " />
                 </div>
                <?php
            }
        }
    }

    public function printSelector()
    {
    }


    public function addRelatedNavLink($url, $title)
    {
        $this->relatedNavLinks[$url] = $title;
    }

    public function getRelatedNavLinks()
    {
        return $this->relatedNavLinks;
    }


    public function getMailer()
    {
        $auth = array
        (
            'username' => 'commissioner+lightdatasys.com',
            'password' => '***REMOVED***',
            'host' => '{mail.lightdatasys.com:995/pop3/ssl/novalidate-cert}INBOX'
        );
        if(is_null($this->mailer))
            $this->mailer = new MxSmtpMail('mail.lightdatasys.com', 26, $auth, false);

        return $this->mailer;
    }
}


?>
