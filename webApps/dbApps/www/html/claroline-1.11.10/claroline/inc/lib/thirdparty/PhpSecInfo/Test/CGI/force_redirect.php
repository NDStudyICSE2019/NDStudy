<?php
/**
 * Test class for cgi force_redirect
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */

/**
 * require the PhpSecInfo_Test_Cgi class
 */
require_once dirname(__FILE__) . '/../Test_Cgi.php';

/**
 * Test class for cgi force_redirect
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */
class PhpSecInfo_Test_Cgi_Force_Redirect extends PhpSecInfo_Test_Cgi
{

    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "force_redirect";


    /**
     * Checks to see if cgi.force_redirect is enabled
     *
     */
    function _execTest() {

        if ($this->getBooleanIniValue('cgi.force_redirect')) {
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

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', "force_redirect is enabled, which is the recommended setting");
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_WARN, 'en', "force_redirect is disabled.  In most cases, this is a <strong>serious</strong> security vulnerability.  Unless you are absolutely sure this is not needed, enable this setting");

    }

}