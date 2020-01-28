<?php // $Id: iterators.lib.php 13760 2011-10-28 09:26:29Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Iterator classes
 *
 * @version     Claroline 1.11 $Revision: 13760 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.utils
 * @since       Claroline 1.11
 */

interface CountableIterator extends Countable, Iterator{};

interface CountableSeekableIterator extends CountableIterator, SeekableIterator{};


/**
 * Define a generic array row to object array iterator
 * You must extends it and implement the current() method
 */
abstract class RowToObjectArrayIterator implements CountableIterator
{

  protected $collection = null;
  protected $currentIndex = 0;
  protected $maxIndex;
  protected $keys = null;
  
  /**
   *
   * @param Array $array 
   */
  public function __construct($array) 
  {

    $this->collection = $array;
    $this->maxIndex = count( $array );
    $this->keys = array_keys( $array );
  }

  public function key()
  {
    return $this->keys[$this->currentIndex];
  }

  public function next()
  {
    ++$this->currentIndex;
  }

  public function rewind()
  {
    $this->currentIndex = 0;
  }

  public function valid()
  {
    return ( isset($this->keys[$this->currentIndex]) );
  }
  
  public function count()
  {
      return count($this->collection);
  }
  
}

/**
 * Define a generic row to object iterator iterator
 * You must extends it and implement the current() method
 */
abstract class RowToObjectIteratorIterator implements CountableIterator
{
    protected $internalIterator;
    
    /**
     * Constructor
     * @param CountableIterator $internalIterator
     */
    public function __construct(CountableIterator $internalIterator)
    {
        $this->internalIterator = $internalIterator;
    }
    
    public function next ()
    {
        return $this->internalIterator->next();
    }
    
    public function key ()
    {
        return $this->internalIterator->key();
    }
    
    public function valid ()
    {
        return $this->internalIterator->valid();
    }
    
    public function rewind ()
    {
        return $this->internalIterator->rewind();
    }
    
    public function count ()
    {
        return count( $this->internalIterator );
    }
}
