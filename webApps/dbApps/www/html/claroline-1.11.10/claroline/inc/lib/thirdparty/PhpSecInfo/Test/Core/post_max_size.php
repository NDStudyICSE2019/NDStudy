<?php
/**
 * Test Class for post_max_size
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */


/**
 * require the PhpSecInfo_Test_Core class
 */
require_once dirname(__FILE__) . '/../Test_Core.php';

/**
 * The max recommended size for the post_max_size setting, in bytes
 *
 */
define ('PHPSEC_POST_MAXLIMIT', 1024*256);

/**
 * Test Class for post_max_size
 *
 * @package PhpSecInfo
 */
class PhpSecInfo_Test_Core_Post_Max_Size extends PhpSecInfo_Test_Core
{

    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "post_max_size";


    /**
     * Check to see if the post_max_size setting is enabled.
     */
    function _execTest() {

        $uploads_max_filesize = ini_get('upload_max_filesize');

        if ($uploads_max_filesize
            && $this->returnBytes($uploads_max_filesize) < PHPSEC_POST_MAXLIMIT
            && $uploads_max_filesize != -1) {
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

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', 'post_max_size is enabled, and appears to
                be a relatively low value');
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_NOTICE, 'en', 'post_max_size is not enabled, or is set to
                a high value.  Allowing a large value may open up your server to denial-of-service attacks');
    }


}