<?php // $Id: debug.lib.php 14314 2012-11-07 09:09:19Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Debugging functions and classes.
 *
 * @version     $Revision: 14314 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     kernel.core
 */

function dbg_html_var( $var )
{
    return claro_htmlspecialchars(var_export( $var, true ));
}

class Profiler
{
    const PROFILER_STATUS_STARTED = 'started';
    const PROFILER_STATUS_NOT_STARTED = 'not_started';
    const PROFILER_STATUS_STOPPED = 'stopped';
    
    private $startTime;
    private $status;
    private $endTime;
    private $log;

    public function __construct()
    {
        $this->log = array();
        $this->status = self::PROFILER_STATUS_NOT_STARTED;
    }

    public function start( $restart = false )
    {
        if ( $this->status == self::PROFILER_STATUS_STARTED
            && ! $restart )
        {
            return;
        }
        
        $this->startTime = $this->_getCurrentTime();
        $this->status = self::PROFILER_STATUS_STARTED;
        
        Console::log(sprintf("&gt;&gt; Profiler (re)started at %f", $this->startTime), 'profile');
    }

    public function restart()
    {
        $this->start( true );
    }

    public function stop()
    {
        if ( $this->status != self::PROFILER_STATUS_STARTED )
        {
            $this->restart();
        }

        $this->endTime = $this->_getCurrentTime();
        $this->status = self::PROFILER_STATUS_STOPPED;
        
        Console::log(sprintf("&gt;&gt; Profiler stoped at %f", $this->endTime), 'profile');
        Console::log(
            sprintf("** Elapsed time : %f seconds **", $this->getElapsedTime())
            , 'profile');
    }

    public function mark( $file, $line, $msg = '##MARK##' )
    {
        if ( $this->status != self::PROFILER_STATUS_STARTED )
        {
            $this->restart();
        }

        $now = $this->_getCurrentTime();

        $elapsed = $now - $this->startTime;
        $elapsed = sprintf( '%f seconds', $elapsed );
        $timestamp = sprintf( '[@%f]', $now );

        $mark = "$timestamp $msg <br />in $file at line $line after $elapsed";

        $this->log[] = $mark;
        Console::log($mark, 'profile');
    }

    public function getElapsedTime()
    {
        return $this->endTime - $this->startTime;
    }

    private function _getCurrentTime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}