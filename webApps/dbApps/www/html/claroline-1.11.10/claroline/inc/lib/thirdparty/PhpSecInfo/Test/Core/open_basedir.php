<?php
/**
 * Test Class for open_basedir
 *
 * @package PhpSecInfo
 * @author Ed Finkler <coj@funkatron.com>
 */


/**
 * require the PhpSecInfo_Test_Core class
 */
require_once dirname(__FILE__) . '/../Test_Core.php';

/**
 * Test Class for open_basedir
 *
 * @package PhpSecInfo
 */
class PhpSecInfo_Test_Core_Open_Basedir extends PhpSecInfo_Test_Core
{

    /**
     * This should be a <b>unique</b>, human-readable identifier for this test
     *
     * @var string
     */
    var $test_name = "open_basedir";

    var $_messages = array(
        PHPSECINFO_TEST_RESULT_OK => array(
                    'en' => 'open_basedir is enabled, which is the recommended setting. Keep in mind that other web
                    applications not written in PHP will not be restricted by this setting.',
                    ),
        PHPSECINFO_TEST_RESULT_NOTICE => array(
                    'en' => 'open_basedir is disabled.  When this is enabled, only files that are in the given directory/directories and
                    their subdirectories can be read by PHP scripts.  You should consider turning this on.  Keep in mind that other web
                    applications not written in PHP will not be restricted by this setting.',
                    ),
        );

    /**
     * Check to see if the open_basedir setting is enabled.
     *
     */
    function _execTest() {

        if ($this->getBooleanIniValue('open_basedir')) {
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

        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_OK, 'en', 'open_basedir is enabled, which is the
                recommended setting. Keep in mind that other web applications not written in PHP will not
                be restricted by this setting.');
        $this->setMessageForResult(PHPSECINFO_TEST_RESULT_NOTICE, 'en', 'open_basedir is disabled.  When
                    this is enabled, only files that are in the
                    given directory/directories and their subdirectories can be read by PHP scripts.
                    You should consider turning this on.  Keep in mind that other web applications not
                    written in PHP will not be restricted by this setting.');
    }


}