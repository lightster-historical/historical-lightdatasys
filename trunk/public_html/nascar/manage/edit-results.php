<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarManageResponder.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/DataImporter.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/facebook/NascarFacebook.php';
require_once PATH_LIB . 'com/lightdatasys/nascar/facebook/NascarFacebookUpdater.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/HttpHeader.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class EditResultsResponder extends NascarManageResponder
{
    public function printSelector()
    {
    }


    public function getPageTitle()
    {
        $race = $this->getRace();

        return $race->getName() . ' ' . parent::getPageTitle();
    }


    public function post($args)
    {
        $race = $this->getRace();
        $season = $this->getSeason();
        $series = $this->getSeries();

        if($this->input->set('submit_import_results'))
        {
            $this->input->set('file');
            $file = trim($this->input->get('file'));

            if($file != '')
            {
                $result = DataImporter::importRaceResults($race->getId(), $file);
                if($result !== false)
                {
                    //HttpHeader::forwardTo('?raceId=' . $race->getId());
                }
            }
        }
        else if($this->input->set('submit_import_drivers'))
        {
            $this->input->set('drivers');
            $drivers = trim($this->input->get('drivers'));

            if($drivers != '')
            {
                $result = DataImporter::importDriverEntryList($race->getId(), $drivers);
                if($result !== false)
                {
                    #HttpHeader::forwardTo('?series=' . $data->getSeriesId() . '&season=' .
                   #     $data->getSeasonYear() . '&race=' . $data->getRaceNumber());
                }
            }
        }
        else if($this->input->set('submit_save'))
        {
            $db = Database::getConnection('com.lightdatasys.nascar');

            $this->input->set('led');
            $this->input->set('ledMost');
            $this->input->set('penalties');

            $led = $this->input->get('led');
            if(!is_array($led))
                $led = array();
            $ledMost = $this->input->get('ledMost');
            if(!is_array($ledMost))
                $ledMost = array();
            $penalties = $this->input->get('penalties');

            foreach($penalties as $driverId => $foo)
            {
                if(!array_key_exists($driverId, $led))
                    $led[$driverId] = 0;
                if(!array_key_exists($driverId, $ledMost))
                    $ledMost[$driverId] = 0;

                $query = new Query('UPDATE ' . $db->getTable('Result')
                    . ' SET `ledLaps`=' . (intval($led[$driverId]) == 1 ? '1' : '0') . ','
                    . '`ledMostLaps`=' . (intval($ledMost[$driverId]) == 1 ? '1' : '0') . ','
                    . '`penalties`=' . intval($penalties[$driverId])
                    . ' WHERE `driverId`=' . intval($driverId)
                    . ' AND `raceId`=' . intval($race->getId()));
                $db->execQuery($query);
            }
        }

        $fb = new LDS_NascarFacebook();
        LDS_NascarFacebookUpdater::updateBoxesForAllUsers($fb);

        $this->get($args);
    }


    public function get($args)
    {
        $race = $this->getRace();
        $season = $this->getSeason();
        $series = $this->getSeries();

        $this->printHeader();

        ?>
         <dl class="header-data">
          <dt>Season</dt>
          <dd><?php echo $season->getYear(); ?> <?php echo $series->getName(); ?></dd>
          <dt>Race</dt>
          <dd><?php echo $race->getNumber() . ' &ndash; ' . $race->getName(); ?></dd>
          <dt>Track</dt>
          <dd><?php echo $race->getTrack()->getName(); ?></dd>
          <dt>Date</dt>
          <dd><?php echo $this->printDate($race->getDate(), 'l, F j, Y, g:i a'); ?></dd>
         </dl>
         <form action="<?php $_SERVER['PHP_SELF']; ?>" class="form-default" method="post">
          <fieldset>
           <legend>Result Import</legend>
           <div class="field">
            <input type="text" name="file" value="" />
            <label><em>Result URL</em>This URL should be the address of the respective race's entry list, lineup, or results provided by NASCAR.com.</label>
           </div>
          </fieldset>
          <fieldset class="submit">
           <div class="field">
            <input type="submit" name="submit_import_results" value="Import" />
           </div>
          </fieldset>
         <br />
          <fieldset>
           <legend>Driver Entry List Import</legend>
           <div class="textarea-field">
            <label><em>Driver List</em>Enter a list of drivers, one driver to a line, to import the entry list.</label>
            <textarea name="drivers"></textarea>
           </div>
          </fieldset>
          <fieldset class="submit">
           <div class="field">
            <input type="submit" name="submit_import_drivers" value="Import" />
           </div>
          </fieldset>
         <br />
        <?php

        $results = $race->getResults()->getByRank();
        if(count($results) > 0)
        {
            ?>
              <div class="table-default center">
               <table>
            <?php

            $rowStyle = new RolloverIterator(array('row-a', 'row-b'));

            $count = 0;
            foreach($results as $result)
            {
                if($count % 9== 0)
                {
                    ?>
                     <tr>
                      <th colspan="8">
                       <input type="submit" name="submit_save" value="Save" />
                      </th>
                     </tr>
                     <tr>
                      <th style="width: 22px; ">F</th>
                      <th style="width: 22px; ">S</th>
                      <th style="width: 22px; ">#</th>
                      <th colspan="2">Driver</th>
                      <th>Led</th>
                      <th>Most</th>
                      <th>Adj.</th>
                     </tr>
                    <?php
                }
                $count++;

                $id = $result->getDriver()->getId();
                ?>
                 <tr class="<?php echo $rowStyle; ?>">
                  <td><?php echo $result->getFinish(); ?></td>
                  <td><?php echo $result->getStart(); ?></td>
                  <td><?php echo $result->getCar(); ?></td>
                  <?php $result->getDriver()->printCellSet(); ?>
                  <td><input type="checkbox" name="led[<?php echo $id; ?>]" value="1"<?php echo $result->getLedLaps() >= 1 ? ' checked="checked"' : ''; ?> /></td>
                  <td><input type="checkbox" name="ledMost[<?php echo $id; ?>]" value="1"<?php echo $result->getLedMostLaps() >= 1 ? ' checked="checked"' : ''; ?> /></td>
                  <td><input type="text" name="penalties[<?php echo $id; ?>]" value="<?php echo $result->getPenalties(); ?>"  style="width: 50px; " /></td>
                 </tr>
                <?php
            }

            ?>
                <tr>
                 <th colspan="8">
                  <input type="submit" name="submit_save" value="Save" />
                 </th>
                </tr>
               </table>
              </div>
            <?php
        }
        else
        {
            ?>
             <div class="tip-message">
              Race results for this race have not yet been imported.
             </div>
            <?php
        }

        ?>
          <input type="hidden" name="raceId" value="<?php echo $race->getId(); ?>" />
         </form>
        <?php

        $this->printFooter();
    }
}



?>
