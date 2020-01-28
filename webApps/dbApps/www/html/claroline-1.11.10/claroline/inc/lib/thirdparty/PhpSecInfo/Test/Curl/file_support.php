<?php
/**
 * Test class for CURL file_support
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */

/**
 * require the PhpSecInfo_Test_Curl class
 */
 require_once dirname(__FILE__) . '/../Test_Curl.php';

/**
 * Test class for CURL file_support
 *
 * Checks for CURL file:// support; if this is installed, it can be used to bypass
 * safe_mode and open_basedir
 *
 * @todo I believe this hole was plugged in PHP 5.1.5 and 4.4.4(?).  This test should be updated to take this into consideration (check the version and decide what to do)
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */
class PhpSecInfo_Test_Curl_File_Support extends PhpSecInfo_Test_Curl
{

    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "file_support";


    /**
     * Checks to see if libcurl's "file://" support is enabled by examining the "protocols" array
     * in the info returned from curl_version()
     * @return integer
     *
     */
    function _execTest() {

        $curlinfo = curl_version();

        if (!in_array('file', $curlinfo['protocols'])) {
            return PHPSECINFO_TEST_RESULT_OK;
        } else {
            return PHPSECINFO_TEST_RESULT_WARN;
        }

    }



    /**
     * Set the messages specific to this test
     *
     */
    function _setMessages() {
        parent::_setMessages();

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', "file:// support in CURL seems to be disabled");
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_WARN, 'en', "libcurl's file:// support is enabled.  This can be used to bypass safe mode and open_basedir restrictions.  libcurl should be re-compiled with file:// support disabled");

    }

}