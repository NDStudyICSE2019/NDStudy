<?php
/**
 * Main class file
 *
 * Original @package PhpSecInfo
 * Original @author Ed Finkler <coj@funkatron.com>
 */


/**
 * The default language setting if none is set/retrievable
 *
 */
define ('PHPSECINFO_LANG_DEFAULT', 'en');

/**
 * a general version string to differentiate releases
 *
 */
define ('PHPSECINFO_VERSION', '0.1.1');

/**
 * a YYYYMMDD date string to indicate "build" date
 *
 */
define ('PHPSECINFO_BUILD', '20061023');

/**
 * This is the main class for the phpsecinfo system.  It's responsible for
 * dynamically loading tests, running those tests, and generating the results
 * output
 *
 * Example:
 * <code>
 * <?php require_once('lib/PhpSecInfo.lib.php'); ?>
 * <?php phpsecinfo(); ?>
 * </code>
 *
 * If you want to capture the output, or just grab the test results and display them
 * in your own way, you'll need to do slightly more work.
 *
 * Example:
 * <code>
 * require_once('lib/PhpSecInfo.lib.php');
 * // instantiate the class
 * $psi = new PhpSecInfo();
 *
 * // load and run all tests
 * $psi->loadAndRun();
 *
 * // grab the results as a multidimensional array
 * $results = $psi->getResultsAsArray();
 * echo "<pre>"; echo print_r($results, true); echo "</pre>";
 *
 * // grab the standard results output as a string
 * $html = $psi->getOutput();
 *
 * // send it to the browser
 * echo $html;
 * </code>
 *
 *
 * The procedural function "phpsecinfo" is defined below this class.
 * @see phpsecinfo()
 *
 * @author Ed Finkler <coj@funkatron.com>
 *
 * v0.1.1
 * - Added PhpSecInfo::getOutput(), PhpSecInfo::loadAndRun() and PhpSecInfo::getResultsAsArray() methods
 * - Modified PhpSecInfo::runTests() to fix undefined offsent notices
 * - Modified PhpSecInfo_Test::setMessageForResult() to fix undefined offset notices
 * - Modified PhpSecInfo_Test_Curl_File_Support to skip if PHP version is < 5 (detection of file protocol support relies on PHP5 version of curl_version)
 *
 * v0.1
 * - Initial public release
 *
 */
class PhpSecInfo
{

    /**
     * An array of tests to run
     *
     * @var array PhpSecInfo_Test
     */
    var $tests_to_run = array();

    /**
     * An array of results.  Each result is an associative array:
     * <code>
     * $result['result'] = PHPSECINFO_TEST_RESULT_NOTICE;
     * $result['message'] = "a string describing the test results and what they mean";
     * </code>
     *
     * @var array
     */
    var $test_results = array();


    /**
     * An array of tests that were not run
     *
     * <code>
     * $result['result'] = PHPSECINFO_TEST_RESULT_NOTRUN;
     * $result['message'] = "a string explaining why the test was not run";
     * </code>
     *
     * @var array
     */
    var $tests_not_run = array();


    /**
     * The language code used.  Defaults to PHPSECINFO_LANG_DEFAULT, which
     * is 'en'
     *
     * @var string
     * @see PHPSECINFO_LANG_DEFAULT
     */
    var $language = PHPSECINFO_LANG_DEFAULT;


    /**
     * An array of integers recording the number of test results in each category.  Categories can include
     * some or all of the PHPSECINFO_TEST_* constants.  Constants are the keys, # of results are the values.
     *
     * @var array
     */
    var $result_counts = array();


    /**
     * The number of tests that have been run
     *
     * @var integer
     */
    var $num_tests_run = 0;


    /**
     * Constructor
     *
     * @return PhpSecInfo
     */
    function PhpSecInfo() {

    }


    /**
     * recurses through the Test subdir and includes classes in each test group subdir,
     * then builds an array of classnames for the tests that will be run
     *
     */
    function loadTests() {

        $test_root = dir(dirname(__FILE__).DIRECTORY_SEPARATOR.'Test');

        //echo "<pre>"; echo print_r($test_root, true); echo "</pre>";

        while (false !== ($entry = $test_root->read())) {
            if ( is_dir($test_root->path.DIRECTORY_SEPARATOR.$entry) 
                && !preg_match( '|^\.(.*)$|', $entry ) 
                && !preg_match( '|^\.CVS$|i', $entry )
                && ( 'CVS' != $entry) ) 
            {
                $test_dirs[] = $entry;
            }
        }
        // echo "<pre>"; echo print_r($test_dirs, true); echo "</pre>";

        // include_once all files in each test dir
        foreach ($test_dirs as $test_dir) {
            
            $this_dir = dir($test_root->path.DIRECTORY_SEPARATOR.$test_dir);

            while (false !== ($entry = $this_dir->read())) {
                if (!is_dir($this_dir->path.DIRECTORY_SEPARATOR.$entry)) {
                    include_once $this_dir->path.DIRECTORY_SEPARATOR.$entry;
                    $classNames[] = "PhpSecInfo_Test_".$test_dir."_".basename($entry, '.php');
                }
            }

        }

        $this->tests_to_run =& $classNames;
    }


    /**
     * This runs the tests in the tests_to_run array
     *
     */
    function runTests() {
        // initialize a bunch of arrays
        $this->test_results  = array();
        $this->result_counts = array();
        $this->result_counts[PHPSECINFO_TEST_RESULT_NOTRUN] = 0;
        $this->num_tests_run = 0;

        /**
         * @var PhpSecInfo_Test
         */
        foreach ($this->tests_to_run as $testClass) {
            $test = new $testClass();

            if ($test->isTestable()) {
                $test->test();
                $rs = array('result' => $test->getResult(), 'message' => $test->getMessage());
                $this->test_results[$test->getTestGroup()][$test->getTestName()] = $rs;

                // initialize if not yet set
                if (!isset ($this->result_counts[$rs['result']]) ) {
                    $this->result_counts[$rs['result']] = 0;
                }

                $this->result_counts[$rs['result']]++;
                $this->num_tests_run++;
            } else {
                $rs = array('result' => $test->getResult(), 'message' => $test->getMessage());
                $this->result_counts[PHPSECINFO_TEST_RESULT_NOTRUN]++;
                $this->tests_not_run[$test->getTestGroup()."::".$test->getTestName()] = $rs;
            }
        }
    }


    /**
     * This is the main output method.  The look and feel mimics phpinfo()
     *
     */
    function renderOutput() {

        /**
         * We need to use PhpSecInfo_Test::getBooleanIniValue() below
         * @see PhpSecInfo_Test::getBooleanIniValue()
         */
        require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'Test'.DIRECTORY_SEPARATOR.'Test.php');


?>
<div class="center">
<table border="0" cellpadding="3" width="600">
<tr class="h"><td>
<h1 class="p">
<?php if ( PhpSecInfo_Test::getBooleanIniValue('expose_php') ) : ?>
<a href="http://www.php.net/"><img border="0" src="<?php echo '?=' . php_logo_guid() ?>" alt="PHP Logo" /></a>
<?php endif; ?>
PHP Environment Security Info
</h1>
<h2 class="p">Version <?php echo PHPSECINFO_VERSION ?>; build <?php echo PHPSECINFO_BUILD ?></h2>
</td></tr>
</table>
<br />
        <?php
            foreach ($this->test_results as $group_name=>$group_results) {
                $this->_outputRenderTable($group_name, $group_results);
            }

            $this->_outputRenderNotRunTable();

            $this->_outputRenderStatsTable();

            ?>

</div>
        <?php
    }


    /**
     * This is a helper method that makes it easy to output tables of test results
     * for a given test group
     *
     * @param string $group_name
     * @param array $group_results
     */
    function _outputRenderTable($group_name, $group_results) {

        // exit out if $group_results was empty or not an array.  This sorta seems a little hacky...
        if (!is_array($group_results) || sizeof($group_results) < 1) {
            return false;
        }

        ksort($group_results);

        ?>
        <h2><?php echo claro_htmlspecialchars($group_name, ENT_QUOTES) ?></h2>

        <table border="0" cellpadding="3" width="600">
        <tr class='h'>
            <th>Test</th>
            <th>Result</th>
        </tr>
        <?php foreach ($group_results as $test_name=>$test_results): ?>

        <tr>
            <td class="e"><?php echo claro_htmlspecialchars($test_name, ENT_QUOTES) ?></td>
            <td class="<?php echo $this->_outputGetCssClassFromResult($test_results['result']) ?>">
                <?php echo $test_results['message'] ?>
            </td>
        </tr>

        <?php endforeach; ?>
        </table><br />

        <?php
        return true;
    }



    /**
     * This outputs a table containing a summary of the test results (counts and % in each result type)
     *
     * @see PHPSecInfo::_outputRenderTable()
     * @see PHPSecInfo::_outputGetResultTypeFromCode()
     */
    function _outputRenderStatsTable() {

        foreach($this->result_counts as $code=>$val) {
            if ($code != PHPSECINFO_TEST_RESULT_NOTRUN) {
                $percentage = round($val/$this->num_tests_run * 100,2);

                $stats[$this->_outputGetResultTypeFromCode($code)] = array( 'count' => $val,
                                                                'result' => $code,
                                                                'message' => "$val out of {$this->num_tests_run} ($percentage%)");
            }
        }

        $this->_outputRenderTable('Test Results Summary', $stats);

    }



    /**
     * This outputs a table containing a summary or test that were not executed, and the reasons why they were skipped
     *
     * @see PHPSecInfo::_outputRenderTable()
     */
    function _outputRenderNotRunTable() {

        $this->_outputRenderTable('Tests Not Run', $this->tests_not_run);

    }




    /**
     * This is a helper function that returns a CSS class corresponding to
     * the result code the test returned.  This allows us to color-code
     * results
     *
     * @param integer $code
     * @return string
     */
    function _outputGetCssClassFromResult($code) {

        switch ($code) {
            case PHPSECINFO_TEST_RESULT_OK:
                return 'v-ok';
                break;

            case PHPSECINFO_TEST_RESULT_NOTICE:
                return 'v-notice';
                break;

            case PHPSECINFO_TEST_RESULT_WARN:
                return 'v-warn';
                break;

            case PHPSECINFO_TEST_RESULT_NOTRUN:
                return 'v-notrun';
                break;

            case PHPSECINFO_TEST_RESULT_ERROR:
                return 'v-error';
                break;

            default:
                return 'v-notrun';
                break;
        }

    }



    /**
     * This is a helper function that returns a label string corresponding to
     * the result code the test returned.  This is mainly used for the Test
     * Results Summary table.
     *
     * @see PHPSecInfo::_outputRenderStatsTable()
     * @param integer $code
     * @return string
     */
    function _outputGetResultTypeFromCode($code) {

        switch ($code) {
            case PHPSECINFO_TEST_RESULT_OK:
                return 'Passed';
                break;

            case PHPSECINFO_TEST_RESULT_NOTICE:
                return 'Notices';
                break;

            case PHPSECINFO_TEST_RESULT_WARN:
                return 'Warnings';
                break;

            case PHPSECINFO_TEST_RESULT_NOTRUN:
                return 'Not Run';
                break;

            case PHPSECINFO_TEST_RESULT_ERROR:
                return 'Errors';
                break;

            default:
                return 'Invalid Result Code';
                break;
        }

    }


    /**
     * Loads and runs all the tests
     *
     * As loading, then running, is a pretty common process, this saves a extra method call
     *
     * @since 0.1.1
     *
     */
    function loadAndRun() {
        $this->loadTests();
        $this->runTests();
    }


    /**
     * returns an associative array of test data.  Four keys are set:
     * - test_results  (array)
     * - tests_not_run (array)
     * - result_counts (array)
     * - num_tests_run (integer)
     *
     * note that this must be called after tests are loaded and run
     *
     * @since 0.1.1
     * @return array
     */
    function getResultsAsArray() {
        $results = array();

        $results['test_results'] = $this->test_results;
        $results['tests_not_run'] = $this->tests_not_run;
        $results['result_counts'] = $this->result_counts;
        $results['num_tests_run'] = $this->num_tests_run;

        return $results;
    }



    /**
     * returns the standard output as a string instead of echoing it to the browser
     *
     * note that this must be called after tests are loaded and run
     *
     * @since 0.1.1
     *
     * @return string
     */
    function getOutput() {
        ob_start();
        $this->renderOutput();
        $output = ob_get_clean();
        return $output;
    }




}




/**
 * A globally-available function that runs the tests and creates the result page
 *
 */
function phpsecinfo() {
    ini_set('display_errors', 'On');
    error_reporting(E_ALL);

    $psi = new PhpSecInfo();
    $psi->loadAndRun();
    $psi->renderOutput();
}

