<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class IndexResponder extends LightDataSysResponder
{
    public function checkPermissions()
    {
        parent::checkPermissions();
        $this->checkPermission('com.lightdatasys', 'usercp');
    }


    public function get($args)
    {
        $this->printHeader();

        ?>
         <!--<h3>Hello <em><?php echo $this->user->getUsername(); ?></em>!</h3>-->
         What are you looking to accomplish today?
         <ul>
          <li><a href="/prefs/password.php">Change my password</a></li>
          <!--<li><a href="/user/prefs/display-name.php">Change my display name</a></li>-->
          <li><a href="/prefs/color.php">Choose my display color</a></li>
         </ul>
        <?php

        $this->printFooter();
    }
}


?>
