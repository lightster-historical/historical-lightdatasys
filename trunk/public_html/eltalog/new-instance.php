<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogResponder.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Field.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Item.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/ItemInstance.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Type.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Manager.php';
require_once PATH_LIB . 'com/mephex/core/HttpHeader.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class NewInstanceResponder extends EltalogResponder
{
    protected $itemId;
    protected $item;

    protected $categoryId;
    protected $category;

    protected $locationId;

    protected $nextAction;

    protected $displayJavascript;

    protected $values;
    protected $errors;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        $db = Database::getConnection('com.lightdatasys.eltalog');

        //$this->onLoad = "javascript: createSearch('crew-0', 'value-div-crew-0', 'irr'); ";
        $this->displayJavascript = 0;

        $this->values = array();
        $this->errors = array();

        $this->input->set('item', IntegerInput::getInstance());
        $this->itemId = max(0, $this->input->get('item'));
        $this->item = null;

        $this->categoryId = 0;
        $this->category = null;

        $this->locationId = 0;
        if($this->input->set('location', IntegerInput::getInstance()))
        {
            $locations = $this->input->get('location');
            foreach($locations as $loc)
            {
                $loc = intval($loc);
                if($loc > 0)
                {
                    $this->locationId = $loc;
                }
            }
        }

        $this->input->set('systemNextAction', IntegerInput::getInstance());
        $this->nextAction = $this->input->get('systemNextAction');

        if($this->itemId > 0)
        {
            $query = new Query('SELECT item.*, value.value AS title FROM '
                . $db->getTable('Item') . ' AS item INNER JOIN '
                . $db->getTable('TypeValue') . ' AS value'
                . ' ON item.titleId=value.valueId'
                . ' WHERE itemId=' . $this->itemId);
            $result = $db->execQuery($query);
            if($row = $db->getAssoc($result))
            {
                $this->item = $row;

                $query = new Query('SELECT * FROM ' . $db->getTable('ItemCategory')
                    . ' WHERE categoryId=' . intval($this->item['categoryId']));
                $result = $db->execQuery($query);
                if($this->category = $db->getAssoc($result))
                    $this->categoryId = $this->category['categoryId'];
            }
            else
                $this->itemId = 0;
        }

        if($this->itemId <= 0)
        {
            HttpHeader::forwardTo('index.php');
        }
    }

    public function getPageTitle()
    {
        return 'Create ' . $this->category['instanceTitle'] . ' of <em>' . $this->item['title'] . '</em>';
    }

    public function printExtendedHTMLHead()
    {
        parent::printExtendedHTMLHead();

        if($this->displayJavascript == 1)
        {
            ?>
             <script src="/eltalog/form-manager.js" type="text/javascript"></script>
             <script type="text/javascript">
              <!--
              function init()
              {
                  focusForm('form');
              }
              //-->
             </script>
            <?php
        }
        else if($this->displayJavascript == 2)
        {
            $fields = Field::getByCategoryId($this->categoryId);

            ?>
             <script src="/eltalog/search-suggest.js" type="text/javascript"></script>
             <script src="/eltalog/form-manager.js" type="text/javascript"></script>
             <script type="text/javascript">
              <!--
              function init()
              {
                  fieldInits();
                  activateLocations();

                  focusForm('form');
              }

              function fieldInits()
              {
                  document.getElementById('form').onsubmit = checkSubmit;
                  document.getElementById('location-0').onchange = checkLocationValue;
                  document.getElementById('new-location-form').onsubmit = function() {return false;};
                  document.getElementById('new-location-create').onclick = createNewLocation;
                  document.getElementById('new-location-cancel').onclick = cancelNewLocation;
            <?php
            foreach($fields as $field)
            {
                if($field->getKind() == ELTALOG_KIND_INSTANCE)
                    $field->printJavascriptInits();
            }
            ?>
              }
              //-->
             </script>
            <?php
        }
    }

    public function printOpenBodyTag()
    {
        parent::printOpenBodyTag();

        ?>
         <form action="item.php" id="new-location-form" class="form-default">
          <div id="new-location" style="display: none; ">
           <div id="new-location-background" style=" z-index: 1; opacity: .5; -ms-filter:'progid:DXImageTransform.Microsoft.Alpha(Opacity=50)'; filter: alpha(opacity=50); position: absolute; margin: 0; background: #ffffff; width: 100%; left: 0; top: 0; height: 100%; ">
           </div>
           <div style="position: absolute; z-index: 2; margin: auto auto; top: 25%; left: 35%; width: 30%; opacity: 1; background: #eeeeee; padding: 5px; border: 1px solid #000000; ">
            <fieldset>
             <legend>New Location</legend>
             <div class="field">
              <span id="new-location-parent" style="float: left; ">None</span>
              <input type="hidden" name="new_location_parent" id="new-location-parent-id" value="0" />
              <label><em>Parent Location</em></label>
             </div>
             <div class="field">
              <input type="text" name="new_location_code" id="new-location-code" value="" style="width: 50px;" />
              <label><em>Code</em></label>
             </div>
             <div class="field">
              <input type="text" name="new_location_description" id="new-location-description" value="" />
              <label><em>Description</em></label>
             </div>
            </fieldset>
            <fieldset class="submit">
             <input type="submit" name="new_location_create" id="new-location-create" value="Create Location" />
             <input type="submit" name="new_location_cancel" id="new-location-cancel" value="Cancel" />
            </fieldset>
           </div>
          </div>
         </form>
        <?php
    }


    public function post($args)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        if($this->input->set('submit_cancel'))
        {
            HttpHeader::forwardTo('item.php?item=' . $this->itemId);
        }
        else if($this->input->set('submit_save'))
        {
            try
            {
                $instanceId = ItemInstance::create($this->categoryId, $this->itemId, $this->locationId, $_POST);

                $url = 'index.php';
                if($instanceId > 0)
                {
                    $action = $this->input->get('systemNextAction');
                    if($action == ELTALOG_NEXT_CREATE_ITEM)
                        $url = 'new-item.php?savedInstance=' . $instanceId;
                    else if($action == ELTALOG_NEXT_CREATE_INSTANCE)
                        $url = 'new-instance.php?savedInstance=' . $instanceId
                            . '&item=' . $this->itemId;
                    else
                        $url = 'item.php?item=' . $this->itemId;
                }

                HttpHeader::forwardTo($url);
            }
            catch(FormInputsException $ex)
            {
                $this->errors = $ex->getErrors();
                $this->values = $ex->getValues();

                $this->printItemForm();
            }
        }
        else
        {
            $this->get($args);
        }
    }

    public function get($args)
    {
        $this->printItemForm();
    }


    function printItemForm()
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $this->onLoad = "javascript: init(); ";
        $this->displayJavascript = 2;

        $category = $this->category;

        $fields = Field::getByCategoryId($this->categoryId);
        $types = Type::getByCategoryId($this->categoryId);
        $managers = Manager::getByCategoryId($this->categoryId);

        $this->printHeader();

        if($this->input->set('savedItem') || $this->input->set('savedInstance'))
        {
            ?>
             <div class="info-message">
            <?php
            if($this->input->set('savedItem'))
            {
                echo 'The item has been created.';
            }
            else
            {
                echo 'The instance has been created.';
            }
            ?>
             </div>
            <?php
        }

        ?>
         <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="form" class="form-default">
          <div class="item">
           <ul>
        <?php

        $itemValues = array();
        $query = new Query('SELECT itemVal.*, value.* FROM ' . $db->getTable('ItemValue')
            . ' AS itemVal INNER JOIN ' . $db->getTable('TypeValue')
            . ' AS value ON itemVal.valueId=value.valueId'
            . ' INNER JOIN ' . $db->getTable('Category_Field') . ' AS cat_field '
            . ' ON itemVal.fieldId=cat_field.fieldId'
            /*. ' INNER JOIN ' . $db->getTable('Field') . ' AS field '
            . ' ON itemVal.fieldId=field.fieldId'*/
            //. ' WHERE (cat_field.systemFlags & ' . ELTALOG_DISPLAY_SUMMARY . ')=' . ELTALOG_DISPLAY_SUMMARY
            . ' WHERE itemVal.itemId=' . $this->itemId
            . ' GROUP BY value.valueId'
            . ' ORDER BY cat_field.orderIndex ASC, value ASC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $itemValues[$row['valueId']] = $row;
        }

            ?>
             <li>
              <dl>
            <?php
            $fieldOpen = false;
            $lastField = '';
            foreach($itemValues as $value)
            {
                $value['value'] = str_replace(' ', '&nbsp;', $value['value']);
                $field = $fields[$value['fieldId']];

                if($fieldOpen)
                {
                    if($lastField != $field->getKeyname())
                    {
                        ?>
                         </dd>
                         <dt>
                          <?php echo $field->getTitle(); ?>
                         </dt>
                        <?php
                        echo '<dd>' . $value['value'];
                    }
                    else
                    {
                        echo ', ' . $value['value'];
                    }
                }
                else
                {
                    ?>
                     <dt>
                      <?php echo $field->getTitle(); ?>
                     </dt>
                    <?php
                    echo '<dd>' . $value['value'];

                    $fieldOpen = true;
                }

                $lastField = $field->getKeyname();
            }

            if($fieldOpen)
                echo '</dd>';
            ?>
              </dl>
             </li>
            <?php

        ?>
          </ul>
         </div>
          <fieldset>
           <legend>
            <!--<input type="checkbox" name="system_instance" id="system_instance" />-->
            <label for="system_instance"><?php echo $category['instanceTitle']; ?> Information</label>
           </legend>
           <div class="field">
            <div id="locations">
        <?php
        /*
             <input type="text" name="dummy-location" id="dummy-location" value="Select a Location   V" readonly="readonly" style="border: 0; " />
             <div id="location-0" class="location-list">

             </div>*/
        #$this->printLocationDivs($this->locationId);
        $this->printLocations($this->locationId);
        ?>
            </div>
            <label>
             <em>Location</em>
            </label>
           </div>
        <?php
        foreach($fields as $field)
        {
            if($field->getKind() == ELTALOG_KIND_INSTANCE)
            {
                $keyname = $field->getKeyname();
                ?>
                 <div class="field">
                <?php
                if(array_key_exists($keyname, $this->values)
                    && count($this->values[$keyname]) > 0)
                    $values = $this->values[$keyname];
                else
                    $values = array('');

                $field->printFormFields($values);
                ?>
                  <label>
                   <em><?php echo $field->getTitle(); ?></em>
                   <?php echo $field->getDescription(); ?>
                <?php
                if(array_key_exists($keyname, $this->errors))
                {
                    ?>
                     <span class="field-error">
                    <?php
                    foreach($this->errors[$keyname] as $error)
                    {
                        echo $error;
                    }
                    ?>
                     </span>
                    <?php
                }
                ?>
                  </label>
                 </div>
                <?php
            }
        }
        ?>
          </fieldset>
          <fieldset>
           <div class="field">
            <select name="systemNextAction">
        <?php
        $nextActions = array
        (
            ELTALOG_NEXT_VIEW => 'View the ' . $category['itemTitle'],
            ELTALOG_NEXT_CREATE_ITEM => 'Create an item',
            ELTALOG_NEXT_CREATE_INSTANCE => 'Create another ' . $category['instanceTitle']
        );

        $i = 1;
        foreach($nextActions as $id => $description)
        {
            $selected = '';
            if($id == $this->nextAction)
                $selected = ' selected="selected"';

            echo '<option value="' . $id . '"' . $selected . '>' . $i . ' - ' . $description . '</option>';

            $i++;
        }
        ?>
            </select>
            <label>
             <em>What next?</em>
            </label>
           </div>
          </fieldset>
          <fieldset class="submit">
           <input type="submit" name="submit_save" value="Save" accesskey="s" />
           <input type="submit" name="submit_cancel" value="Cancel" />
           <input type="hidden" name="item" value="<?php echo $this->itemId; ?>" />
          </fieldset>
         </form>
        <?php

        $this->printFooter();
    }


    function printLocations($value)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $locations = array();
        $locationsByParent = array();
        $query = new Query('SELECT parentId, locationId, description, code FROM '
            . $db->getTable('Location') . ' ORDER BY IF(code=\'\', description, code) ASC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $locations[$row['locationId']] = $row;
            $locationsByParent[$row['parentId']][] = $row;
        }

        $this->printLocationGroup($value, $locations, $locationsByParent);
    }

    function printLocationGroup($value, $locations, $locationsByParent, $selectedValue = 0)
    {
        if(array_key_exists($value, $locations))
        {
            $parent = $locations[$value]['parentId'];
            $this->printLocationGroup($parent, $locations, $locationsByParent, $value);
        }

        ?>
         <select name="location[]" id="location-<?php echo $value; ?>">
          <option value="0"></option>
          <option value="-1">New Location</option>
        <?php
        if(array_key_exists($value, $locationsByParent))
        {
            foreach($locationsByParent[$value] as $location)
            {
                $selected = '';
                if($location['locationId'] == $selectedValue)
                    $selected = ' selected="selected"';

                ?>
                 <option value="<?php echo $location['locationId']; ?>"<?php echo $selected; ?>>
                <?php
                if(trim($location['code']) != '')
                    echo htmlentities($location['code'] . ' - ' . $location['description']);
                else
                    echo htmlentities($location['description']);
                ?>
                 </option>
                <?php
            }
        }
        ?>
         </select>
        <?php
    }

    function printLocationDivs($value)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $locations = array();
        $locationsByParent = array();
        $query = new Query('SELECT parentId, locationId, description, code FROM '
            . $db->getTable('Location') . ' ORDER BY IF(code=\'\', description, code) ASC');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $locations[$row['locationId']] = $row;
            $locationsByParent[$row['parentId']][] = $row;
        }

        $this->printLocationDiv(0, $locationsByParent, $value);
    }

    function printLocationDiv($value, $locations, $selectedValue = 0)
    {
        ?>
         <div id="location-<?php echo $value; ?>">
          <ul>
        <?php
        foreach($locations[$value] as $location)
        {
            ?>
             <li>
              <a href="#"><?php echo $code; ?></a>
             </li>
            <?php
        }
        ?>
          </ul>
         </div>
        <?php
    }
}



?>
