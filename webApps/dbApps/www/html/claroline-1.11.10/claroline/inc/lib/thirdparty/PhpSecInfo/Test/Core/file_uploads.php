<?php
/**
 * Test Class for file_uploads
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */


/**
 * require the PhpSecInfo_Test_Core class
 */
require_once dirname(__FILE__) . '/../Test_Core.php';

/**
 * Test Class for file_uploads
 *
 * @package PhpSecInfo
 */
class PhpSecInfo_Test_Core_File_Uploads extends PhpSecInfo_Test_Core
{

    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "file_uploads";


    /**
     * Check to see if the file_uploads setting is enabled.
     */
    function _execTest() {

        if (!$this->getBooleanIniValue('file_uploads')) {
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

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', 'file_uploads are disabled.  Unless you\'re sure you need them, this is the recommended setting');
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_NOTICE, 'en', 'file_uploads are enabled.  If you do not require file upload capability, consider disabling them.');
    }


}