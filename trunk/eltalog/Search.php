<?php


require_once PATH_LIB . 'com/lightdatasys/eltalog/Item.php';
require_once PATH_LIB . 'com/mephex/db/Database.php';


class Search
{
    protected static $typeInstancesByTypeId = array();
    protected static $typeInstancesByCategoryId = array();

    // the auto-incremented unique id number as used in the database
    protected $id;

    protected $keywords;
    protected $parsed;
    protected $values;
    protected $categories;
    protected $results;
    protected $resultCount;
    protected $totalRank;


    protected function __constuct()
    {
        $this->id = -1;

        $this->keywords = '';
        $this->parsed = null;
        $this->values = null;
        $this->categories = null;
        $this->results = null;
        $this->resultCount = -1;
        $this->totalRank = -1;
    }


    public function getKeywords()
    {
        return $this->keywords;
    }

    public function getResults($currPage = 1, $itemsPerPage = 5)
    {
        if(is_null($this->results))
            $this->loadResults();

        $start = ($currPage - 1) * $itemsPerPage;
        $end = $start + $itemsPerPage - 1;

        $curr = 0;
        $results = array();
        foreach($this->results as $id => $result)
        {
            if($start <= $curr && $curr <= $end)
                $results[$id] = $result;

            $curr++;
        }

        return $results;
    }

    public function getResultCount()
    {
        if($this->resultCount < 0)
            $this->loadResults();

        return $this->resultCount;
    }

    public function getTotalRank()
    {
        if($this->totalRank < 0)
            $this->loadResults();

        return $this->totalRank;
    }

    public function loadResults()
    {
        $this->results = &self::getSearchResults($this->values, $this->categories);
        $this->resultCount = count($this->results);
        $this->totalRank = self::calcTotalRank($this->results);
    }



    public static function createSearch($keywords)
    {
        $search = new Search();
        $search->keywords = trim($keywords);
        $search->parsed = &self::parseKeywords($search->keywords);

        $search->categories = array();
        if(array_key_exists('category', $search->parsed))
        {
            $categoryQueries = $search->parsed['category'];
            if(count($categoryQueries) > 0)
            {
                foreach($categoryQueries as $query)
                {
                    $query = intval($query);
                    if($query > 0)
                        $search->categories[$query] = $query;
                }
            }
        }

        $search->values = &self::getValues($search->parsed, $search->categories);

        return $search;
    }

    public static function &parseKeywords($searchString)
    {
        /*
        SEARCH QUERY GRAMMAR
        queries = (\s*query)*
        query = keyword|function|quote
        keyword = [a-zA-Z0-9_\']+
        funcName = [a-zA-Z0-9_]+
        argument = keyword|quote
        arguments = (\s*argument)*
        function = funcName\(arguments\)
        quote = \"keyword\"
        */

        $subjects = preg_split('/\s+/', $searchString);

        $queries = array();

        $openQuote = -1;
        $openParen = -1;
        $fieldName = '';
        $query = '';
        while(count($subjects) > 0 || $openQuote >= 0 || $openParen >= 0)
        {
            // check to see if any subjects remain
            $endReached = !(count($subjects) > 0);

            // subjects have no white-space, however...
            // some example subjects contain spaces, so the actual subject being
            // considered is within asterisks
            //::    abc *xyz* 123
            //----  xyz is the search string

            // if the end has been reached, do not retrieve a subject (it'll error!)
            if(!$endReached)
                $subject = array_shift($subjects);

            // if a set of double quotes is still open
            if($openQuote != -1)
            {
                if($endReached)
                {
                    $queries[$fieldName][] = trim($query);

                    $openQuote = -1;
                    $query = '';
                }
                else
                {
                    $closeQuote = strpos($subject, '"');
                    if($closeQuote === false)
                        $closeQuote = -1;

                    // if a double quote was found
                    if($closeQuote >= 0)
                    {
                        // if the closing double quote is not the first char
                        if($closeQuote > 0)
                        {
                            $query .= ' ' . substr($subject, 0, $closeQuote);

                            if(strlen($subject) > $closeQuote)
                                array_unshift($subjects, substr($subject, $closeQuote));
                        }
                        else
                        {
                            // add the query to the query list
                            $queries[$fieldName][] = trim($query);

                            // add anything after the quote to the subjects
                            if(strlen($subject) > $closeQuote+1)
                                array_unshift($subjects, substr($subject, $closeQuote+1));

                            $openQuote = -1;
                            $query = '';
                        }
                    }
                    else
                    {
                        // the quote is still going... add the subject to
                        // the open query
                        $query .= ' ' . $subject;
                    }
                }
            }
            // if an open paren has been opened and no matching closing
            // bracket has been found
            else if($openParen != -1)
            {
                if($endReached)
                {
                    $openParen = -1;
                    $fieldName = '';
                }
                else
                {
                    $openQuote = strpos($subject, '"');
                    $closeParen = strpos($subject, ')');

                    // boolean false is returned if the needle is not found in
                    // the haystatck, but this is easily confused with 0
                    if($openQuote === false)
                        $openQuote = -1;
                    if($closeParen === false)
                        $closeParen = -1;

                    // if a close paren has been found before a double quote
                    if(($openQuote < 0 || $closeParen < $openQuote) && $closeParen >= 0)
                    {
                        if($closeParen > 0)
                        {
                            // add the query to the query list
                            $queries[$fieldName][] = substr($subject, 0, $closeParen);

                            if(strlen($subject) > $closeParen)
                                array_unshift($subjects, substr($subject, $closeParen));
                        }
                        else
                        {
                            // add anything after the closing paren to the subjects
                            if(strlen($subject) > $closeParen+1)
                                array_unshift($subjects, substr($subject, $closeParen+1));

                            $openParen = -1;
                            $fieldName = '';
                        }
                    }
                    else if(($closeParen < 0 || $openQuote < $closeParen) && $openQuote >= 0)
                    {
                        // if a double quote is found after a keyword, assume that
                        // a space was supposed to be entered before the double quote
                        //::    *abc"* xyz
                        //::    *abc"xyz*
                        //::    *abc")* xyz
                        //::    *abc")xyz*
                        if($openQuote > 0)
                        {
                            // add the pre-quote content to the query list
                            $queries[$fieldName][] = substr($subject, 0, $openQuote);

                            // add the rest of the subject to the query
                            if(strlen($subject) > $openQuote+1)
                                array_unshift($subjects, substr($subject, $openQuote+1));
                        }
                        // if there was a space before the double quote
                        //::    abc *"* xyz
                        //::    abc *"xyz*
                        //::    abc *")* xyz
                        //::    abc *")xyz*
                        else
                        {
                            // add the rest of the subject to the query
                            if(strlen($subject) > $openQuote+1)
                                array_unshift($subjects, substr($subject, $openQuote+1));
                        }
                    }
                    // if neither a closing paren or opening double quote was found
                    else
                    {
                        // add the subject to the query list
                        $queries[$fieldName][] = $subject;
                    }
                }
            }
            else
            {
                $openQuote = strpos($subject, '"');
                $openParen = strpos($subject, '(');

                // boolean false is returned if the needle is not found in
                // the haystatck, but this is easily confused with 0
                if($openQuote === false)
                    $openQuote = -1;
                if($openParen === false)
                    $openParen = -1;

                // if an open paren is found before a double quote
                //::    *abc(* xyz
                //::    *abc(xyz*
                //::    *abc("* xyz
                //::    *abc("xyz*
                //::    abc *(* xyz
                //::    abc *(xyz*
                //::    abc *("* xyz
                //::    abc *("xyz*
                if(($openQuote < 0 || $openParen < $openQuote) && $openParen >= 0)
                {
                    // the opening double quote has not been reached yet
                    $openQuote = -1;

                    // if there is a keyword immediately before the open paren,
                    // it is the fieldName to search within
                    //::    *abc(* xyz
                    //::    *abc(xyz*
                    //::    *abc("* xyz
                    //::    *abc("xyz*
                    if($openParen > 0)
                    {
                        $fieldName = strtolower(substr($subject, 0, $openParen));

                        // if there is not a space after the open paren,
                        // there is content that is part of the search query...
                        // add it to the subject list!
                        //::    *abc(xyz*
                        //::    *abc("xyz*
                        if(strlen($subject) > $openParen+1)
                            array_unshift($subjects, substr($subject, $openParen+1));
                    }
                    // if the open paren is the first character, there is
                    // not a fieldName to use
                    //::    abc *(* xyz
                    //::    abc *(xyz*
                    //::    abc *("* xyz
                    //::    abc *("xyz*
                    else
                    {
                        // trash the open paren and add the rest of the subject
                        // back to the subject list (if any subject remains)
                        if(strlen($subject) > 1)
                            array_unshift($subjects, substr($subject, 1));
                    }
                }
                // if a double quote is found before an open paren
                //::    *abc"* xyz
                //::    *abc"xyz*
                //::    *abc"(* xyz
                //::    *abc"(xyz*
                //::    abc *"* xyz
                //::    abc *"xyz*
                //::    abc *"(* xyz
                //::    abc *"(xyz*
                else if(($openParen < 0 || $openQuote < $openParen) && $openQuote >= 0)
                {
                    // the opening paren has not been reached yet
                    $openParen = -1;

                    // if a double quote is found after a keyword, assume that
                    // a space was supposed to be entered before the double quote
                    //::    *abc"* xyz
                    //::    *abc"xyz*
                    //::    *abc"(* xyz
                    //::    *abc"(xyz*
                    if($openQuote > 0)
                    {
                        // add the pre-quote content to the query list
                        $queries[$fieldName][] = substr($subject, 0, $openQuote);

                        // add the rest of the subject to the query
                        if(strlen($subject) > $openQuote+1)
                            array_unshift($subjects, substr($subject, $openQuote+1));
                    }
                    // if there was a space before the double quote
                    //::    abc *"* xyz
                    //::    abc *"xyz*
                    //::    abc *"(* xyz
                    //::    abc *"(xyz*
                    else
                    {
                        // add the rest of the subject to the query
                        if(strlen($subject) > $openQuote+1)
                            array_unshift($subjects, substr($subject, $openQuote+1));
                    }
                }
                // if neither a double quote or open paren are found
                //::    abc *xyz* 123
                else
                {
                    // add the subject to the query list
                    $queries[$fieldName][] = $subject;
                }
            }
        }

        // convert multi-field searches to multiple single field searches
        $newQueries = array();
        foreach($queries as $fieldNames => $fieldQueries)
        {
            $fieldNamesList = explode(',', $fieldNames);
            foreach($fieldNamesList as $fieldName)
            {
                if(!array_key_exists($fieldName, $newQueries))
                    $newQueries[$fieldName] = array();

                $newQueries[$fieldName] =
                    array_merge($newQueries[$fieldName], $fieldQueries);
            }
        }

        return $newQueries;
    }

    protected static function &getValues(&$queries, &$categories)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $values = null;

        $allFieldQueries = array();
        $titleQueries = array();
        $categoryQueries = array();
        $newQueries = array();
        $invalidQueries = array();
        foreach($queries as $fieldName => $fieldQueries)
        {
            if($fieldName == 'title')
            {
                foreach($fieldQueries as $query)
                {
                    $titleQueries[$query] = $query;
                }
            }
            else if($fieldName == 'category')
            {
                // categories are pre-processed
            }
            else if($fieldName != '')
            {
                $managers = Manager::getByFieldKeyname($fieldName);

                foreach($managers as $manager)
                {
                    foreach($fieldQueries as $query)
                    {
                        $inst = $manager->getInstance();
                        if($inst->isValid($query, null))
                        {
                            /*$op = '';
                            if(substr($query, 0, 1) == '+')
                            {
                                $query = substr($query, 1);
                                $op = '+';
                            }*/
                            $val = $inst->getCanonicalValue($query, null);
                            $newQueries[$fieldName][$val] = $val;
                        }
                        else
                        {
                            $invalidQueries[$fieldName][] = $inst->getCanonicalValue($query, null);
                        }
                    }
                }
            }
            else
            {
                foreach($fieldQueries as $query)
                {
                    $allFieldQueries[$query] = $query;
                }
            }
        }

        if(count($allFieldQueries) > 0 || count($titleQueries) > 0
            || count($newQueries) > 0)
        {
            $wheres = array();
            $matches = array();

            $allFieldQuery = implode(' ', $allFieldQueries);
            if(count($allFieldQueries) > 0)
            {
                foreach($allFieldQueries as $query)
                {
                    $query = addslashes(preg_quote($query));
                    preg_match_all('/[^a-zA-Z0-9\s*]+/', $query, $others, PREG_PATTERN_ORDER);
                    foreach($others[0] as $match)
                        $query = str_replace($match, '[' . $match . '[:space:]]?', $query);
                    $query = str_replace(' ', '[[:space:]]+', $query);
                    $query = str_replace('*', '[^[:space:]]*', $query);
                    $wheres[] = 'val.value REGEXP \'[[:<:]]' .
                        $query . '[[:>:]]\'';
                    $matches[] = 'IF(val.value REGEXP \'[[:<:]]' .
                        $query . '[[:>:]]\', IF(val.typeId=0,2,1), 0)';
                }
                /*
                $wheres[] = '(MATCH (val.value) AGAINST (\'' . $allFieldQuery
                    . '\' IN BOOLEAN MODE))';
                $matches[] = 'MATCH(val.value) AGAINST (\'' . $allFieldQuery
                    . '\' IN BOOLEAN MODE)';
                //*/
            }

            if(count($titleQueries) > 0)
            {
                foreach($titleQueries as $query)
                {
                    $query = addslashes(preg_quote($query));
                    preg_match_all('/[^a-zA-Z0-9\s*]+/', $query, $others, PREG_PATTERN_ORDER);
                    foreach($others[0] as $match)
                        $query = str_replace($match, '[' . $match . '[:space:]]?', $query);
                    $query = str_replace(' ', '[[:space:]]+', $query);
                    $query = str_replace('*', '[^[:space:]]*', $query);
                    $wheres[] = '(val.typeId=0 AND val.value REGEXP \'[[:<:]]' .
                        $query . '[[:>:]]\')';
                    $matches[] = 'IF(val.typeId=0 AND val.value REGEXP \'[[:<:]]' .
                        $query . '[[:>:]]\', 4, 0)';
                }
                /*
                $titleQuery = implode(' ', $titleQueries);
                $wheres[] = '(val.typeId=0 AND'
                    . ' MATCH (val.value) AGAINST (\'' . $titleQuery
                    . '\' IN BOOLEAN MODE))';
                $matches[] = '2*MATCH(val.value) AGAINST (\'' . $titleQuery
                    . '\' IN BOOLEAN MODE)';
                //*/
            }

            $categoryWhere = '';
            if(count($categories) > 0)
            {
                $categoryWhere = ' (cat.categoryId IN ('
                    . implode(',', $categories) . ')' . ' OR cat.categoryId IS NULL)';
            }

            foreach($newQueries as $fieldName => $fieldQueries)
            {
                foreach($fieldQueries as $query)
                {
                    $query = addslashes(preg_quote($query));
                    preg_match_all('/[^a-zA-Z0-9\s*]+/', $query, $others, PREG_PATTERN_ORDER);
                    foreach($others[0] as $match)
                        $query = str_replace($match, '[' . $match . '[:space:]]?', $query);
                    $query = str_replace(' ', '[[:space:]]+', $query);
                    $query = str_replace('*', '[^[:space:]]*', $query);
                    $wheres[] = '(field.keyname=\'' . addslashes($fieldName) . '\' AND val.value REGEXP \'[[:<:]]' .
                        $query . '[[:>:]]\')';
                    $matches[] = 'IF(field.keyname=\'' . addslashes($fieldName) . '\' AND val.value REGEXP \'[[:<:]]' .
                        $query . '[[:>:]]\', 2, 0)';
                }
                /*
                $query = implode(' ', $fieldQueries);
                $wheres[] = '(field.keyname=\'' . addslashes($fieldName) . '\''
                    . ' AND MATCH (val.value) AGAINST (\'' . $query
                    . '\' IN BOOLEAN MODE))';
                $matches[] = '2*MATCH(val.value) AGAINST (\'' . $query
                    . '\' IN BOOLEAN MODE)';
                //*/
            }

            $rank = '';
            $order = '';
            if(count($matches) > 0)
            {
                $rank = ', (' . implode('+', $matches) . ') AS rank';
                $order = ' ORDER BY rank DESC';
            }

            $where = '';
            if(count($wheres) > 0 && $categoryWhere != '')
            {
                $where = ' WHERE (' . implode(' OR ', $wheres) . ') '
                    . ' AND ' . $categoryWhere;
            }
            else if(count($wheres) > 0)
            {
                $where = ' WHERE (' . implode(' OR ', $wheres) . ') ';
            }
            else if($categoryWhere != '')
            {
                $where = ' WHERE ' . $categoryWhere;
            }

            $values = array();
            $query = new Query('SELECT val.valueId, val.value ' . $rank . ' FROM '
                . $db->getTable('TypeValue') . ' AS val '
                . ' LEFT JOIN ' . $db->getTable('Field') . ' AS field '
                . ' ON val.typeId=field.typeId '
                . ' LEFT JOIN ' . $db->getTable('Category_Field') . ' AS cat_field '
                . ' ON field.fieldId=cat_field.fieldId '
                . ' LEFT JOIN ' . $db->getTable('ItemCategory') . ' AS cat '
                . ' ON cat_field.categoryId=cat.categoryId '
                . $where
                . ' GROUP BY val.valueId' . $order);
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                $values[intval($row['valueId'])] = $row;
            }
        }

        return $values;
    }

    protected static function &getSearchResults(&$values, &$categories)
    {
        $db = Database::getConnection('com.lightdatasys.eltalog');

        $searchResults = array();

        if(count($values) > 0 || (is_null($values) && count($categories) > 0))
        {
            if(is_null($values))
                $values = array();
            $valueIds = array_keys($values);

            $where = ' WHERE ';
            if(count($valueIds) > 0 && count($categories) > 0)
            {
                $where .= 'val.valueId IN (' . implode(',', $valueIds)
                    . ') AND item.categoryId IN ('
                    . implode(',', $categories) . ')';
            }
            else if(count($valueIds) > 0)
            {
                $where .= 'val.valueId IN (' . implode(',', $valueIds)
                    . ')';
            }
            else
            {
                $where .= 'item.categoryId IN ('
                    . implode(',', $categories) . ')';
            }

            $results = array();
            if(count($valueIds) > 0)
            {
                $query = new Query(
                    '(SELECT itemVal.itemId, val.valueId FROM '
                    . $db->getTable('TypeValue') . ' AS val '
                    . ' INNER JOIN ' . $db->getTable('ItemValue') . ' AS itemVal '
                    . ' ON val.valueId=itemVal.valueId '
                    . ' WHERE val.valueId IN (' . implode(',', $valueIds) . ')'
                    . ')'
                    . ' UNION '
                    . '(SELECT inst.itemId, val.valueId FROM '
                    . $db->getTable('TypeValue') . ' AS val '
                    . ' INNER JOIN ' . $db->getTable('InstanceValue') . ' AS instVal '
                    . ' ON val.valueId=instVal.valueId '
                    . ' INNER JOIN ' . $db->getTable('Instance') . ' AS inst '
                    . ' ON instVal.instanceId=inst.instanceId '
                    . ' WHERE val.valueId IN (' . implode(',', $valueIds) . ')'
                    . ')'
                    . ' UNION '
                    . '(SELECT item.itemId, val.valueId FROM '
                    . $db->getTable('TypeValue') . ' AS val '
                    . ' INNER JOIN ' . $db->getTable('Item') . ' AS item '
                    . ' ON val.valueId=item.titleId '
                    . $where
                    . ')'
                    . '');
            }
            else
            {
                $query = new Query('SELECT item.itemId, val.valueId FROM '
                    . $db->getTable('TypeValue') . ' AS val '
                    . ' INNER JOIN ' . $db->getTable('Item') . ' AS item '
                    . ' ON val.valueId=item.titleId '
                    . $where
                    . '');
            }
            $result = $db->execQuery($query);
            while($row = $db->getAssoc($result))
            {
                if(array_key_exists($row['itemId'], $results))
                {
                    $results[$row['itemId']]['valueIds'][$row['valueId']] = $row['valueId'];
                    if(array_key_exists($row['valueId'], $values)
                        && array_key_exists('rank', $values[$row['valueId']]))
                        $results[$row['itemId']]['rank'] += $values[$row['valueId']]['rank'];
                    else
                        $results[$row['itemId']]['rank'] += 1;

                }
                else
                {
                    $res = array();
                    $res['itemId'] = $row['itemId'];
                    $res['valueIds'][$row['valueId']] = $row['valueId'];
                    if(array_key_exists($row['valueId'], $values)
                        && array_key_exists('rank', $values[$row['valueId']]))
                        $res['rank'] = $values[$row['valueId']]['rank'];
                    else
                        $res['rank'] = 1;

                    $results[$row['itemId']] = $res;
                }
            }

            $itemIds = array_keys($results);
            $items = Item::getByItemIds($itemIds);
            foreach($results as $itemId => $result)
            {
                if(array_key_exists($itemId, $items))
                {
                    $searchResults[$itemId] = new SearchResult($items[$itemId]
                        , $result['valueIds'], $result['rank']);
                }
            }

            function compareByRank($a, $b)
            {
                $aRank = $a->getRank();
                $bRank = $b->getRank();

                if($aRank < $bRank)
                    return 1;
                else if($aRank > $bRank)
                    return -1;
                else
                    return strcmp($a->getItem()->getTitle(), $b->getItem()->getTitle());
            }
            uasort($searchResults, 'compareByRank');
        }

        return $searchResults;
    }

    protected static function calcTotalRank(&$results)
    {
        $totalRank = 0;
        foreach($results as $result)
        {
            $totalRank += $result->getRank();
        }

        return $totalRank;
    }
}


class SearchResult
{
    public $item;
    public $valueIds;
    public $rank;


    public function __construct($item, $valueIds, $rank)
    {
        $this->item = $item;
        $this->valueIds = $valueIds;
        $this->rank = $rank;
    }


    public function getItem()
    {
        return $this->item;
    }

    public function getRank()
    {
        return $this->rank;
    }

    public function getValueIds()
    {
        return $this->valueIds;
    }
}


?>
