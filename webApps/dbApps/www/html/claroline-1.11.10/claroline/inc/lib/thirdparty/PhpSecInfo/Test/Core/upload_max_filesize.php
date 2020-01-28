<?php
/**
 * Test Class for upload_max_filesize
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */

/**
 * require the PhpSecInfo_Test_Core class
 */
require_once dirname(__FILE__) . '/../Test_Core.php';

/**
 * The max recommended size for the upload_max_filesize setting, in bytes
 *
 */
define ('PHPSEC_UPLOAD_MAXLIMIT', 1024*256);


/**
 * Test Class for upload_max_filesize
 *
 * @package PhpSecInfo
 */
class PhpSecInfo_Test_Core_Upload_Max_Filesize extends PhpSecInfo_Test_Core
{


    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "upload_max_filesize";

    /**
     * Check to see if the upload_max_filesize setting is enabled.
     */
    function _execTest() {

        $uploads_max_filesize = ini_get('upload_max_filesize');

        if ($uploads_max_filesize
            && $this->returnBytes($uploads_max_filesize) < PHPSEC_UPLOAD_MAXLIMIT
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

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', 'upload_max_filesize is enabled, and appears to be a relatively low value.');
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_NOTICE, 'en', 'upload_max_filesize is not enabled, or is set to a high value.  Are you sure your apps require uploading files of this size?  If not, lower the limit, as large file uploads can impact server performance');
    }


}