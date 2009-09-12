<?php



require_once PATH_LIB . 'com/mephex/data-object/DataObject.php';
require_once PATH_LIB . 'com/mephex/data-object/list/AbstractList.php';
require_once PATH_LIB . 'com/mephex/data-object/list/outputter/ResponderListTableOutputter.php';



class LDS_RaceListTableOutputter extends MXT_DO_ResponderListTableOutputter
{
    protected $isRaceForChaseDisplayed;
    protected $isChaseDisplayed;
    protected $isNonPointsDisplayed;
    
    
    public function __construct(MXT_DO_AbstractManageResponder $responder)
    {
        parent::__construct($responder);
        
        $this->isEditAllowed = $this->getResponder()->isEditAllowed();
    }


    public function outputList(MXT_DO_AbstractList $list)
    {
        $this->isFirstNonPointsDisplayed = false;

        parent::outputList($list);
    }

    public function getDivStyleClass()
    {
        return 'table-default center';
    }


    public function isEditAllowed()
    {
        return $this->isEditAllowed;
    }


    public function outputTableHeaderRowExtras(MXT_DO_AbstractList $list)
    {
        ?>
         <th>Results</th>
         <th>Standings</th>
         <th colspan="2">Fantasy</th>
        <?php
        if($this->isEditAllowed())
        {
            echo '<th colspan="2">Manage</th>';
        }
    }

    public function getColumnCount()
    {
        $count = 8;
        if($this->isEditAllowed())
            $count += 2;

        return $count;
    }

    public function outputListObject(MXT_DO_AbstractList $list, $i)
    {
        $obj = $list->getObjectUsingOffset($i);

        if($obj instanceof LDS_Race)
        {
            if(!$this->isChaseDisplayed && $obj->isForPoints() && $obj->isChaseRace())
            {
                $this->isChaseDisplayed = true;

                ?>
                 <tr>
                  <th colspan="<?php echo $this->getColumnCount(); ?>"><em>Chase Events</em></th>
                 </tr>
                <?php
            }

            if(!$this->isRaceForChaseDisplayed && $obj->isForPoints() && !$obj->isChaseRace() != 0 && $obj->getSeason()->getChaseRaceNo() > 0)
            {
                $this->isRaceForChaseDisplayed = true;

                ?>
                 <tr>
                  <th colspan="<?php echo $this->getColumnCount(); ?>"><em>Race to the Chase Events</em></th>
                 </tr>
                <?php
            }

            if(!$this->isNonPointsDisplayed && !$obj->isForPoints())
            {
                $this->isNonPointsDisplayed = true;

                ?>
                 <tr>
                  <th colspan="<?php echo $this->getColumnCount(); ?>"><em>Non-points Events</em></th>
                 </tr>
                <?php
            }
        }

        parent::outputListObject($list, $i);
    }

    public function outputListObjectRowExtras(MXT_DO_AbstractList $list, MXT_DataObject $obj)
    {
        $id = $obj->getId();

        $race = $obj;
        $qs = '?raceId=' . $race->getId();
        if($race->getResultsType() != 0)
        {
            switch($race->getResultsType())
            {
                case LDS_Race::RESULT_ENTRY_LIST:
                    $resultStatus = 'Entry List';
                    break;
                case LDS_Race::RESULT_LINEUP:
                    $resultStatus = 'Lineup';
                    break;
                case LDS_Race::RESULT_UNOFFICIAL:
                    $resultStatus = 'Unofficial';
                    break;
                case LDS_Race::RESULT_OFFICIAL:
                    $resultStatus = 'Official';
                    break;
                default:
                    $resultStatus = '';
            }
            
            /*
            $resultStatus = '';
            if($race->hasEntryList())
                $resultStatus = 'Entry List';
            else if($race->hasLineup())
                $resultStatus = 'Lineup';
            else if($race->hasResults())
            {
                if($race->isOfficial())
                    $resultStatus = 'Official';
                else
                    $resultStatus = 'Unofficial';
            }
            */
            ?>
             <td>
              <a href="results.php<?php echo $qs; ?>"><?php echo $resultStatus; ?></a>
             </td>
             <td>
            <?php

            if($race->isForPoints() && $race->hasResults())
            {
                ?>
                 <a href="driver-standings.php<?php echo $qs; ?>"><?php echo $resultStatus; ?></a>
                <?php
            }
            else
            {
                ?>
                 --
                <?php
            }

            ?>
             </td>
             <td>
              <a href="fantasy/picks.php<?php echo $qs; ?>">Picks</a>
             </td>
             <td>
            <?php

            if($race->isForPoints())
            {
                ?>
                 <a href="fantasy/standings.php<?php echo $qs; ?>">Standings</a>
                <?php
            }
            else
            {
                ?>
                 --
                <?php
            }

            ?>
             </td>
            <?php
        }
        else
        {
            $now = new Date();
            ?>
             <td>&nbsp;</td>
             <td>&nbsp;</td>
            <?php
            if($race->getPickStatus() >= '1'
                || $now->compareTo($race->getDate()) > 0)
            {
                ?>
                 <td><a href="fantasy/picks.php<?php echo $qs; ?>">Picks</a></td>
                 <td>
                <?php

                if($race->isForPoints())
                {
                    ?>
                     <a href="fantasy/standings.php<?php echo $qs; ?>">Standings</a>
                    <?php
                }
                else
                {
                    ?>
                     --
                    <?php
                }

                ?>
                 </td>
                <?php
            }
            else
            {
                ?>
                 <td>&nbsp;</td>
                 <td>&nbsp;</td>
                <?php
            }
            ?>
            <?php
        }

        if($this->isEditAllowed())
        {
            ?>
             <td><a href="?raceId=<?php echo $id; ?>&amp;action=edit">Race</a></td>
             <td><a href="/nascar/manage/edit-results.php?raceId=<?php echo $id; ?>">Results</a></td>
            <?php
        }
    }
}



?>
