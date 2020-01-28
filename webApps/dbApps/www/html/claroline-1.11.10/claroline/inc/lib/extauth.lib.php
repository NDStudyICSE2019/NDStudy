<?php // $Id: extauth.lib.php 13302 2011-07-11 15:19:09Z abourguignon $

/**
 * CLAROLINE
 *
 * External Authentication library
 *
 * @version     $Revision: 13302 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 */


/**
 * This class is mainly a bridge between the claroline system
 * and the PEAR Auth library. It allows to use external authentication system
 * for claroline login process
 *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @deprecated  since Claroline 1.9, use AuthManager and AuthDriver instead
 */
class ExternalAuthentication
{
    var $auth; // auth container

    /**
     * constructor.
     *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
     * @param string $extAuthType
     * @param string $authOptionList
     */
    function ExternalAuthentication($extAuthType, $authOptionList,
                                    $formFieldList = array('username' => 'login',
                                                           'password' => 'password'))
    {
        // Auth library expects HTTP POST request with 'password' and 'username'
        // keys. The Claroline authentication form uses 'login' and 'password'.
        // The following line joins 'login' and 'password' enabling Auth to work
        // properly

        $_POST['username'] = $GLOBALS[ $formFieldList['username'] ];
        $_POST['password'] = $GLOBALS[ $formFieldList['password'] ];

        if ($extAuthType == 'LDAP')
        {
            // CASUAL PATCH (Nov 21 2005) : due to a sort of bug in the
            // PEAR AUTH LDAP container, we add a specific option wich forces
            // to return attributes to a format compatible with the attribute
            // format of the other AUTH containers

            $authOptionList ['attrformat'] = 'AUTH';
        }

        require_once('Auth/Auth.php');

        $this->auth = new Auth($extAuthType, $authOptionList,'', false);

        $this->auth->start();
    }

    function setAuthSourceName($authSourceName)
    {
        $this->authSourceName = $authSourceName;
    }



    /**
     * check if user is authenticated
     *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
     * @return
     */
    function isAuth()
    {
        return $this->auth->getAuth();
    }

    /**
     * record user data into the claroline system
     *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
     * @param array $extAuthAttribNameList - list that make correspondance
     *        between claroline attribute names and the external authentication
     *        system attribute name
     * @param array $extAttribTreatmentList list of preliminary treatment before
     *        submitting the attribute values to the claroline system. Each
     *        claroline attributes destination can have its own preliminary
     *        treatment
     * @param int $uid (optional) user id if the user is already registered to
     *        claroline
     * @return
     */
    function recordUserData($extAuthAttribNameList, $extAuthAttribTreatmentList, $uid = false)
    {
        /* Default initialisation of user attributes
         * It will be progressively filled by the foreach loop below
         */

        $userAttrList = array('lastname'     => NULL,
                              'firstname'    => NULL,
                              'loginName'    => NULL,
                              'email'        => NULL,
                              'officialCode' => NULL,
                              'phoneNumber'  => NULL,
                              'isCourseCreator' => NULL,
                              'authSource'   => NULL);

        foreach($extAuthAttribNameList as $claroAttribName => $extAuthAttribName)
        {
            if ( ! is_null($extAuthAttribName) )
            {
                $userAttrList[$claroAttribName] = $this->auth->getAuthData($extAuthAttribName);
            }
        }

        /* Possible preliminary treatment before recording */

        foreach($userAttrList as $claroAttribName => $claroAttribValue)
        {
            if ( array_key_exists($claroAttribName, $extAuthAttribTreatmentList ) )
            {
                $treatmentName = $extAuthAttribTreatmentList[$claroAttribName];

                if ( function_exists( (string)$treatmentName ) )
                {
                    $claroAttribValue = $treatmentName($claroAttribValue);
                }
                else
                {
                    $claroAttribValue = $treatmentName;
                }
            }

            $userAttrList[$claroAttribName] = $claroAttribValue;
        } // end foreach

        /* Two fields retrieving info from another source ... */

        $userAttrList['loginName' ] = $this->auth->getUsername();
        $userAttrList['authSource'] = $this->authSourceName;

        /* Data record */

        $userTbl = claro_sql_get_main_tbl();

        $dbFieldToClaroMap = array('nom'          => 'lastname',
                                   'prenom'       => 'firstname',
                                   'username'     => 'loginName',
                                   'email'        => 'email',
                                   'officialCode' => 'officialCode',
                                   'phoneNumber'  => 'phoneNumber',
                                   'isCourseCreator' => 'isCourseCreator',
                                   'authSource'   => 'authSource');
        $sqlPrepareList = array();

        // Status 1 == IsCourseCreator true

        if ( isset($userAttrList['status']) )
        {
            if ( $userAttrList['status'] == 1 ) $userAttrList['isCourseCreator'] = 1;
            else                                $userAttrList['isCourseCreator'] = 0;
        }

        foreach($dbFieldToClaroMap as $dbFieldName => $claroAttribName)
        {
            if ( ! is_null($userAttrList[$claroAttribName]) )
            {
                $sqlPrepareList[] = $dbFieldName. ' = "'.claro_sql_escape($userAttrList[$claroAttribName]).'"';
            }
        }

        // TODO use user.lib.php

        $sql = ($uid ? 'UPDATE' : 'INSERT INTO') . " `".$userTbl['user']."` "
              ."SET ".implode(', ', $sqlPrepareList)
              .($uid ? 'WHERE user_id = '.(int)$uid : '');

        $res  = mysql_query($sql)
                or die('<center>UPDATE QUERY FAILED LINE '.__LINE__.'<center>');

        if ($uid) $this->uid = $uid;
        else      $this->uid = mysql_insert_id();
    }

    /**
     * get the current uid of the logged usser
     *
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
     * @return int
     */
    function getUid()
    {
        return $this->uid;
    }
}
