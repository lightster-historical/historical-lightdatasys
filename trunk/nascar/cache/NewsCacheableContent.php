<?php


require_once PATH_LIB . 'com/lightdatasys/nascar/NascarResponder.php';

require_once PATH_LIB . 'com/mephex/aggregator/AggDatabaseItem.php';
require_once PATH_LIB . 'com/mephex/cache/CacheableContent.php';
require_once PATH_LIB . 'com/mephex/core/Date.php';


class LDS_NewsCacheableContent implements MXT_CacheableContent
{
    protected $responder;
    protected $series;


    public function __construct(NascarResponder $responder, $series)
    {
        $this->responder = $responder;
        $this->series = $series;
    }


    public function getResponder()
    {
        return $this->responder;
    }

    public function getSeriesArray()
    {
        return $this->series;
    }


    public function getContent()
    {
        $series = $this->getSeriesArray();

        $this->printNews($series['feedName']);
    }

    public function getContentLastUpdated()
    {
        //$series = $this->getSeriesArray();
        //return $series['lastUpdatedNews'];
        return new Date();
    }

    public function getDirectory()
    {
        $series = $this->getSeriesArray();
        return 'nascar/index.php/';
    }

    public function getFileName()
    {
        $series = $this->getSeriesArray();
        return $series['keyname']. '_news.txt';
    }


    public function printNews($feedName)
    {
        $responder = $this->getResponder();

        $date = new Date();

        $newsDate = new Date($date);
        $newsDate->changeDay(2);
        $items = AggDatabaseItem::getItems($feedName, 5, 0, new DateRange(null, null));

        if(count($items) > 0)
        {
            ?>
             <div id="nascar-news">
              <h4>News</h4>
              <div style="font-size: .9em; border: 1px solid #666666; padding: 0; ">
               <ul style="list-style-type: none; margin: 5px; padding: 0; ">
            <?php
            foreach($items as $item)
            {
                ?>
                 <li style="margin: 5px 0; padding: 0; ">
                  <a href="<?php echo $item->getLink(); ?>" target="_blank"><?php echo $item->getTitle(); ?></a><br />
                  <?php echo $responder->getDate($item->getPublishDate(), null, true); ?>
                 </li>
                <?php
            }
            ?>
               </ul>
               <div style="margin: 5px 0 0 0; padding: 5px; background: #bbccdd; ">
                <!--<a href="">More news</a><br />-->
                (provided by <a href="http://nascar.com">NASCAR.com</a>)
               </div>
              </div>
             </div>
            <?php
        }
    }
}



?>
