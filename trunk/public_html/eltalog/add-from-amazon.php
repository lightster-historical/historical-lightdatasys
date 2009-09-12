<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogResponder.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Field.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Item.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/ItemInstance.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Search.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Type.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Manager.php';
require_once PATH_LIB . 'com/mephex/core/HttpHeader.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/input/FormInputsException.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class AddFromAmazonResponder extends EltalogResponder
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

        //$this->onLoad = "javascript: createSearch('crew-0', 'value-div-crew-0', 'irr'); ";
        $this->displayJavascript = 0;

        $this->values = array();
        $this->instanceValueCount = 0;

        $this->errors = array();
        $this->itemErrorCount = 0;

        $this->input->set('category', IntegerInput::getInstance());
        $this->categoryId = max(0, $this->input->get('category'));
        $this->category = null;

        $this->input->set('title');
        $this->title = trim($this->input->get('title'));

        $this->input->set('systemNextAction', IntegerInput::getInstance());
        $this->nextAction = $this->input->get('systemNextAction');
        if($this->nextAction <= 0)
            $this->nextAction = ELTALOG_NEXT_CREATE_INSTANCE;

        if($this->categoryId > 0)
        {
            $query = new Query('SELECT * FROM ' . $db->getTable('ItemCategory')
                . ' WHERE categoryId=' . $this->categoryId);
            $result = $db->execQuery($query);
            if($category = $db->getAssoc($result))
            {
                $this->categoryId = $category['categoryId'];
                $this->category = $category;
            }
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

        if($this->categoryId <= 0)
            $this->errors['category'][] = 'An item type is required.';

        if($this->input->set('submit_cancel'))
        {
            HttpHeader::forwardTo('index.php');
        }
        else if(count($this->errors) <= 0)
        {
            if($this->input->set('submit_save'))
            {
                try
                {
                    $itemId = Item::create($this->categoryId, $this->title, $_POST);

                    $url = 'index.php';
                    if($itemId > 0)
                    {
                        $action = $this->input->get('systemNextAction');
                        if($action == ELTALOG_NEXT_CREATE_ITEM)
                            $url = 'new-item.php?savedItem=' . $itemId
                                . '&category=' . $this->categoryId;
                        else if($action == ELTALOG_NEXT_CREATE_INSTANCE)
                            $url = 'new-instance.php?savedItem=' . $itemId
                                . '&item=' . $itemId;
                        else
                            $url = 'item.php?item=' . $itemId;
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
        $this->printBasicInfoForm();
    }


    function printBasicInfoForm()
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $this->onLoad = "javascript: init(); ";
        $this->displayJavascript = 1;

        $this->printHeader();

        $query = new Query('SELECT * FROM ' . $db->getTable('ItemCategory')
            . ' ORDER BY title ASC');
        $catResult = $db->execQuery($query);
        if($catRow = $db->getAssoc($catResult))
        {
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
              <div id="search-count"></div>
              <fieldset>
               <legend>Basic Information</legend>
               <div class="field">
            <?php
            if(array_key_exists('title', $this->values))
                $values = $this->values['title'];
            else
                $values = '';
            ?>
             <select name="category">
              <option value="0"></option>
            <?php
            do
            {
                $selected = '';
                if($catRow['categoryId'] == $this->categoryId)
                    $selected = ' selected="selected"';

                echo '<option value="' . $catRow['categoryId']. '"' . $selected . '>';
                echo htmlentities($catRow['title']);
                echo '</option>';
            }
            while($catRow = $db->getAssoc($catResult));
            ?>
                </select>
                <label>
                 <em>Type</em>
            <?php
            if(array_key_exists('category', $this->errors))
            {
                ?>
                 <span class="field-error">
                <?php
                foreach($this->errors['category'] as $error)
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
               <div class="field">
                <input type="text" name="title" value="<?php echo htmlentities($this->title); ?>" />
                <label>
                 <em>Title</em>
            <?php
            if(array_key_exists('title', $this->errors))
            {
                ?>
                 <span class="field-error">
                <?php
                foreach($this->errors['title'] as $error)
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
              </fieldset>
              <fieldset class="submit">
               <input type="submit" name="submit_next" value="Next &raquo;" accesskey="s" />
              </fieldset>
             </form>
            <?php
        }
        else
        {
            ?>
             <div class="error-message">
              No item categories exist. One or more categories must exist before creating an item.
             </div>
            <?php
        }

        $this->printFooter();
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


        $title = preg_replace('/\(|\)|\bthe\b|\ba\b|\ban\b|\bon\b|\band\b|\bof\b/i', '', $this->title);
        $search = Search::createSearch('title("' . $title
            . '" ' . $title . ') category(' . $this->categoryId . ')');
        $results = $search->getResults();
        $resultCount = count($results);
        if($resultCount > 0)
        {
            ?>
             <div class="tip-message">
              Did you mean
            <?php
            $isLast = false;
            $count = 0;
            $max = min(5, $resultCount);
            $separator = '';
            $qMark = '';
            foreach($results as $result)
            {
                $count++;
                if($count > $max)
                    break;

                $item = $result->getItem();
                $rank = $result->getRank();

                if($count == $max)
                {
                    if($count == 2)
                        $separator = ' or ';
                    else if($count > 2)
                        $separator = ', or ';

                    $isLast = true;
                    $qMark = '?';
                }

                echo $separator;
                echo '<a href="item.php?item=' . $item->getId() . '">';
                echo htmlentities($item->getTitle());
                echo '</a>';
                //echo ' [' . $rank . ']';
                echo $qMark;

                $separator = ', ';
            }
            ?>
             </div>
            <?php
        }
        ?>
         <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id="form" class="form-default">
          <div id="search-count"></div>
          <fieldset>
           <legend><?php echo $category['itemTitle']; ?></legend>
           <div class="field">
            <div class="hidden">
             <input type="hidden" name="title" value="<?php echo htmlentities($this->title); ?>" class="hidden" />
             <?php echo htmlentities($this->title); ?>
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
          <fieldset>
           <div class="field">
            <select name="systemNextAction">
        <?php
        $nextActions = array
        (
            ELTALOG_NEXT_VIEW => 'View the ' . $category['itemTitle'],
            ELTALOG_NEXT_CREATE_ITEM => 'Create another item',
            ELTALOG_NEXT_CREATE_INSTANCE => 'Create a ' . $category['instanceTitle']
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
           <div class="field">
            <input type="submit" name="submit_save" value="Save" accesskey="s" />
            <input type="submit" name="submit_cancel" value="Cancel" />
            <input type="hidden" name="category" value="<?php echo $this->categoryId; ?>" />
           </div>
          </fieldset>
         </form>
        <?php

        $this->printFooter();
    }
}



?>
