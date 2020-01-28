<?php // $Id: backlog.class.php 14201 2012-07-09 14:20:10Z jrm_ $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Backlog.
 *
 * @version     $Revision: 14201 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 */

define ( 'BACKLOG_SUCCESS', 'BACKLOG_SUCCESS' );
define ( 'BACKLOG_FAILURE', 'BACKLOG_FAILURE' );
define ( 'BACKLOG_DEBUG',   'BACKLOG_DEBUG' );
define ( 'BACKLOG_INFO',   'BACKLOG_INFO' );

/**
 * Backlog class
 * @deprecated since Claroline 1.10, use exceptions, Console.lib or DialogBox 
 *  instead
 */
class Backlog
{
    var $_backlog = array();
    var $_size = array();

    function Backlog()
    {
        $this->_size[BACKLOG_SUCCESS] = 0;
        $this->_size[BACKLOG_FAILURE] = 0;
        $this->_size[BACKLOG_DEBUG] = 0;
        $this->_size[BACKLOG_INFO] = 0;
    }

    function success( $msg )
    {
        $this->message( $msg, BACKLOG_SUCCESS );
        $this->_size[BACKLOG_SUCCESS]++;
    }

    function failure( $msg )
    {
        $this->message( $msg, BACKLOG_FAILURE );
        $this->_size[BACKLOG_FAILURE]++;
    }

    function debug( $msg )
    {
        $this->message( $msg, BACKLOG_DEBUG );
        $this->_size[BACKLOG_DEBUG]++;
    }

    function info( $msg )
    {
        $this->message( $msg, BACKLOG_INFO );
        $this->_size[BACKLOG_INFO]++;
    }

    function message( $msg, $type )
    {
        $this->_backlog[] = array( 'type' => $type, 'msg' => $msg );
    }

    function size( $type = null )
    {
        switch ( $type )
        {
            case BACKLOG_SUCCESS:
            case BACKLOG_FAILURE:
            case BACKLOG_DEBUG:
            case BACKLOG_INFO:
            {
                return $this->_size[$type];
            } break;

            default:
            {
                return count($this->_backlog);
            }
        }
    }

    function output()
    {
        $out = array();

        foreach ( $this->_backlog as $entry )
        {
            $type = $entry['type'];
            $msg = $entry['msg'];

            switch ( $type )
            {
                case BACKLOG_SUCCESS:
                {
                    $out[] = '<span class="backlogSuccess">' . $msg . '</span>';
                } break;
                case BACKLOG_FAILURE:
                {
                    $out[] = '<span class="backlogFailure">' . $msg . '</span>';
                } break;
                case BACKLOG_DEBUG:
                {
                    $out[] = '<span class="backlogDebug">' . $msg . '</span>';
                } break;
                case BACKLOG_INFO:
                {
                    $out[] = '<span class="backlogInfo">' . $msg . '</span>';
                } break;
                default:
                {
                    $out[] = '<span class="backlogMessage">' . $msg . '</span>';
                }
            }

            unset ($type, $msg );
        }

        return implode( '<br />' . "\n", $out );
    }

    function append( $other )
    {
        if ( is_a( $other, 'Backlog' ) )
        {
            $this->_backlog = array_merge( $this->_backlog, $other->_backlog );
            return true;
        }
        else
        {
            return false;
        }
    }

    function main()
    {
        $bl = new Backlog;
        echo '<pre>';
        $bl->success( 'message success 1' );
        $bl->debug( 'message debug 1' );
        $bl->failure( 'message failure 1' );
        $bl->success( 'message success 2' );
        $bl->info( 'message info 1' );
        var_dump( $bl->size() );
        var_dump( $bl->_size );
        echo '</pre>';

        echo '<pre>';
        echo $bl->output();
        echo '</pre>';
        
        echo 'Append';
        echo '<pre>';
        $bl->append( $bl );
        echo $bl->output();
        echo '</pre>';
    }
}

class Backlog_Reporter
{
    public static function report( $summary, $details, $label = '', $focus = false )
    {
        $id = uniqid('details');

        if ( empty( $details ) )
        {
            $display = '<span class="backlogSummary">'.$summary.'</span>';
        }
        else
        {
            $linkName = ( $focus ) ? "#lnk_$id" : "#";
            $labeldetails = empty( $label ) ? get_lang('details') : $label;
            $display = <<<__ERRDISP__
<script type="text/javascript">
function toggleDetails( id )
{
var details = document.getElementById( id );

if ( details.style.display == 'block' )
{
    details.style.display = 'none';
}
else
{
    details.style.display = 'block';
}
}
</script>
<a name="lnk_$id"></a>
<span class="backlogSummary">$summary
[<a href="$linkName" onclick="toggleDetails('$id');return false;">$labeldetails</a>]
</span>
<div id="$id" style="display: none;" class="backlogDetails">
$details
</div>
__ERRDISP__;
        }

        return $display;
    }
}

if ( basename( $_SERVER['PHP_SELF'] ) === basename(__FILE__) )
{
    Backlog::main();
}
