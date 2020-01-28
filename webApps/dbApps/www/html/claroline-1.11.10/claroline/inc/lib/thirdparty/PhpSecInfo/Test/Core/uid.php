<?php
/**
 * Test class for UID
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */


/**
 * require the PhpSecInfo_Test_Core class
 */
require_once dirname(__FILE__) . '/../Test_Core.php';


/**
 * the minimum "safe" UID that php should be executing as.  This can vary,
 * but in general 100 seems like a good min.
 *
 */
define ('PHPSECINFO_MIN_SAFE_UID', 100);

/**
 * Test class for UID
 *
 * @package PhpSecInfo
 */
class PhpSecInfo_Test_Core_Uid extends PhpSecInfo_Test_Core
{

    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "uid test";

    /**
     * Checks the UID of the PHP process to make sure it is above PHPSECINFO_MIN_SAFE_UID
     *
     * @see PHPSECINFO_MIN_SAFE_UID
     */
    function _execTest() {

        if (getmyuid() >= PHPSECINFO_MIN_SAFE_UID) {
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

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', 'PHP is executing as what is probably a non-privileged user');
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_WARN, 'en', 'PHP may be executing as a "privileged" user,
                which could be a serious security vulnerability.');
    }


}