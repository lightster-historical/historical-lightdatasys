<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nfl/NflResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class IndexResponder extends NflResponder
{
    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.nfl');

        $this->printHeader();

        ?>
         blah blah
        <?php

        $this->printFooter();
    }
}



?>
