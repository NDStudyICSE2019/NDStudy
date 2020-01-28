<?php // $Id: finder.lib.php 12923 2011-03-03 14:23:57Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * File Finder Library
 * Inspired by SPL examples by Marcus Boerger
 *
 * @version     1.9 $Revision: 12923 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     utils
 */

// From PEAR PHP_Compat 1.5.0
if (!defined('PATH_SEPARATOR'))
{
    define('PATH_SEPARATOR', strtoupper(substr(PHP_OS, 0, 3) == 'WIN') ? ';' : ':' );
}

/**
 * File Finder that searches a given path for files or folders matching a given criterium
 */
class Claro_FileFinder extends FilterIterator
{
    protected $searchString;
    
    /**
     * @param   string $path list of directory path separated by PATH_SEPARATOR
     * @param   string $searchString search criterium
     * @param   bool $recursive set to false to disable recursive search in subdirectories, default true
     */
    public function __construct( $path, $searchString, $recursive = true )
    {
        $this->searchString = $searchString;
        
        $pathList = explode( PATH_SEPARATOR, $path );
            
        if (count($pathList) <= 1)
        {
            if ( ! $recursive )
            {
                parent::__construct( 
                    new IteratorIterator( 
                        new DirectoryIterator($path) ) ); 
            }
            else
            {
                parent::__construct(
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path)));
            }
        }
        else
        {
            $it = new AppendIterator();
                
            foreach ( $pathList as $path )
            {
                if ( ! $recursive )
                {
                    $it->append( 
                        new IteratorIterator( 
                            new DirectoryIterator($path) ) ); 
                }
                else
                {
                    $it->append(
                        new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($path)));
                }
            }
            
            parent::__construct($it);
        }
    }
    
    /**
     * Get the search criterium
     * @return  string
     */
    public function getSearchString()
    {
        return $this->searchString;
    }
    
    /**
     * @see     FilterIterator (SPL)
     */
    public function accept()
    {
        return !strcmp($this->getSearchString(), $this->current() );
    }
}

/**
 * Use a PCRE regular expression as search criterium
 */
class Claro_FileFinder_Regexp extends Claro_FileFinder
{
    public function accept()
    {
        return preg_match( $this->current(), $this->getSearchString() );
    }
}

/**
 * Use a file extension as search criterium
 */
class Claro_FileFinder_Extension extends Claro_FileFinder
{
    public function accept()
    {
        return $this->current()->isFile() &&
            ( strtolower( substr( $this->current(), - ( strlen($this->getSearchString()) ) ) ) == 
                strtolower( $this->getSearchString() ) );
    }
}
