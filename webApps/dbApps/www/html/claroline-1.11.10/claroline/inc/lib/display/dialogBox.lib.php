<?php // $Id: dialogBox.lib.php 13034 2011-04-01 15:07:53Z abourguignon $

// vim: expandtab sw=4 ts=4 sts=4:

/**
 * CLAROLINE
 *
 * Dialog box.
 *
 * @version     $Revision: 13034 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2.0 or later
 * @package     display
 */

/**
 * Constants deprecated
 * @deprecated since Claroline 1.9.6 use DialogBox constants instead
 */
define ( 'DIALOG_INFO',     'DIALOG_INFO' );
define ( 'DIALOG_SUCCESS',  'DIALOG_SUCCESS' );
define ( 'DIALOG_WARNING',  'DIALOG_WARNING' );
define ( 'DIALOG_ERROR',    'DIALOG_ERROR' );
define ( 'DIALOG_QUESTION', 'DIALOG_QUESTION');
define ( 'DIALOG_FORM',     'DIALOG_FORM' );
define ( 'DIALOG_TITLE',    'DIALOG_TITLE' );

/**
 * Dialog Box class
 */
class DialogBox implements Display
{
    private
        $_dialogBox = array(),
        $_size = array(),
        $_boxType = 'auto';

    /**
     * @since Claroline 1.9.6
     **/
    const
        DIALOG_INFO =       'DIALOG_INFO',
        DIALOG_SUCCESS =    'DIALOG_SUCCESS',
        DIALOG_WARNING =    'DIALOG_WARNING',
        DIALOG_ERROR =      'DIALOG_ERROR',
        DIALOG_QUESTION =   'DIALOG_QUESTION',
        DIALOG_FORM =       'DIALOG_FORM',
        DIALOG_TITLE =      'DIALOG_TITLE';

    /*
     * Constructor
     */
    public function __construct()
    {
        $this->_size[self::DIALOG_INFO] = 0;
        $this->_size[self::DIALOG_SUCCESS] = 0;
        $this->_size[self::DIALOG_WARNING] = 0;
        $this->_size[self::DIALOG_ERROR] = 0;
        $this->_size[self::DIALOG_QUESTION] = 0;
        $this->_size[self::DIALOG_FORM] = 0;
        $this->_size[self::DIALOG_TITLE] = 0;
    }

    /*
     * Add a standard message
     * @param $msg string text to show in dialog
     * @return $this
     * @since return $this for chaining since Claroline 1.9.6
     */
    public function info( $msg )
    {
        $this->message( $msg, self::DIALOG_INFO );
        $this->_size[self::DIALOG_INFO]++;

        return $this;
    }

    /*
     * Add a success message
     * @param $msg string text to show in dialog
     * @return $this
     * @since return $this for chaining since Claroline 1.9.6
     */
    public function success( $msg )
    {
        $this->message( $msg, self::DIALOG_SUCCESS );
        $this->_size[self::DIALOG_SUCCESS]++;

        return $this;
    }

    /*
     * Add a success message
     * @param $msg string text to show in dialog
     * @return $this
     * @since return $this for chaining since Claroline 1.9.6
     */
    public function warning( $msg )
    {
        $this->message( $msg, self::DIALOG_WARNING );
        $this->_size[self::DIALOG_WARNING]++;

        return $this;
    }

    /*
     * Add an error message
     * @param $msg string text to show in dialog
     * @return $this
     * @since return $this for chaining since Claroline 1.9.6
     */
    public function error( $msg )
    {
        $this->message( $msg, self::DIALOG_ERROR );
        $this->_size[self::DIALOG_ERROR]++;

        return $this;
    }

    /*
     * Add a question
     * @param $msg string text to show in dialog
     * @return $this
     * @since return $this for chaining since Claroline 1.9.6
     */
    public function question( $msg )
    {
        $this->message( $msg, self::DIALOG_QUESTION );
        $this->_size[self::DIALOG_QUESTION]++;

        return $this;
    }

    /*
     * Add a form
     * @param $msg string text to show in dialog
     * @return $this
     * @since return $this for chaining since Claroline 1.9.6
     */
    public function form( $msg )
    {
        $this->message( $msg, self::DIALOG_FORM );
        $this->_size[self::DIALOG_FORM]++;

        return $this;
    }

    /*
     * Add a title message
     * @param $msg string text to show in dialog
     * @return $this
     * @since return $this for chaining since Claroline 1.9.6
     */
    public function title( $msg )
    {
        $this->message( $msg, self::DIALOG_TITLE );
        $this->_size[self::DIALOG_TITLE]++;

        return $this;
    }

    /*
     * internal function used by helpers
     * @param $msg string text to show in dialog
     * @param $type type of message to be added
     * @return $this
     * @since return $this for chaining since Claroline 1.9.6
     */
    private function message( $msg, $type )
    {
        $this->_dialogBox[] = array( 'type' => $type, 'msg' => $msg );

        return $this;
    }

    /*
     * Set which style should the box have
     * @param $boxType string text to show in dialog
     * @return $this
     * @since return $this for chaining since Claroline 1.9.6
     */
    public function setBoxType( $boxType )
    {
        $this->_boxType = $boxType;

        return $this;
    }

    /*
     * returns html required to display the dialog box
     * @return string
     */
    public function render()
    {
        if( !empty($this->_dialogBox) )
        {
            $out = array();

            foreach ( $this->_dialogBox as $entry )
            {
                $type = $entry['type'];
                $msg = $entry['msg'];

                switch ( $type )
                {
                    case self::DIALOG_INFO:
                        $class = 'msgInfo';
                    break;

                    case self::DIALOG_SUCCESS:
                        $class = 'msgSuccess';
                    break;

                    case self::DIALOG_WARNING:
                        $class = 'msgWarning';
                    break;

                    case self::DIALOG_ERROR:
                        $class = 'msgError';
                    break;

                    case self::DIALOG_QUESTION:
                        $class = 'msgQuestion';
                    break;

                    case self::DIALOG_FORM:
                        // forms must always be in a div
                        $class = 'msgForm';
                    break;

                    case self::DIALOG_TITLE:
                        $class = 'msgTitle';
                    break;

                    default:
                        $class = 'msgMessage';
                    break;
                }

                $out[] = '<div class="claroDialogMsg ' . $class . '">' . $msg . '</div>';

                unset ($type, $msg );
            }

            switch( $this->_boxType )
            {
                case 'auto' :

                     // order is important first meet is choosed
                    if( $this->_size[self::DIALOG_ERROR] > 0 )
                    {
                        $boxClass = 'boxError';
                    }
                    elseif( $this->_size[self::DIALOG_WARNING] > 0 )
                    {
                        $boxClass = 'boxWarning';
                    }
                    elseif( $this->_size[self::DIALOG_SUCCESS] > 0 )
                    {
                        $boxClass = 'boxSuccess';
                    }
                    elseif( $this->_size[self::DIALOG_QUESTION] > 0 )
                    {
                        $boxClass = 'boxQuestion';
                    }
                    elseif( $this->_size[self::DIALOG_INFO] > 0 )
                    {
                        $boxClass = 'boxInfo';
                    }
                    else
                    {
                        $boxClass = '';
                    }

                break;

                case 'info' :
                    $boxClass = 'boxInfo';
                break;

                case 'success' :
                    $boxClass = 'boxSuccess';
                break;

                case 'warning' :
                    $boxClass = 'boxWarning';
                break;

                case 'error' :
                    $boxClass = 'boxError';
                break;

                case 'question' :
                    $boxClass = 'boxQuestion';
                break;

                default :
                    $boxClass = '';
                break;
            }

            // todo check that the floating div + spacer do not break design

            return '<div class="claroDialogBox ' . $boxClass . '">' . "\n"
                . implode( "\n", $out )
                . '</div>' . "\n\n"
                ;
        }
        else
        {
            return '';
        }
    }
}