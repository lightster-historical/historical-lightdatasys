<?php


require_once 'path.php';


require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogConstants.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/EltalogResponder.php';
require_once PATH_LIB . 'com/lightdatasys/eltalog/Field.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class ItemResponder extends EltalogResponder
{
    protected $item;
    protected $itemId;

    protected $locations;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        $db = Database::getConnection('com.lightdatasys.eltalog');

        $this->input->set('item', IntegerInput::getInstance());
        $this->itemId = $this->input->get('item');
        $this->item = null;

        if($this->itemId > 0)
        {
            $query = new Query('SELECT item.*, value.value AS title FROM '
                . $db->getTable('Item') . ' AS item INNER JOIN '
                . $db->getTable('TypeValue') . ' AS value'
                . ' ON item.titleId=value.valueId'
                . ' WHERE itemId=' . $this->itemId);
            $result = $db->execQuery($query);
            if($row = $db->getAssoc($result))
                $this->item = $row;
            else
                $this->itemId = 0;
        }

        if($this->itemId <= 0)
        {
            HttpHeader::forwardTo('index.php');
        }

        $this->locations = null;
    }


    public function getPageTitle()
    {
        if($this->itemId > 0)
            return $this->item['title'];
        else
            return parent::getPageTitle();
    }


    public function get($args)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $this->printHeader();

        $fields = Field::getByCategoryId($this->item['categoryId']);
        $types = Type::getByCategoryId($this->item['categoryId']);
        $managers = Manager::getByCategoryId($this->item['categoryId']);

        $query = new Query('SELECT * FROM ' . $db->getTable('ItemCategory')
            . ' WHERE categoryId=' . intval($this->item['categoryId']));
        $result = $db->execQuery($query);
        $category = $db->getAssoc($result);

        $instances = array();
        $instanceIds = array();
        $query = new Query('SELECT inst.* FROM ' . $db->getTable('Instance')
            . ' AS inst LEFT JOIN ' . $db->getTable('InstanceValue')
            . ' AS instVal ON inst.instanceId=instVal.instanceId '
            . ' AND instVal.fieldId=' . intval($category['instOrderFieldId'])
            . ' LEFT JOIN ' . $db->getTable('TypeValue') . ' AS value'
            . ' ON instVal.valueId=value.valueId'
            . ' WHERE itemId=' . $this->itemId . ' GROUP BY inst.instanceId'
            . ' ORDER BY inst.locationId, CAST(value.value+(-1) AS UNSIGNED), value.value');
        $result = $db->execQuery($query);
        while($row = $db->getAssoc($result))
        {
            $instances[$row['instanceId']] = $row;
            $instanceIds[] = intval($row['instanceId']);
        }

        $instValues = array();
        if(count($instanceIds) > 0)
        {
            $query = new Query('SELECT instVal.*, value.* FROM ' . $db->getTable('InstanceValue')
                . ' AS instVal INNER JOIN ' . $db->getTable('TypeValue')
                . ' AS value ON instVal.valueId=value.valueId'
                . ' INNER JOIN ' . $db->getTable('Category_Field')
                . ' AS cat_field ON instVal.fieldId=cat_field.fieldId'
                . ' AND cat_field.categoryId=' . intval($category['categoryId'])
                . ' WHERE instVal.instanceId IN (' . implode(',', $instanceIds) . ')'
                . ' ORDER BY cat_field.orderIndex ASC, value ASC');
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                $instValues[$row['instanceId']][$row['valueId']] = $row;
            }
        }

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
         <a href="edit-item.php?item=<?php echo $this->itemId; ?>">Edit Item</a>
         <a href="new-instance.php?item=<?php echo $this->itemId; ?>">New Instance</a>
         <div class="item">
          <ul>
        <?php

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
                $manager = $field->getManager()->getInstance();

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
                        echo '<dd>' . $manager->formatValue($value['value'], $field);
                    }
                    else
                    {
                        echo ', ' . $manager->formatValue($value['value'], $field);
                    }
                }
                else
                {
                    ?>
                     <dt>
                      <?php echo $field->getTitle(); ?>
                     </dt>
                    <?php
                    echo '<dd>' . $manager->formatValue($value['value'], $field);

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
        <?php
        if(count($instances) > 0)
        {
            ?>
              <div class="instance-summary">
               <h3><?php echo $category['instanceTitle']; ?> List</h3>
               <ul>
            <?php

            $lastLocationId = null;
            foreach($instances as $instance)
            {
                if(is_null($lastLocationId) || $lastLocationId != $instance['locationId'])
                {
                    if(!is_null($lastLocationId))
                    {
                        ?>
                         </li>
                        <?php
                    }

                    ?>
                     <li>
                      <div class="location">
                       <?php $this->printLocation($instance['locationId']); ?>
                       <span class="code">
                        [<?php $this->printLocationCode($instance['locationId']); ?>]
                       </span>
                      </div>
                    <?php

                    $lastLocationId = $instance['locationId'];
                }
                else
                {
                    echo '<hr />';
                }
                ?>
                  <dl>
                <?php
                $fieldOpen = false;
                $lastField = '';
                foreach($instValues[$instance['instanceId']] as $value)
                {
                    $value['value'] = str_replace(' ', '&nbsp;', $value['value']);
                    $field = $fields[$value['fieldId']];
                    $manager = $field->getManager()->getInstance();

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
                            echo '<dd>' . $manager->formatValue($value['value'], $field);
                        }
                        else
                        {
                            echo ', ' . $manager->formatValue($value['value'], $field);
                        }
                    }
                    else
                    {
                        ?>
                         <dt>
                          <?php echo $field->getTitle(); ?>
                         </dt>
                        <?php
                        echo '<dd>' . $manager->formatValue($value['value'], $field);

                        $fieldOpen = true;
                    }

                    $lastField = $field->getKeyname();
                }

                if($fieldOpen)
                    echo '</dd>';
                ?>
                   <dt>&nbsp;</dt>
                   <dd>
                    <a href="edit-instance.php?instance=<?php echo $instance['instanceId']; ?>">Edit</a>
                    | <a href="delete-instance.php?instance=<?php echo $instance['instanceId']; ?>">Delete</a>
                   </dd>
                  </dl>
                <?php
            }

            if(!is_null($lastLocationId))
            {
                ?>
                 </li>
                <?php
            }
            ?>
               </ul>
              </div>
            <?php
        }
        ?>
         </div>
         <br style="clear: both; " />
        <?php

        $this->printFooter();
    }



    public function printLocation($locationId)
    {
        $location = $this->getLocation($locationId);

        if($location['parentId'] != 0)
        {
            $this->printLocation($location['parentId']);
            echo ' : ';
        }

        echo $location['description'];
    }

    public function printLocationCode($locationId)
    {
        $location = $this->getLocation($locationId);

        if($location['parentId'] != 0)
        {
            $this->printLocationCode($location['parentId']);
            if(trim($location['code']) != '')
                echo '-';
        }

        echo $location['code'];
    }

    public function getLocation($locationId)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        if(is_null($this->locations))
        {
            $query = new Query('SELECT parentId, locationId, description, code FROM '
                . $db->getTable('Location') . ' ORDER BY IF(code=\'\', description, code) ASC');
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                $this->locations[$row['locationId']] = $row;
            }
        }

        return $this->locations[$locationId];
    }
}



?>
