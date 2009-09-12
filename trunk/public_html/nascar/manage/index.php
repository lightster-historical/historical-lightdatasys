<?php


require_once 'path.php';


require_once PATH_LIB . 'com/mephex/core/HttpHeader.php';
HttpHeader::forwardTo('/nascar/schedule.php');

/*
require_once PATH_LIB . 'com/lightdatasys/nascar/NascarManageResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class IndexResponder extends NascarManageResponder
{
    public function getPageTitle()
    {
        $series = $this->getSeries();
        $season = $this->getSeason();

        $title = $season->getYear() . ' ' . $series->getName() . ' ' . parent::getPageTitle();

        return $title;
    }


    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $series = $this->getSeries();
        $season = $this->getSeason();
        $races = $season->getRaces();

        $this->printHeader();

        if(count($races) > 0)
        {
            ?>
             <dl class="header-data">
              <dt>Season</dt>
              <dd><?php echo $season->getYear(); ?> <?php echo $series->getName(); ?></dd>
             </dl>
             <div class="table-default">
              <table>
               <tr>
                <th>Date</th>
                <th>TV</th>
                <th>Race</th>
                <th>Track</th>
                <th colspan="3">Edit</th>
               </tr>
            <?php

            $forPoints = true;
            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));
            $raceNo = 1;
            foreach($races as $race)
            {
                if($forPoints && !$race->isForPoints())
                {
                    $forPoints = false;
                    ?>
                     <tr>
                      <th colspan="8"><em>Non-points Events</em></th>
                     </tr>
                    <?php
                }

                $selectedStyle = '';
                //if($race->getId() == $data->getRaceId())
                //    $selectedStyle = ' style="background-color: #ffffcc; "';

                //$qs = '?series=' . $data->getSeriesId() . '&amp;year=' . $data->getYear() . '&amp;race=' . $raceNo;
                $qs = '?raceId=' . $race->getId();

                ?>
                 <tr class="center <?php echo $rowStyle; ?>"<?php echo $selectedStyle; ?>>
                  <td><?php echo $this->printDate($race->getDate(), 'M j, g:i a'); ?></td>
                  <td><?php echo $race->getTvStation()->getName(); ?></td>
                  <td><?php echo $race->getName(); ?></td>
                  <td><?php echo $race->getTrack()->getName(); ?></td>
                  <td><a href="race.php?raceId=<?php echo $race->getId(); ?>">Race</a></td>
                  <td><a href="edit-results.php<?php echo $qs; ?>">Results</a></td>
                  <td><a href="edit-picks.php<?php echo $qs; ?>">Picks</a></td>
                 </tr>
                <?php

                $raceNo++;
            }
            ?>
              </table>
             </div>
             <br class="clear" />
            <?php
        }
        else
        {
            ?>
             <div class="tip-message">
              Driver standings have not yet been posted. Please check back after the first race.
             </div>
            <?php
        }

        $this->printFooter();
    }
}
*/



?>
