<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogResponder.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Field.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Type.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Manager.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class SmartTextSearchResponder extends EltalogResponder
{
    protected $searchSuggestIds;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        //$this->onLoad = "javascript: createSearch('crew-0', 'value-div-crew-0', 'irr'); ";
       // $this->onLoad = "javascript: createSearchSuggests(); ";

        $this->input->set('fieldId', IntegerInput::getInstance());
        $this->input->set('categoryId', IntegerInput::getInstance());
        $this->input->set('maxCount', IntegerInput::getInstance());
        $this->input->set('value');

        $this->searchSuggestIds = array();
    }

    public function post($args)
    {
        header('Content-type: text/xml');
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $fieldId = $this->input->get('fieldId');
        $categoryId = $this->input->get('categoryId');
        $maxCount = $this->input->get('maxCount');
        $value = $this->input->get('value');

        echo '<?xml version="1.0" ?>';
        $field = Field::getByFieldId($fieldId, $categoryId);
        ?>
         <smart-search>
          <results>
        <?php
        if(!is_null($field))
        {
            $values = $field->getValues(0, $maxCount, 0, ' AND SUBSTRING(val.value, 1, '
                . strlen($value) . ')=\'' . addslashes($value) . '\'');
            foreach($values as $value)
            {
                echo '<item>' . htmlentities($value) . "</item>\n";
            }
        }
        ?>
          </results>
         </smart-search>
        <?php
    }

    public function get($args)
    {
        $this->post($args);
    }
}



?>
