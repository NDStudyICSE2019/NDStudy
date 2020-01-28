<?php // $Id: lock.lib.php 14181 2012-06-13 08:15:00Z zefredz $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 *
 * @version     Claroline 1.11 $Revision: 14181 $
 * @copyright   2001-2012 Universite catholique de Louvain (UCL)
 * @author      Frederic Minne <zefredz@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     icterms
 */

/**
 * This class implements lock to force the execution of only one redirecting 
 * kernel hook by the kernel. Kernel hooks are pieces of code defined in modules
 * through the functions.php script to execute instructions just after the kernel 
 * initialization on every pages of the platform. Redirecting are kernel hooks 
 * that use HTTP redirection to go to another script than the one currently 
 * executed. The issue with multiple redircting kernel hooks is that can redirect 
 * one another forming an infinite loop making the platform unable to work 
 * properly.
 * This class allows to avoid those issues by requesting a lock before the 
 * execution of the kernel hook, so only one redirecting hook will be executed.
 * You can find examples of usage in ICSURVEW and ICTERM modules.
 * 
 * WARNING : DO NOT FORGET to release the lock when the script to 
 * which the hook has made the redirection has finished his job.
 * 
 * Usage : in functions.php
 * 
 * // ask for the lock
 * Claro_KernelHook_Lock::getLock();
 * 
 * // check if the module has got the lock
 * if ( Claro_KernelHook_Lock::hasLock() )
 * {
 *      claro_redirect( get_module_url('MYMODULE') . '/hook.php' );
 * }
 * 
 * 
 * Usage : in hook.php
 * 
 * // do your stuff
 * 
 * 
 * // DO NOT FORGET TO RELEASE THE LOCK !
 * 
 * Claro_KernelHook_Lock::releaseLock();
 * 
 */
class Claro_KernelHook_Lock
{
    const CLARO_KERNEL_HOOK_LOCK = 'claroKernelHookLock';
    
    /**
     * Get a lock the current module
     * @return boolean 
     */
    public static function getLock()
    {
        $moduleLabel = get_current_module_label();
        
        if ( empty($moduleLabel) )
        {
            return false;
        }
        
        if( self::hasLock( $moduleLabel ) )
        {
            return true;
        }
        elseif( self::lockAvailable() )
        {
            $_SESSION[ self::CLARO_KERNEL_HOOK_LOCK ] = $moduleLabel;
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
     * Check if the current module has the lock
     * @return type 
     */
    public static function hasLock()
    {
        $moduleLabel = get_current_module_label();
        
        return isset( $_SESSION[ self::CLARO_KERNEL_HOOK_LOCK ] )
            && $_SESSION[ self::CLARO_KERNEL_HOOK_LOCK ] == $moduleLabel;
    }
    
    /**
     * Check if the lock is available
     * @return type 
     */
    public static function lockAvailable()
    {
        return ! ( isset( $_SESSION[ self::CLARO_KERNEL_HOOK_LOCK ] )
            && ! empty( $_SESSION[ self::CLARO_KERNEL_HOOK_LOCK ] ) );
    }
    
    /**
     * Release the lock
     * @return boolean 
     */
    public static function releaseLock()
    {
        $moduleLabel = get_current_module_label();
        
        if( self::hasLock( $moduleLabel ) )
        {
            unset( $_SESSION[ self::CLARO_KERNEL_HOOK_LOCK ] );
            return true;
        }
        else
        {
            return false;
        }
    }
}
