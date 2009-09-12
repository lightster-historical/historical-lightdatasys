<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarManageResponder.php';
require_once PATH_LIB . 'com/mephex/user/User.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';
require_once PATH_LIB . 'com/mephex/core/RolloverIterator.php';


class EditDriversResponder extends NascarManageResponder
{
    public function printExtendedHTMLHead()
    {
        parent::printExtendedHTMLHead();
        ?>
         <style type="text/css">
          <!--
          input
          {
              width: 75px;
          }
          //-->
         </style>
        <?php
    }

    public function printSelector()
    {
    }


    public function post($args)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $this->input->set('background');
        $this->input->set('border');
        $this->input->set('color');

        $backgrounds = $this->input->get('background');
        $borders = $this->input->get('border');
        $colors = $this->input->get('color');
        foreach($backgrounds as $driverId => $foo)
        {
            $query = new Query('UPDATE ' . $db->getTable('Driver')
                . ' SET `background`=\'' . addslashes($backgrounds[$driverId]) . '\','
                . '`border`=\'' . addslashes($borders[$driverId]) . '\','
                . '`color`=\'' . addslashes($colors[$driverId]) . '\''
                . ' WHERE driverId=' . intval($driverId));
            $db->execQuery($query);
        }

        $this->get($args);
    }


    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.nascar');

        $this->printHeader();

        ?>
         <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" class="form-default">
          <div class="table-default center">
           <table>
        <?php

        $rowStyle = new RolloverIterator(array('row-a', 'row-b'));

        $count = 0;

        $query = new Query('SELECT * FROM ' . $db->getTable('Driver')
            . ' ORDER BY firstName ASC, lastName ASC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            if($count % 10 == 0)
            {
                ?>
                 <tr>
                  <th colspan="5">
                   <input type="submit" name="submit_save" value="Save" />
                  </th>
                 </tr>
                 <tr>
                  <th colspan="2">Driver</th>
                  <!--<th>First Name</th>
                  <th>Last Name</th>
                  //-->
                  <th>Border</th>
                  <th>Background</th>
                  <th>Font</th>
                 </tr>
                <?php
            }
            $count++;

            $border = $row['border'];
            $background = $row['background'];
            $fontColor = $row['color'];

            $secondaryStyle = array();
            $secondaryStyle[] = 'padding: 3px;';
            if(!empty($border))
                $secondaryStyle[] = 'background: ' . $border . ';';

            $primaryStyle = array();
            $primaryStyle[] = 'padding: 3px 6px;';
            $primaryStyle[] = 'text-align: right;';
            if(!empty($background))
                $primaryStyle[] = 'background: ' . $background . ';';
            if(!empty($fontColor))
                $primaryStyle[] = 'color: ' . $fontColor . ';';

            if(count($secondaryStyle) > 0)
                $secondaryStyle = ' style="' . implode(' ', $secondaryStyle) . '"';
            if(count($primaryStyle) > 0)
                $primaryStyle = ' style="' . implode(' ', $primaryStyle) . '"';

            $id = $row['driverId'];

            ?>
             <tr class="center <?php echo $rowStyle; ?>">
              <td id="secondary-<?php echo $id; ?>"<?php echo $secondaryStyle; ?>>&nbsp;</td>
              <td id="primary-<?php echo $id; ?>"<?php echo $primaryStyle; ?>>
               <?php echo $row['firstName'] . ' ' . $row['lastName']; ?>
              </td>
              <!--<td><input type="text" name="firstName[<?php echo $id; ?>]" value="<?php echo $row['firstName']; ?>" /></td>
              <td><input type="text" name="lastName[<?php echo $id; ?>]" value="<?php echo $row['lastName']; ?>" /></td>
              //-->
              <td><input type="text" name="border[<?php echo $id; ?>]" value="<?php echo $row['border']; ?>" onkeyup="javascript: document.getElementById('secondary-<?php echo $id; ?>').style.backgroundColor=this.value; return true; " /></td>
              <td><input type="text" name="background[<?php echo $id; ?>]" value="<?php echo $row['background']; ?>" onkeyup="javascript: document.getElementById('primary-<?php echo $id; ?>').style.backgroundColor=this.value; return true; " /></td>
              <td><input type="text" name="color[<?php echo $id; ?>]" value="<?php echo $row['color']; ?>" onkeyup="javascript: document.getElementById('primary-<?php echo $id; ?>').style.color=this.value; return true; " /></td>
             </tr>
            <?php
        }

        ?>
            <tr>
             <th colspan="5">
              <input type="submit" name="submit_save" value="Save" />
             </th>
            </tr>
           </table>
          </div>
          <br class="clear" />
         </form>
        <?php

        $this->printFooter();
    }
}



?>
