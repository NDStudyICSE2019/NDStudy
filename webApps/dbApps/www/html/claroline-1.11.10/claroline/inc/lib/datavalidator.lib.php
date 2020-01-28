<?php // $Id: datavalidator.lib.php 13302 2011-07-11 15:19:09Z abourguignon $

/**
 * CLAROLINE
 *
 * DataValidator class
 * Validates the content of data chained into an array according to a set
 * of defined rules. You can define your own validation rules (by creating
 * functions) but the class also provides a list of predefined rules.
 *
 * Example :
 *
 *   $validator = new DataValidator()
 *   $dataList = array('lastname'  => 'Doe',
 *                     'firstname' => 'John' ,
 *                     'email'     => 'doe@somewhere.net');
 *
 *   $validator->setDataList($dataList);
 *
 *   $validator->addRule('lastname' , 'Lastname is missing', 'required'  );
 *   $validator->addRule('firstname', 'Wrong First Name'   , 'lettersonly');
 *   $validator->addRule('email'    , 'Wrong email address', 'email'      );
 *
 *   if ( $validator->validate(DATAVALIDATOR_STRICT_MODE) )
 *   {
 *     ...
 *   }
 *   else
 *   {
 *     echo explode(', ', $validator->getErrorList() );
 *   }
 *
 * @version     $Revision: 13302 $
 * @copyright   (c) 2001-2011, Universite catholique de Louvain (UCL)
 * @author      Claroline Team <info@claroline.net>
 * @author      Hugues Peeters <hugues.peeters@advalvas.be>
 * @license     http://www.gnu.org/copyleft/gpl.html
 *              GNU GENERAL PUBLIC LICENSE version 2 or later
 * @package     KERNEL
 * @deprecated  since 1.9, use utils/validator.lib instead
 */

define ('DATAVALIDATOR_STRICT_MODE', true);

/**
 * @deprectaed since Claroline 1.9, use utils/validator.lib.php instead
 */
class DataValidator
{
    var $dataList           = array(),
        $errorList          = array(),
        $ruleNameList       = array(),
        $ruleParamList      = array(),
        $ruleRelDataKeyList = array(),
        $ruleRelErrorList   = array(),
        $requiredDataList   = array(),
        $requiredErrorList  = array();

    /**
     * @param array
     */

    function setDataList($dataList)
    {
        $this->dataList = $dataList;
    }

    /**
     * @return array
     */

    function getDataList()
    {
        return $this->dataList;
    }

    /**
     * @param string $dataKey
     * @param string $errorMessage returned if the data doesn't obey to the rule
     * @param mixed (string or ressource) $rule
     *        The validator class provides a predefined rules (listed below).
     *        It is possible to call them just by entering the string name.
     *        predefined rules : required, numeric, alphanumeric, lettersonly,
     *        regex, compare, nonzero, min, max, range, maxlength, minlength,
     *        rangelength, nopunctuation, email, ip, hostname
     * @param array $xtraParamList additional parameters required for the
     *                             function rule
     */

    function addRule($dataKey, $errorMessage, $rule, $xtraParamList = array() )
    {
        if ( 'required' == $rule )
        {
            // 'required' rule is a special case needing to be treated appart
            $this->requiredDataList[]  = $dataKey;
            $this->requiredErrorList[] = $errorMessage;
        }
        else
        {
            if ( ! is_array($xtraParamList) )
            {
                $xtraParamList = array( $xtraParamList);
            }

            $this->ruleNameList[]       = $rule;
            $this->ruleParamList[]      = $xtraParamList;
            $this->ruleRelDataKeyList[] = $dataKey;
            $this->ruleRelErrorList[]   = $errorMessage;
        }
    }


    /**
     * @param bool $strict 'true' apply every rule on every dataKey, even for
     *                      not required data
     *                     'false' (default) leave rule for emtpy data not required
     * @return boolean
     */

    function validate( $strict = false )
    {
        $this->wrongDataList    = array();
        $this->errorMessageList = array();

        // First, validate required keys

        foreach( $this->requiredDataList as $refKey => $dataKey )
        {
            if ( ! array_key_exists($dataKey, $this->dataList) )
            {
                $this->wrongDataList[]    = $dataKey;
                $this->errorMessageList[] = 'UNDEFINED INDEX <i>'.$dataKey.'</i>';
                continue;
            }

            if ( $this->rl_required($this->dataList[$dataKey]) == false )
            {
                $this->wrongDataList[]    = $dataKey;
                $this->errorMessageList[] = $this->requiredErrorList[$refKey];
            }
        }

        // Then, validate other key rules ...

        foreach( $this->ruleNameList as $ruleKey => $ruleName )
        {
            $dataKey   = $this->ruleRelDataKeyList[$ruleKey];

            if ( ! array_key_exists($dataKey, $this->dataList) )
            {
                $this->wrongDataList[]    = $dataKey;
                $this->errorMessageList[] = 'UNDEFINED INDEX <i>'.$dataKey.'</i>';
                continue;
            }

            if (    ! $strict
                 && ! in_array($dataKey, $this->requiredDataList )
                 && ! $this->rl_required($this->dataList[$dataKey]) )
            {
                // when strict mode is not activated, if element is empty and
                // not required we shouldn't validate it with other rules

                continue;
            }

            $dataValue = $this->dataList[$dataKey];

            $completeParamList = array_merge( array($dataValue), array_values($this->ruleParamList[$ruleKey]) );

            if ( method_exists( $this, 'rl_' . $ruleName) )
            {
                $callback = array( &$this, 'rl_' . $ruleName);
            }
            elseif ( function_exists($ruleName) )
            {
                $callback = $ruleName;
            }
            else
            {
                trigger_error('CALL TO UNDEFINED FUNCTION : ' . $ruleName, E_USER_WARNING);
                return false;
            }

            if ( call_user_func_array( $callback, $completeParamList) == false)
            {
                $this->wrongDataList[]    = $dataKey;
                $this->errorMessageList[] = $this->ruleRelErrorList[$ruleKey];
            }
        } // end foreach $this->ruleNameList as $ruleKey => $ruleName

        if ( count($this->wrongDataList) > 0 ) return false;
        else                                   return true;
    }

    /**
     * @return array
     */

    function getWrongDataKeyList()
    {
        return array_unique($this->wrongDataList);
    }

    /**
     * @return array
     */

    function getRightDataKeyList()
    {
        return array_values(array_diff( array_keys($this->dataList) ,
                                        $this->getWrongDataKeyList()
                                       )
                            );
    }

    /**
     * @param $dataKey filter returning only error messages
     *        related to this data key
     * @return array
     */

    function getErrorList( $dataKey = null )
    {
        if ( is_null ($dataKey))
        {
            return $this->errorMessageList;
        }
        else
        {
            $errorList = array();

            $errorMsgKeyList = array_keys($this->wrongDataList, $dataKey);

            foreach( $errorMsgKeyList as $thisErrorMsgKey )
            {
                $errorList[] = $this->errorMessageList[$thisErrorMsgKey];
            }

            return $errorList;
        }
    }

                            /** PREDEFINED RULES **/

    /**
     * Predefined validation rule
     * @param $data
     * @return boolean
     */

    function rl_required($data)
    {
        return trim($data) != '';
    }

    /**
     * Predefined validation rule
     * @param $data
     * @return boolean
     */

    function rl_numeric($data)
    {
        return is_numeric($data);
    }

    /**
     * Predefined validation rule
     * @param $data
     * @return boolean
     */

    function rl_alphanumeric($data)
    {
        return ctype_alnum($data);
    }

    /**
     * Predefined validation rule
     * @param $data
     * @return boolean
     */

    function rl_lettersonly($data)
    {
        return ctype_alpha($data);
    }

    /**
     * Predefined validation rule
     * @param $data
     * @return boolean
     */

    function rl_regex($data, $rx)
    {
        return preg_match($rx, $data);
    }

    /**
     * Predefined validation rule
     * @param $data
     * @param string $rx regular expression
     * @return boolean
     */

    function rl_compare($dataA, $dataB)
    {
        return $dataA == $dataB;
    }

    /**
     * Predefined validation rule
     * @param $data
     * @return boolean
     */

    function rl_nonzero($data)
    {
        return 0 != (int) $data;
    }

    /**
     * Predefined validation rule
     * @param $data
     * @param $value
     * @return boolean
     */

    function rl_min($data, $value)
    {
        return ( (float)$data ) >= ( (float)$value );
    }

    /**
     * Predefined validation rule
     * @param $data
     * @param $value
     * @return boolean
     */

    function rl_max($data, $value)
    {
        return ( (float)$data ) <= ( (float)$value );
    }

    /**
     * Predefined validation rule
     * @param $data
     * @param $min minimum value
     * @param $max maximum value
     * @return boolean
     */

    function rl_range($data, $min, $max)
    {
        return    $this->rl_min($data, $min) && $this->rl_max($data, $max);
    }

    /**
     * Predefined validation rule
     * @param string $data
     * @param int $length maximum number of characters
     * @return boolean
     */

    function rl_maxlength($data, $length)
    {
        return (strlen(trim($data))) <= (int) $length;
    }

    /**
     * Predefined validation rule
     * @param string $data
     * @param int $length minimum number of characters
     * @return boolean
     */

    function rl_minlength($data, $length)
    {
        return (strlen(trim($data))) >= (int)$length;
    }

    /**
     * Predefined validation rule
     * @param string $data
     * @param int $minlength minimum number of characters
     * @param int $maxlength maximum number of characters
     * @return boolean
     */

    function rl_rangelength($data, $minlength, $maxlength)
    {
        return   $this->rl_maxlength($data, $maxlength)
              && $this->rl_minlength($data, $minlength);
    }

    /**
     * Predefined validation rule
     * @param string $data
     * @return boolean
     */

    function rl_nopunctuation($data)
    {
        return ! preg_match('/[().\/*\\^?#!@$%+=,"\'><~\[\]{}"\']/', $data);
    }

    /**
     * Predefined validation rule
     * @param $data
     * @return boolean
     */

    function rl_email($data)
    {
        static $usernameRx = '/^((\"[^\"\f\n\r\t\v\b]+\")|([\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+(\.[\w\!\#\$\%\&\'\*\+\-\~\/\^\`\|\{\}]+)*))$/';

        $dataList = explode('@', $data);

        if ( count($dataList) != 2  )                  return false;
        if ( ! $this->rl_hostname($dataList[1]) )      return false;
        if ( ! preg_match($usernameRx, $dataList[0]) ) return false;

        return true;
    }

    /**
     * Predefined validation rule
     * @param $data
     * @return boolean
     */

    function rl_ip($data)
    {
        return (bool) ip2long($data);
    }

    /**
     * Predefined validation rule
     * @param $data
     * @return boolean
     */

    function rl_hostname($data)
    {
        static $domainNameRx = '/^(?:[^\W_](?:[^\W_]|-){0,61}[^\W_]\.)+[a-zA-Z]{2,6}\.?$/';
        static $localNameRx  = '/^(?:[^\W_](?:[^\W_]|-){0,61}[^\W_]\.)*(?:[^\W_](?:[^\W_]|-){0,61}[^\W_])\.?$/';

        return    $this->rl_ip($data)
               || preg_match($domainNameRx, $data)
               || preg_match($localNameRx, $data);
    }
}
