<?php
/**
 * Test class for GID
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
define ('PHPSECINFO_MIN_SAFE_GID', 100);

/**
 * Test class for GID
 *
 * @package PhpSecInfo
 */
class PhpSecInfo_Test_Core_Gid extends PhpSecInfo_Test_Core
{

    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "gid test";



    /**
     * Checks the GID of the PHP process to make sure it is above PHPSECINFO_MIN_SAFE_GID
     *
     * @see PHPSECINFO_MIN_SAFE_GID
     */
    function _execTest() {

        if (getmygid() >= PHPSECINFO_MIN_SAFE_GID) {
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

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', 'PHP is executing as what is probably a non-privileged group');
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_WARN, 'en', 'PHP may be executing as a "privileged" group, which could be a serious security vulnerability.');
    }


}