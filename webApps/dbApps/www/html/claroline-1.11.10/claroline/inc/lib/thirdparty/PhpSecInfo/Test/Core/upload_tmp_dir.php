<?php
/**
 * Test Class for upload_tmp_dir
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */

/**
 * require the PhpSecInfo_Test_Core class
 */
require_once dirname(__FILE__) . '/../Test_Core.php';

/**
 * Test Class for upload_tmp_dir
 *
 * @package PhpSecInfo
 */
class PhpSecInfo_Test_Core_Upload_Tmp_Dir extends PhpSecInfo_Test_Core
{

    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "upload_tmp_dir";

    var $_messages = array();

    /**
     * Check to see if the upload_tmp_dir setting is enabled.  If it is set, check if it matches PHPSECINFO_TEST_COMMON_TMPDIR
     *
     * The test for PHPSECINFO_TEST_COMMON_TMPDIR is pretty UNIX-specific, and should probably include other common world-writable
     * dirs from other OSes
     *
     * @see PHPSECINFO_TEST_COMMON_TMPDIR
     */
    function _execTest() {

        if (ini_get('upload_tmp_dir') && !preg_match("|".PHPSECINFO_TEST_COMMON_TMPDIR."/?|", ini_get('upload_tmp_dir'))) {
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

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', 'upload_tmp_dir is enabled, which is the
                        recommended setting. Make sure your upload_tmp_dir path is not world-readable');
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_NOTICE, 'en', 'upload_tmp_dir is disabled, or is set to a
                        common world-writable directory.  This typically allows other users on this server
                        to access temporary copies of files uploaded via your PHP scripts.  You should set
                        upload_tmp_dir to a non-world-readable directory');
    }

}