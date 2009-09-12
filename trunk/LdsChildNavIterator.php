<?php


require_once PATH_LIB . 'com/mephex/nav/Navigation.php';
require_once PATH_LIB . 'com/mephex/nav/NavItem.php';
require_once PATH_LIB . 'com/mephex/nav/NavIterator.php';


class LdsChildNavIterator extends NavIterator
{
    public function printItem(NavItem $item, $keys, $depth, $parentSelected = true)
    {
        ?>
         <li><a href="<?php echo $item->getURL(); ?>"><?php echo $item->getTitle(); ?></a></li>
        <?php
    }
}


?>
