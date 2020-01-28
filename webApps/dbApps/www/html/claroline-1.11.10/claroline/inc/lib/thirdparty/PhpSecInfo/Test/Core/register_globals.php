<?php
/**
 * Test Class for register_globals
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */


/**
 * require the PhpSecInfo_Test_Core class
 */
require_once dirname(__FILE__) . '/../Test_Core.php';


/**
 * Test Class for register_globals
 *
 * @package PhpSecInfo
 */
class PhpSecInfo_Test_Core_Register_Globals extends PhpSecInfo_Test_Core
{

    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "register_globals";


    /**
     * Checks to see if register_globals is enabled
     *
     */
    function _execTest() {


        if (!$this->getBooleanIniValue('register_globals')) {
            return PHPSECINFO_TEST_RESULT_OK;
        }

        return PHPSECINFO_TEST_RESULT_WARN;
    }


    /**
     * Set the messages specific to this test
     *
     */
    function _setMessages() {
        parent::_setMessages();

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', 'register_globals is disabled, which is the recommended setting');
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_WARN, 'en', 'register_globals is enabled.  This could be a serious security risk.  You should disable register_globals immediately');
    }


}