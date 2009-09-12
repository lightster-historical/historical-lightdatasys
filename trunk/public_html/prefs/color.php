<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class ColorResponder extends LightDataSysResponder
{
    public function checkPermissions()
    {
        parent::checkPermissions();
        $this->checkPermission('com.lightdatasys', 'usercp');
    }


    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys');

        $this->printHeader();

        $sql = new Query('SELECT playerId FROM player_user WHERE userId=' . $this->user->getId());
        $query = $db->execQuery($sql);
        $row = $db->getRow($query);
        $playerId = intval($row[0]);

        $this->input->set('color', IntegerInput::getInstance());
        if($this->input->get('color') > 0)
        {
            $sql = new Query('UPDATE player SET bgcolor=\''
                . sprintf('%06X', $this->input->get('color')) . '\' WHERE playerId=' . $playerId);
            $query = $db->execQuery($sql);
        }

        $players = array();
        $query = new Query('SELECT playerId, name, bgcolor FROM player ORDER BY name');
        $result = $db->execQuery($query);
        while($row = $db->getRow($result))
        {
            $players[$row[0]] = $row;
        }

        $player = $players[$playerId];
        $name = explode(' ', $player[1]);
        $initials = substr($name[0], 0, 1) . strtolower(substr($name[0], -1, 1));
        ?>
          <h3>
           <?php echo '<span style="color: #ffffff; background-color: #' . $player[2] . '; padding: 2px; margin: 1px; " title="' . $player[1] . '">' . $initials . '</span>'; ?>
           <em><?php echo $this->user->getUserName(); ?></em> Display Color
          </h3>
          Click a color to set your display color.
         <table>
        <?php

        for($i = 0x00; $i <= 0xFF; $i += 0x33)
        {
            echo '<tr style="height: 20px; ">';
            for($j = 0x00; $j <= 0xFF; $j += 0x33)
            {
                for($k = 0x00; $k <= 0xFF; $k += 0x33)
                {
                    echo '<td style="width: 20px; padding: 0px; background-color: #';
                    printf('%02X%02X%02X', $i, $j, $k);
                    echo ';" >';
                    echo '<a href="?color=';
                    echo hexdec(sprintf('%02X%02X%02X', $i, $j, $k));
                    echo '" title="#';
                    printf('%02X%02X%02X', $i, $j, $k);
                    echo '" style="width: 20px; height: 20px; display: block; text-decoration: none; ">&nbsp;</a>';
                    echo '</td>';
                }
            }
            echo '</tr>';
        }
        ?>
         </table>
        <?php

        $this->printFooter();
    }
}


?>
