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
require_once PATH_LIB . 'com/mephex/input/FormInputsException.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class EditItemResponder extends EltalogResponder
{
    protected $categoryId;
    protected $category;

    protected $title;

    protected $nextAction;

    protected $displayJavascript;

    protected $values;

    protected $errors;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        $db = Database::getConnection('com.lightdatasys.eltalog');

        $this->displayJavascript = 0;

        $this->values = array();
        $this->errors = array();

        $this->input->set('item', IntegerInput::getInstance());
        $this->itemId = max(0, $this->input->get('item'));
        $this->item = null;

        $this->categoryId = 0;
        $this->category = null;

        $this->input->set('title');
        $this->title = trim($this->input->get('title'));

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
                if(empty($this->title))
                    $this->title = $row['title'];

                $query = new Query('SELECT field.keyname, value.valueId, value.value FROM '
                    . $db->getTable('ItemValue')
                    . ' AS itemVal INNER JOIN ' . $db->getTable('TypeValue')
                    . ' AS value ON itemVal.valueId=value.valueId'
                    . ' INNER JOIN ' . $db->getTable('Field')
                    . ' AS field ON itemVal.fieldId=field.fieldId'
                    . ' WHERE itemVal.itemId=' . intval($this->itemId));
                $result = $db->execQuery($query);
                while($row = $db->getAssoc($result))
                {
                    $this->values[$row['keyname']][] = array($row['valueId'], $row['value']);
                }

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

                  focusForm('form');
              }

              function fieldInits()
              {
                  document.getElementById('form').onsubmit = checkSubmit;
            <?php
            foreach($fields as $field)
            {
                if($field->getKind() == ELTALOG_KIND_ITEM)
                    $field->printJavascriptInits();
            }
            ?>
              }
              //-->
             </script>
            <?php
        }
    }


    public function post($args)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        if($this->title == '')
            $this->errors['title'][] = 'A title is required.';

        if($this->input->set('submit_cancel'))
        {
            HttpHeader::forwardTo('item.php?item=' . $this->itemId);
        }
        else if(count($this->errors) <= 0)
        {
            if($this->input->set('submit_save'))
            {
                try
                {
                    $item = Item::getByItemId($this->itemId);
                    $item->update($this->title, $_POST);

                    $url = 'index.php';
                    if($this->itemId > 0)
                    {
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

        ?>
         <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="form" class="form-default">
          <div id="search-count"></div>
          <fieldset>
           <legend><?php echo $category['itemTitle']; ?></legend>
           <div class="field">
            <div class="hidden">
             <input type="text" name="title" value="<?php echo htmlentities($this->title); ?>" />
            </div>
            <label>
             <em>Title</em>
            </label>
           </div>
        <?php
        foreach($fields as $field)
        {
            if($field->getKind() == ELTALOG_KIND_ITEM)
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
          <fieldset class="submit">
           <div class="field">
            <input type="submit" name="submit_save" value="Save" accesskey="s" />
            <input type="submit" name="submit_cancel" value="Cancel" />
            <input type="hidden" name="item" value="<?php echo $this->itemId; ?>" />
           </div>
          </fieldset>
         </form>
        <?php

        $this->printFooter();
    }
}



?>
