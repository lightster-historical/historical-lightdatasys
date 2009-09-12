<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 *
 *
 * PHP version 5
 *
 * LICENSE: This file may only be used under the terms of the license
 *          available at www.mephex.com.
 *
 * @author     Matt Light <mlight@@mephex..com>
 * @copyright  2006 Mephex Technologies
 * @license    http://www.mephex.com
 * @link
 * @since      05.12.27
 * @version    05.12.27
 */


// this software should not be running if the paths are not set
if (!defined('dirLib')) trigger_error('dirLib is not set', E_USER_ERROR);
if (!defined('dirCfg')) trigger_error('dirCfg is not set', E_USER_ERROR);



class EltalogEditor_Book
{    
    protected $id;
    protected $title;
    protected $edition;
    protected $isbn;
    protected $upc;
    protected $publisher_id;
    protected $copyright_year;
    protected $location_id;
    protected $owner_id;
    protected $author_ids;
    protected $genre_ids;
    
    protected $errors;
    
    
    public function __construct ()
    {
        $this->id               = null;
        $this->title            = '';
        $this->edition          = null;
        $this->isbn             = null;
        $this->upc              = null;
        $this->publisher_id     = null;
        $this->copyright_year   = null;
        $this->location_id      = null;
        $this->owner_id         = null;
        $this->author_ids       = array();
        $this->genre_ids        = array();
        
        $this->errors           = array();
    }
    // constructor
    
    
    public static function loadBook ($id, $db)
    {
        $book = new EltalogEditor_Book();
        
        $query = $db->query('SELECT * FROM %s WHERE book_id=%d'
            , $db->table('book'), intval($id));
        if ($array = $db->getAssoc($query))
        {
            $book->id             = $array['book_id'];
            $book->title          = $array['title'];
            $book->edition        = iif($array['edition'], null);
            $book->isbn           = iif($array['isbn'], null);
            $book->upc            = iif($array['sku'], null);
            $book->publisher_id   = iif($array['publisher_id'], null);
            $book->copyright_year = iif($array['copyright_year'], null);
            $book->location_id    = iif($array['location_id'], null);
            $book->owner_id       = iif($array['user_id'], null);
            
            $book->author_ids     = array();
            $book->genre_ids      = array();
            
            $query = $db->query('SELECT person_id FROM %s WHERE book_id=%d'
                , $db->table('book_author'), $book->id);
            while ($array = $db->getAssoc($query))
            {
                $book->author_ids = $array['person_id'];
            }
            
            $query = $db->query('SELECT genre_id FROM %s WHERE book_id=%d'
                , $db->table('book_genre'), $book->id);
            while ($array = $db->getAssoc($query))
            {
                $book->genre_ids = $array['genre_id'];
            }
        }      
        
        return $book;
    }
    
    public function saveBook ($db)
    {
        $this->validateFields($db);
        
        if (count($this->errors) > 0)
        {
            return $this->errors;
        }
        else 
        {
            $edition        = $this->edition 
                ? addQuotes(addslashes($this->edition)) : 'NULL';
            $isbn           = $this->isbn 
                ? addQuotes(addslashes($this->isbn)) : 'NULL';
            $upc            = $this->upc
                ? addQuotes(addslashes($this->upc)) : 'NULL';
            $publisher_id   = $this->publisher_id 
                ? addQuotes(addslashes($this->publisher_id)) : 'NULL'; 
            $copyright_year = $this->copyright_year 
                ? addQuotes(addslashes($this->copyright_year)) : 'NULL';
            $location_id    = $this->location_id 
                ? addQuotes(addslashes($this->location_id)) : 'NULL';
            
            if (is_null($this->id))
            {
                $sql   = sprintf('INSERT INTO %s (`title`, `edition`, `isbn`, '
                    . '`sku`, `publisher_id`, `copyright_year`, `location_id`) '
                    . 'VALUES (\'%s\', %s, %s, %s, %s, %s, %s)'
                    , $db->table('book'), addslashes($this->title), $edition
                    , $isbn, $upc
                    , $publisher_id, $copyright_year, $location_id);
                $query = $db->query($sql);
                $this->id = $db->autoIncrementId();
            }
            else
            {
                $sql   = sprintf('UPDATE %s SET title=\'%s\', edition=%s, '
                    . 'isbn=%s, sku=%s, publisher_id=%s, '
                    . 'copyright_year=%s, location_id=%s WHERE book_id=%d'
                    , $db->table('book'), addslashes($this->title), $edition
                    , $isbn, $upc, $publisher_id, $copyright_year
                    , $location_id, $this->id);
                $query = $db->query($sql);
            }
            
            $query = $db->query('DELETE FROM %s WHERE book_id=%d'
                , $db->table('book_author'), $this->id);
            $query = $db->query('DELETE FROM %s WHERE book_id=%d'
                , $db->table('book_genre'), $this->id);
            
            if (count($this->author_ids) > 0)
            {
                $values = '';
                $delim  = '';
                foreach ($this->author_ids as $author)
                {
                    $values .= $delim . ' (' . $this->id . ', ' . $author . ')';
                    $delim   = ',';
                }
                $sql   = sprintf('INSERT INTO %s (`book_id`, `person_id`) VALUES '
                    . $values, $db->table('book_author'));
                $query = $db->query($sql);
            }
            
            if (count($this->genre_ids) > 0)
            {
                $values = '';
                $delim  = '';
                foreach ($this->genre_ids as $genre)
                {
                    $values .= $delim . ' (' . $this->id . ', ' . $genre . ')';
                    $delim   = ',';
                }
                $sql   = sprintf('INSERT INTO %s (`book_id`, `genre_id`) VALUES '
                    . $values, $db->table('book_genre'));
                $query = $db->query($sql);
            }
        }
    }
    
    
    public function getId ()            {return $this->id;}
    public function getTitle ()         {return $this->title;}
    public function getEdition ()       {return $this->edition;}
    public function getISBN ()          {return $this->isbn;}
    public function getUPC ()           {return $this->upc;}
    public function getPublisher ()     {return $this->publisher;}
    public function getCopyrightYear () {return $this->copyright_year;}
    public function getLocation ()      {return $this->location;}
    public function getOwner ()         {return $this->owner;}
    
    public function getAuthor ($index)  
    {
        if ($index < count($this->author_ids))
        {
            return $this->author_ids[$index];
        }
        return null;
    }
    public function getAuthors ()       {return $this->author_ids;}
    public function getAuthorCount ()   {return count($this->author_ids);}
    public function getGenre ($index)
    {
        if ($index < count($this->genre_ids))
        {
            return $this->genre_ids[$index];
        }
        return null;
    }
    public function getGenres ()        {return $this->genre_ids;}
    public function getGenreCount ()    {return count($this->genre_ids);}
    
    public function getErrors ()        {return $this->errors;}
    
    
    public function setTitle ($title) 
        {$this->title = $title;}
    public function setEdition ($edition) 
        {$this->edition = $edition;}
    public function setISBN ($isbn) 
        {$this->isbn = $isbn;}
    public function setUPC ($upc) 
        {$this->upc = $upc;}
    public function setPublisher ($publisher) 
        {$this->publisher_id = $publisher;}
    public function setCopyrightYear ($year)
        {$this->copyright_year = $year;}
    public function setLocation ($location) 
        {$this->location_id = $location;}
    public function setOwner ($owner) 
        {$this->owner_id = $owner;}
        
    public function clearAuthors ()         {$this->author_ids = array();}
    public function addAuthor ($author_id)  {$this->author_ids[] = $author_id;} 
    public function clearGenres ()          {$this->genre_ids = array();}
    public function addGenre ($genre_id)    {$this->genre_ids[] = $genre_id;}


    public function validateFields ($db)
    {
        $this->errors = array();
        
        $this->validateTitle();
        $this->validateEdition();
        $this->validateISBN();
        $this->validateUPC();
        $this->validatePublisher($db);
        $this->validateCopyrightYear();
        $this->validateLocation($db);
        $this->validateOwner($db);
        $this->validateAuthors($db);
        $this->validateGenres($db);
    }
    
    protected function validateTitle ()
    {
        if (trim($this->title) == '')
        {
            $this->title = '';
            $this->errors['title'] = 'A title must be provided.';
        }
    }    
    
    protected function validateEdition ()
    {
        $this->edition = intval($this->edition);
        if ($this->edition <= 0)
        {
            $this->edition = null;
        }
    }
    
    protected function validateISBN ()
    {
        $isbn = trim($this->isbn);
        if (preg_match('!^([0-9]{3,3})?[0-9]{9}[0-9xX]$!', $isbn))
        {
            $offset = (strlen($isbn) > 10) ? 3 : 0;
            $check  = 0;
            for ($i = 0; $i <= 8; $i++)
            {
                $check += (10-$i) * $isbn[$offset + $i];
            }
            $next   = 11-($check % 11);
            if ($next == 11)
            {
                $next = '0';
            }
            else if ($next == 10)
            {
                $next = 'x';
            }

            if ($next != strtolower($isbn[$offset + 9]))
            {
                $this->isbn = null;
                $this->errors['isbn'] = 'The ISBN provided is invalid.';
            }
            else
            {
                $this->isbn = $isbn;
            }
        }
        else if ($isbn != '')
        {
            $this->errors['isbn'] = 'The ISBN provided is invalid.';
        }
    } 
    
    protected function validateUPC ()
    {
        $this->upc = trim($this->upc);
    }
    
    protected function validatePublisher ($db)
    {
        $this->publisher_id = intval($this->publisher_id);
        
        $query = $db->query('SELECT COUNT(company_id) FROM %s WHERE '
            . 'company_id=%d', $db->table('company'), $this->publisher_id);
        $array = $db->getRow($query);
        
        if ($array[0] <= 0)
        {
            $this->publisher_id = null;
        }
    } 
    
    protected function validateCopyrightYear () 
    {
        $this->copyright_year = intval($this->copyright_year);
        
        if ($this->copyright_year <= 0)
        {
            $this->copyright_year = null;
        }
    }
    
    protected function validateLocation ($db)
    {
        $this->location_id = intval($this->location_id);
        
        $query = $db->query('SELECT COUNT(location_id) FROM %s WHERE '
            . 'location_id=%d', $db->table('location'), $this->location_id);
        $array = $db->getRow($query);
        
        if ($array[0] <= 0)
        {
            $this->location_id = null;
        }
    } 
    
    protected function validateOwner ($db)
    {
        $this->owner_id = intval($this->owner_id);
        
        $query = $db->query('SELECT COUNT(user_id) FROM %s WHERE '
            . 'user_id=%d', $db->table('user'), $this->owner_id);
        $array = $db->getRow($query);
        
        if ($array[0] <= 0)
        {
            $this->owner_id = null;
        }
    } 
    
    protected function validateAuthors ($db)
    {
        if (count($this->author_ids) > 0)
        {
            $authors = array();
            foreach ($this->author_ids as $author)
            {
                $authors[] = intval($author); 
            }
            $authors = array_unique($authors);
            
            $this->author_ids = array();
            
            $query = $db->query('SELECT person_id FROM %s WHERE person_id IN (%s)'
                , $db->table('person'), implode(',', $authors));
            while ($array = $db->getRow($query))
            {
                $this->author_ids[] = $array[0];
            }
        }
    }
    
    protected function validateGenres ($db)
    {
        if (count($this->genre_ids) > 0)
        {
            $genres = array();
            foreach ($this->genre_ids as $genre)
            {
                $genres[] = intval($genre); 
            }
            $genres = array_unique($genres);
            
            $this->genre_ids = array();
            
            $query = $db->query('SELECT genre_id FROM %s WHERE genre_id IN (%s)'
                , $db->table('genre'), implode(',', $genres));
            while ($array = $db->getRow($query))
            {
                $this->genre_ids[] = $array[0];
            }
        }
    }
}
// EltalogEditor_Book class


?>
