<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogResponder.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Field.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Type.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Manager.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class LocationResponder extends EltalogResponder
{
    protected $searchSuggestIds;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        //$this->onLoad = "javascript: createSearch('crew-0', 'value-div-crew-0', 'irr'); ";
       // $this->onLoad = "javascript: createSearchSuggests(); ";

        $this->input->set('parent', IntegerInput::getInstance());

        $this->searchSuggestIds = array();
    }

    public function post($args)
    {
        header('Content-type: text/xml');
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $parent = $this->input->get('parent');

        echo '<?xml version="1.0" ?>';
        ?>
         <locations>
          <results parent-id="<?php echo $parent; ?>">
        <?php
        if($parent >= 0)
        {
            $query = new Query('SELECT locationId, description, code FROM ' . $db->getTable('Location')
                . ' WHERE parentId=' . $parent . ' ORDER BY IF(code=\'\', description, code) ASC');
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                echo "<location>\n";
                echo ' <id>' . $row['locationId'] . "</id>\n";
                echo ' <code>' . htmlentities($row['code']) . "</code>\n";
                echo ' <description>' . htmlentities($row['description']) . "</description>\n";
                echo "</location>\n";
            }
        }
        ?>
          </results>
         </locations>
        <?php
    }

    public function get($args)
    {
        $this->post($args);
    }
}



?>
