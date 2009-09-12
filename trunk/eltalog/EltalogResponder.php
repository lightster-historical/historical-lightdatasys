<?php


require_once PATH_LIB . 'com/lightdatasys/LightDataSysResponder.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';
require_once PATH_LIB . 'com/mephex/input/IntegerInput.php';


class EltalogResponder extends LightDataSysResponder
{
    protected $showSearch;

    protected $itemsPerPage;
    protected $itemCount;
    protected $pageCount;
    protected $currentPage;


    public function init($args, $cacheDir = null)
    {
        parent::init($args, $cacheDir);

        $this->showSearch = false;

        $db = Database::getConnection('com.lightdatasys');
        $conn = Database::setHash($db, 'com.lightdatasys.eltalog');
        $conn->setTablePrefix('eltalog');

        //$newItemNav = Navigation::getFromKeys($this->getNavigation(), array('eltalog', 'new-item.php'));
        //new NavItem(0, 'new-item.php?blah', 'Blah', $newItemNav, $_SERVER['PHP_SELF'] . '?blah');

        //NascarPermissions::getInstance();

        $this->input->set('keywords');

        $this->itemsPerPage = 10;
        $this->itemCount = 0;
        $this->pageCount = 0;
        $this->currentPage = 0;
    }


    public function checkPermissions()
    {
        parent::checkPermissions();
        $this->checkPermission('com.lightdatasys.eltalog', 'read');
    }

    public function printExtendedHTMLHead()
    {
        parent::printExtendedHTMLHead();

        ?>
         <link rel="stylesheet" href="/eltalog/style.css" />
        <?php
    }


    public function printHeader()
    {
        parent::printHeader();
    }

    public function printSelector()
    {
        if($this->showSearch)
        {
            ?>
             <form action="search.php" method="get" class="selector">
              <dl>
               <dt>Search</dt>
               <dd>
                <input type="text" name="keywords" class="search-box" value="<?php echo htmlentities($this->input->get('keywords')); ?>" />
               </dd>
              </dl>
              <dl>
               <dd>&nbsp;</dd>
               <dt><input type="submit" value="Go" /></dt>
              </dl>
              <br style="clear: both; " />
            <?php
            if($this->pageCount > 0)
                echo '<hr />';
            ?>
              <?php $this->printPageNumberInfo(false); ?>
             </form>
            <?php
        }
    }

    public function printPageNumberInfo($printBorder = true)
    {
        if($this->pageCount > 0)
        {
            if($printBorder)
                echo '<div class="result-info-border">';

            ?>
             <div class="result-info-container">
              <span class="page-numbers">
            <?php
            for($i = 1; $i <= $this->pageCount; $i++)
            {
                ?>
                 <a href="?page=<?php echo $i; ?>&amp;keywords=<?php echo htmlentities(urlencode($this->input->get('keywords'))); ?>"><?php echo $i; ?></a>
                <?php
            }
            ?>
              </span>
              <span class="result-info">
               Displaying results
               <?php echo ($this->currentPage - 1) * $this->itemsPerPage + 1; ?>
               to
               <?php echo $this->currentPage * $this->itemsPerPage; ?>
               of
               <?php echo $this->itemCount; ?>
            <?php
            if($this->input->get('keywords') != '')
            {
                echo ' for keyword(s) <em>' . htmlentities($this->input->get('keywords')) . '</em>';
            }
            ?>
              </span>
              <br class="clear" />
             </div>
            <?php

            if($printBorder)
                echo '</div>';
        }
    }


    public function printFieldKeywordLink($fieldName, $value)
    {
        $printVal = str_replace(' ', '&nbsp;', $value);
        echo '<a href="search.php?keywords=' . urlencode($fieldName . '("' . $value . '") "' . $value . '"') . '">' . $printVal . '</a>';
    }
}



?>
