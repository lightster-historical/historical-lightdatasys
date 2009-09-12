<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class ResultsResponder extends NascarResponder
{
    public function getPageTitle()
    {
        $race = $this->getRace();
        $season = $this->getSeason();

        $resultStatus = '';
        if($race->hasEntryList())
            $resultStatus = 'Entry List';
        else if($race->hasLineup())
            $resultStatus = 'Lineup';
        else if($race->hasResults())
        {
            if($race->isOfficial())
                $resultStatus = 'Official Results';
            else
                $resultStatus = 'Unofficial Results';
        }
        else
            return 'Results';

        return $race->getName() . ' - ' . $resultStatus;
    }


    public function get($args, $cacheDir = null)
    {
        $race = $this->getRace();
        $series = $this->getSeries();
        $season = $this->getSeason();

        $results = $race->getResults()->getByRank();

        $this->printHeader();

        if(count($results) > 0)
        {
            ?>
             <dl class="header-data">
              <dt>Season</dt>
              <dd><?php echo $season->getYear(); ?> <?php echo $series->getName(); ?></dd>
              <dt>Race</dt>
              <dd>
               <?php echo $race->getNumber() . ' &ndash; ' . $race->getName(); ?>
            <?php
            if(!$race->isForPoints())
                echo ' <em>(non-points event)</em>';
            ?>
              </dd>
              <dt>Track</dt>
              <dd><?php echo $race->getTrack()->getName(); ?></dd>
              <dt>Date</dt>
              <dd><?php echo $this->printDate($race->getDate(), 'l, F j, Y, g:i a'); ?></dd>
             </dl>
             <div class="table-default center">
              <table>
               <tr>
            <?php
            if($race->hasResults() || $race->hasLineup())
            {
                if($race->hasResults())
                    echo '<th style="width: 22px; ">F</th>';
                echo '<th style="width: 22px; ">S</th>';
            }
            ?>
                <th style="width: 22px; ">#</th>
                <th colspan="2">Driver</th>
               </tr>
            <?php

            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));

            foreach($results as $result)
            {
                ?>
                 <tr class="<?php echo $rowStyle; ?>">
                <?php
                if($race->hasResults() || $race->hasLineup())
                {
                    if($race->hasResults())
                        echo '<td>' . $result->getFinish() . '</td>';
                    echo '<td>' . $result->getStart() . '</td>';
                }
                ?>
                  <td><?php echo $result->getCar(); ?></td>
                <?php
                $result->getDriver()->printCellSet();
                ?>
                 </tr>
                <?php
            }

            ?>
              </table>
             </div>
            <?php
        }
        else
        {
            ?>
             <div class="tip-message">
              Race results have not yet been posted. Please check back after the first race.
             </div>
            <?php
        }

        $this->printFooter();
    }
}



?>
