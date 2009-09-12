<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogResponder.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Field.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class IndexResponder extends EltalogResponder
{
    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $this->showSearch = true;

        $this->input->set('page', IntegerInput::getInstance());
        $this->currentPage = $this->input->get('page');
        if($this->currentPage <= 0)
            $this->currentPage = 1;

        $this->itemCount = 0;
        $this->pageCount = 0;
        $query = new Query('SELECT COUNT(itemId) FROM ' . $db->getTable('Item'));
        $result = $db->execQuery($query);
        if($row = $db->getRow($result))
        {
            $this->itemCount = $row[0];
            $this->pageCount = ceil($this->itemCount / $this->itemsPerPage);
        }

        $items = array();
        $itemIds = array();
        $query = new Query('SELECT item.*, value.value AS title FROM '
            . $db->getTable('Item') . ' AS item INNER JOIN '
            . $db->getTable('TypeValue') . ' AS value'
            . ' ON item.titleId=value.valueId '
            . ' ORDER BY IF(SUBSTRING(title, 1, 4)=\'The \', SUBSTRING(title, 5), IF(SUBSTRING(title, 1, 2)=\'A \', SUBSTRING(title, 3), title)) ASC'
            . ' LIMIT ' . intval(($this->currentPage - 1) * $this->itemsPerPage) . ',' . intval($this->itemsPerPage));
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $items[$row['itemId']] = $row;
            $itemIds[] = intval($row['itemId']);
        }
        /*
        $categories = array();
        $query = new Query('SELECT * FROM ' . $db->getTable('ItemCategory')
            . ' AS cat WHERE cat.categoryId IN (SELECT item.categoryId FROM '
            . $db->getTable('Item') . ' AS item)');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $categories[$row['categoryId']] = $row;
        }
        */

        $fields = array();
        $query = new Query('SELECT field.* FROM ' . $db->getTable('Field')
            . ' AS field');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $fields[$row['fieldId']] = $row;
        }

        /*
        $instValues = array();
        $query = new Query('SELECT instVal.*, value.* FROM ' . $db->getTable('InstanceValue')
            . ' AS instVal INNER JOIN ' . $db->getTable('TypeValue')
            . ' AS value ON instVal.valueId=value.valueId');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $row['field'] = &$fields[$row['fieldId']];
            $instValues[$row['instanceId']][$row['valueId']] = $row;
        }*/

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

        $this->printHeader();

        ?>
         <div class="item-summary">
          <ul>
        <?php

        foreach($items as $row)
        {
            $row['itemValues'] = &$itemValues[$row['itemId']];
            $row['instances'] = array();
            $items[$row['itemId']] = $row;

            ?>
             <li>
              <h4><a href="item.php?item=<?php echo $row['itemId']; ?>"><?php echo $row['title']; ?></a></h4>
            <?php
            if(is_array($row['itemValues']))
            {
                ?>
                  <dl>
                <?php
                $fieldOpen = false;
                $lastField = '';
                foreach($row['itemValues'] as $value)
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
                <?php
            }
            ?>
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
