<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/FantasyPlayer.php';


class PlayerListResponder extends NascarResponder
{
    public function get($args)
    {
        $this->printHeader();

        echo '<table>';
        $players = LDS_FantasyPlayer::getAll();
        foreach($players as $player)
        {
            echo '<tr>';
            $player->printCellSet();
            echo '</tr>';
        }
        echo '</table>';

        $this->printFooter();
    }
}



?>
