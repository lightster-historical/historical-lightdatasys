<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogResponder.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Field.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Manager.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Search.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class SearchResponder extends EltalogResponder implements Help
{
    public function getPageTitle()
    {
        $keywords = urldecode($this->input->get('keywords'));

        return 'Search Results';
    }

    public function post($args)
    {
        $this->get($args);
    }

    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $keywords = urldecode($this->input->get('keywords'));

        $this->input->set('page', IntegerInput::getInstance());
        $this->currentPage = $this->input->get('page');
        if($this->currentPage <= 0)
            $this->currentPage = 1;

        $search = Search::createSearch($keywords);
        $results = $search->getResults($this->currentPage, $this->itemsPerPage);
        $resultCount = $search->getResultCount();
        $itemIds = array_keys($results);

        $this->itemCount = $resultCount;
        $this->pageCount = ceil($this->itemCount / $this->itemsPerPage);

        $fields = array();
        $query = new Query('SELECT field.* FROM ' . $db->getTable('Field')
            . ' AS field');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $fields[$row['fieldId']] = $row;
        }

        if(count($itemIds) > 0)
        {
            $itemValues = array();
            $query = new Query('SELECT itemVal.*, value.* FROM ' . $db->getTable('ItemValue')
                . ' AS itemVal INNER JOIN ' . $db->getTable('TypeValue')
                . ' AS value ON itemVal.valueId=value.valueId'
                . ' INNER JOIN ' . $db->getTable('Category_Field') . ' AS cat_field '
                . ' ON itemVal.fieldId=cat_field.fieldId AND'
                . ' (cat_field.systemFlags & ' . ELTALOG_DISPLAY_SUMMARY . ')=' . ELTALOG_DISPLAY_SUMMARY
                /*. ' INNER JOIN ' . $db->getTable('Field') . ' AS field '
                . ' ON itemVal.fieldId=field.fieldId'*/
                . ' WHERE itemVal.itemId IN (' . implode(',', $itemIds) . ')'
                . ' GROUP BY itemVal.itemId, value.valueId'
                . ' ORDER BY cat_field.orderIndex ASC, value ASC');
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                $itemValues[$row['itemId']][$row['valueId']] = $row;
            }
        }

        $this->showSearch = true;
        $this->printHeader();

        ?>
         <div class="item-summary">
          <ul>
        <?php

        foreach($results as $result)
        {
            $item = $result->getItem();

            ?>
             <li>
              <h3><a href="item.php?item=<?php echo $item->getId(); ?>"><?php echo $item->getTitle(); ?></a> [<?php echo number_format($result->getRank() * 100 / $search->getTotalRank()); ?>%]</h3>
              <dl>
            <?php
            $fieldOpen = false;
            $lastField = '';
            foreach($itemValues[$item->getId()] as $value)
            {
                $field = $fields[$value['fieldId']];

                if($fieldOpen)
                {
                    if($lastField != $field['keyname'])
                    {
                        ?>
                         </dd>
                         <dt>
                          <?php echo $field['title']; ?>
                         </dt>
                         <dd>
                        <?php
                        $this->printFieldKeywordLink($field['keyname'], $value['value']);
                    }
                    else
                    {
                        echo ', ';
                        $this->printFieldKeywordLink($field['keyname'], $value['value']);
                    }
                }
                else
                {
                    ?>
                     <dt>
                      <?php echo $field['title']; ?>
                     </dt>
                     <dd>
                    <?php
                    $this->printFieldKeywordLink($field['keyname'], $value['value']);

                    $fieldOpen = true;
                }

                $lastField = $field['keyname'];
            }

            if($fieldOpen)
                echo '</dd>';
            ?>
              </dl>
             </li>
            <?php
        }

        ?>
          </ul>
         </div>
        <?php
        $this->printPageNumberInfo();

        $this->printFooter();
    }
}



?>
