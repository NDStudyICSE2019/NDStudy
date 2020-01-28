<?php
/**
 * Test class for session use_trans_sid
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */


/**
 * require the PhpSecInfo_Test_Session class
 */
require_once dirname(__FILE__) . '/../Test_Session.php';

/**
 * Test class for session use_trans_sid
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */
class PhpSecInfo_Test_Session_Use_Trans_Sid extends PhpSecInfo_Test_Session
{

    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "use_trans_sid";


    /**
     * The "meat" of the test.  This is where the real test code goes.  You should override this when extending
     *
     */
    function _execTest() {

        if (!$this->getBooleanIniValue('use_trans_sid')) {
            return PHPSECINFO_TEST_RESULT_OK;
        }

        return PHPSECINFO_TEST_RESULT_NOTICE;
    }


    /**
     * Set the messages specific to this test
     *
     */
    function _setMessages() {

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', 'use_trans_sid is disabled, which is the recommended setting');
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_NOTICE, 'en', 'use_trans_sid is enabled.  This makes session hijacking easier.  Consider disabling this feature');

    }


}