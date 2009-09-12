<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogResponder.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Field.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Type.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Manager.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class NewLocationResponder extends EltalogResponder
{
    protected $searchSuggestIds;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        //$this->onLoad = "javascript: createSearch('crew-0', 'value-div-crew-0', 'irr'); ";
       // $this->onLoad = "javascript: createSearchSuggests(); ";

        $this->input->set('parent', IntegerInput::getInstance());
        $this->input->set('code');
        $this->input->set('description');

        $this->searchSuggestIds = array();
    }

    public function post($args)
    {
        header('Content-type: text/xml');
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $parent = $this->input->get('parent');
        $code = $this->input->get('code');
        $description = trim($this->input->get('description'));

        echo '<?xml version="1.0" ?>';
        ?>
         <new-location>
        <?php
        if($parent >= 0 && $description != '')
        {
            $query = new Query('INSERT INTO ' . $db->getTable('Location')
                . ' (`parentId`, `description`, `code`) VALUES '
                . ' (' . $parent . ',\'' . addslashes($description)
                . '\',\'' . addslashes($code) . '\')');
            $result = $db->execQuery($query);
            $id = $db->getAutoIncrementId();
            if($id > 0)
            {
                ?>
                 <id><?php echo $id; ?></id>
                <?php
            }
        }
        ?>
         </new-location>
        <?php
    }

    public function get($args)
    {
        $this->post($args);
    }
}



?>
