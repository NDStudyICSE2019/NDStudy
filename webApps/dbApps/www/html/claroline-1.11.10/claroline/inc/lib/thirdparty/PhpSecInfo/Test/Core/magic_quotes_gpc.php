<?php
/**
 * Test Class for magic_quotes_gpc
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */



/**
 * require the PhpSecInfo_Test_Core class
 */
require_once dirname(__FILE__) . '/../Test_Core.php';

/**
 * Test Class for magic_quotes_gpc
 *
 * @package PhpSecInfo
 */
class PhpSecInfo_Test_Core_Magic_Quotes_GPC extends PhpSecInfo_Test_Core
{
    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "magic_quotes_gpc";


    /**
     * Checks to see if magic_quotes_gpc is enabled
     *
     */
    function _execTest() {

        if (!$this->getBooleanIniValue('magic_quotes_gpc')) {
            return PHPSECINFO_TEST_RESULT_OK;
        }

        return PHPSECINFO_TEST_RESULT_NOTICE;
    }


    /**
     * Set the messages specific to this test
     *
     */
    function _setMessages() {
        parent::_setMessages();

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', 'magic_quotes_gpc is disabled, which is the recommended setting');
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_NOTICE, 'en', 'magic_quotes_gpc is enabled.  This
                feature is inconsistent in blocking attacks, and can in some cases cause data loss with
                uploaded files.  You should <i>not</i> rely on magic_quotes_gpc to block attacks.  It is
                recommended that magic_quotes_gpc be disabled, and input filtering be handled by your PHP
                scripts');
    }


}