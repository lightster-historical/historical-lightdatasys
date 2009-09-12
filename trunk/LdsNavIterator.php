<?php


require_once PATH_LIB . 'com/mephex/nav/Navigation.php';
require_once PATH_LIB . 'com/mephex/nav/NavItem.php';
require_once PATH_LIB . 'com/mephex/nav/NavIterator.php';


class LdsNavIterator extends NavIterator
{
    public function printItem(NavItem $item, $keys, $depth, $parentSelected = true)
    {
        if($item->getKeyName() == '')
        {
            if($depth > 0)
            {
                ?>
                 </ul>
                 <hr />
                 <ul>
                <?php
            }
            else
            {
                ?>
                 <li>|</li>
                <?php
            }
        }
        else
        {
            $hasChildren = (count($item->getChildren()) > 0);

            $selected = '';
            if($depth < count($keys) && $keys[$depth] == $item->getKeyName()
                && $parentSelected)
            {
                $selected = ' class="selected"';
            }

            $style = '';
            $mouseJS = ' onmouseover="javascript: setNavDisplay('
                . $item->getId() . ', true, ' . $depth . '); " '
                . ' onmouseout="javascript: setNavDisplay('
                . $item->getId() . ', false, ' . $depth . '); "';

            $parent = $item;
            $rootId = $parent->getId();
            while(!is_null($parent->getParent()))
            {
                $parent = $parent->getParent();
                $rootId = $parent->getId();
            }

            ?>
             <li id="nav-li-<?php echo $item->getId(); ?>"<?php echo $selected . $mouseJS; ?>>
              <a href="<?php echo $item->getURL(); ?>" onclick="javascript: setNavDisplay(<?php echo $rootId; ?>, false, 0); return true; " id="nav-link-<?php echo $item->getId(); ?>"><?php echo $item->getTitle(); ?><?php echo ($hasChildren && $depth > 0) ? '&nbsp;&raquo;' : ''; ?></a>
            <?php
            if($hasChildren)
            {
                ?>
                 <div class="sub-nav" id="sub-nav-<?php echo $item->getId(); ?>"<?php echo $depth >= 1 ? ' style="left: 100%; top: 0px; "' : ''; ?>>
                  <ul>
                <?php
                parent::printItem($item, $keys, $depth, $parentSelected);
                ?>
                  </ul>
                 </div>
                <?php
            }
            ?>
             </li>
            <?php
        }
    }
}


?>
