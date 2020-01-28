<?php // vim: expandtab sw=4 ts=4 sts=4:

/**
 * Random password generator
 *
 * @version     1.0
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Christophe Gesche <moosh@claroline.net>
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @license     http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU AFFERO GENERAL PUBLIC LICENSE version 3
 */

define ( 'MK_PASSWORD_DEFAULT_LENGTH', 12 );
define ( 'MK_PASSWORD_MIN_LENGTH', 8 );

/**
 * Generate random password with some security inside
 * @param       int $length number of characters (min 8, default 12);
 * @return      string password
 * @author      Christophe Gesche <moosh@claroline.net>
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 */
function mk_password( $length = MK_PASSWORD_DEFAULT_LENGTH )
{
    // ASSERTIONS
    assert( 'is_numeric($length)' );
    assert( '$length >= MK_PASSWORD_MIN_LENGTH' );
    
    // avoid infinite loop and too weak password !!!!
    if ( $length < MK_PASSWORD_MIN_LENGTH )
    {
        $length = MK_PASSWORD_MIN_LENGTH;
    }
    
    $lettre = array();

    $lettre[0] = array( 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
    'j', 'k', 'm', 'n', 'p', 'q', 'r',
    's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A',
    'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J',
    'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'D',
    'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '9',
    '6', '5', '1', '3');

    $lettre[1] =  array( '@', '!', '(', ')', 'a', 'e', 'o', 'u', 'y', 'A', 'E',
    'U', 'Y' , '1', '3',  '4', '@', '!', '(', ')' );

    $lettre[-1] = array('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k',
    'm', 'n', 'p', 'q', 'r', 's', 't',
    'v', 'w', 'x', 'z', 'B', 'C', 'D', 'F',
    'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P',
    'Q', 'R', 'S', 'T', 'V', 'W', 'X', 'Z',
    '5', '6', '9', '@', '!', 4, 5, 6, 7, 8, 9);

    $retour   = '';
    $prec     = 1;
    $precprec = -1;
    
    // the following line is not needed anymore since PHP 4.2.0
    // srand((double)microtime() * 20001107);

    while(strlen($retour) < $length)
    {
        /*
            To generate the password string we follow these rules :
            
                (1) If two letters are consonnance (resp. a vowel), the
                following one have to be a vowel (resp. consonnance)
                (2) If letters are from different type, we choose a letter
                from the alphabet.
        */

        $type     = ($precprec + $prec) / 2;
        $r        = $lettre[$type][array_rand($lettre[$type], 1)];
        $retour  .= $r;
        $precprec = $prec;
        $prec     = in_array($r, $lettre[-1]) - in_array($r, $lettre[1]);

    }
    
    return $retour;
}
