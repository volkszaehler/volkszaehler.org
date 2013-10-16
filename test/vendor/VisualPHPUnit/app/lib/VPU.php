<?php

namespace app\lib;

class VPU {

   /**
    * The error messages collected by the custom error handler.
    *
    * @var array
    * @access protected
    */
    protected $_errors = array();

   /**
    * Adds percentage statistics to the provided statistics.
    *
    * @param array $statistics    The statistics.
    * @access protected
    * @return array
    */
    protected function _add_percentages($statistics) {
        $results = array();
        foreach ( $statistics as $name => $stats ) {
            $results[$name] = $stats;
            foreach ( $stats as $key => $value ) {
                if ( $key == 'total' ) {
                    continue;
                }
                // Avoid divide by zero error
                if ( $stats['total'] ) {
                    $results[$name]['percent' . ucfirst($key)] =
                        round($stats[$key] / $stats['total'] * 100, 1);
                } else {
                    $results[$name]['percent' . ucfirst($key)] = 0;
                }
            }
        }

        return $results;
    }

   /**
    * Returns the class name without the namespace.
    *
    * @param string $class    The class name.
    * @access protected
    * @return string
    */
    protected function _classname_only($class) {
        $name = explode('\\', $class);
        return end($name);
    }

   /**
    * Organizes the output from PHPUnit into a more manageable array
    * of suites and statistics.
    *
    * @param string $pu_output    The JSON output from PHPUnit.
    * @param string $source       The executing source (web or cli).
    * @access public
    * @return array
    */
    public function compile_suites($pu_output, $source) {
        $results = $this->_parse_output($pu_output);

        $collection = array();
        $statistics = array(
            'suites' => array(
                'succeeded'  => 0,
                'skipped'    => 0,
                'incomplete' => 0,
                'failed'     => 0,
                'total'      => 0
            )
        );
        $statistics['tests'] = $statistics['suites'];
        foreach ( $results as $result ) {
            if ( !isset($result['event']) || $result['event'] != 'test' ) {
                continue;
            }

            $suite_name = $this->_classname_only($result['suite']);

            if ( !isset($collection[$suite_name]) ) {
                $collection[$suite_name] = array(
                    'tests'  => array(),
                    'name'   => $suite_name,
                    'status' => 'succeeded',
                    'time'   => 0
                );
            }
            $result = $this->_format_test_results($result, $source);
            $collection[$suite_name]['tests'][] = $result;
            $collection[$suite_name]['status'] = $this->_get_suite_status(
                $result['status'], $collection[$suite_name]['status']
            );
            $collection[$suite_name]['time'] += $result['time'];
            $statistics['tests'][$result['status']] += 1;
            $statistics['tests']['total'] += 1;
        }

        foreach ( $collection as $suite ) {
            $statistics['suites'][$suite['status']] += 1;
            $statistics['suites']['total'] += 1;
        }

        $final = array(
            'suites' => $collection,
            'stats'  => $this->_add_percentages($statistics)
        );

        return $final;
    }

   /**
    * Converts the first nested layer of PHPUnit-generated JSON to an
    * associative array.
    *
    * @param string $str    The JSON output from PHPUnit.
    * @access protected
    * @return array
    */
    protected function _convert_json($str) {
        $str = str_replace('&quot;', '"', $str);

        $tags = array();
        $nest = 0;
        $start_mark = 0;
        $in_quotes = false;

        $length = strlen($str);
        for ( $i = 0; $i < $length; $i++ ) {
            $char = $str{$i};

            if ( $char == '"' ) {
                // Escaped quote in debug output
                if ( !$in_quotes || $str{$i - 1} == "\\" ) {
                    $i = strpos($str, '"', $i + 1) - 1;
                    $in_quotes = true;
                } else {
                    $in_quotes = false;
                }
                continue;
            }

            if ( $char == '{' ) {
                $nest++;
                if ( $nest == 1 ) {
                    $start_mark = $i;
                }
            } elseif ( $char == '}' && $nest > 0 ) {
                if ( $nest == 1 ) {
                    $tags[] = substr(
                        $str, $start_mark + 1, $i - $start_mark - 1
                    );
                    $start_mark = $i;
                }
                $nest--;
            }
        }

        return $tags;
    }

   /**
    * Normalizes the test results.
    *
    * @param array $test_results    The parsed test results.
    * @param string $source         The executing source (web or cli).
    * @access protected
    * @return string
    */
    protected function _format_test_results($test_results, $source) {
        $status = $this->_get_test_status(
            $test_results['status'], $test_results['message']
        );
        $name = substr(
            $test_results['test'], strpos($test_results['test'], '::') + 2
        );
        $time = $test_results['time'];
        $message = $test_results['message'];
        $output = ( isset($test_results['output']) )
            ? trim($test_results['output'])
            : '';
        $trace = $this->_get_trace($test_results['trace'], $source);

        return compact(
            'status',
            'name',
            'time',
            'message',
            'output',
            'trace'
        );
    }

   /**
    * Returns the errors collected by the custom error handler.
    *
    * @access public
    * @return array
    */
    public function get_errors() {
        return $this->_errors;
    }

   /**
    * Determines the overall suite status based on the current status
    * of the suite and the status of a single test.
    *
    * @param string $test_status     The status of the test.
    * @param string $suite_status    The current status of the suite.
    * @access protected
    * @return string
    */
    protected function _get_suite_status($test_status, $suite_status) {
        if (
            $test_status === 'incomplete' && $suite_status !== 'failed'
            && $suite_status !== 'skipped'
        ) {
            return 'incomplete';
        }
        if ( $test_status === 'skipped' && $suite_status !== 'failed' ) {
            return 'skipped';
        }
        if ( $test_status === 'failed' ) {
            return 'failed';
        }
        return $suite_status;
    }

   /**
    * Retrieves the status from a PHPUnit test result.
    *
    * @param string $status     The status supplied by VPU's transformed JSON.
    * @param string $message    The message supplied by VPU's transformed JSON.
    * @access protected
    * @return string
    */
    protected function _get_test_status($status, $message) {
        switch ( $status ) {
            case 'pass':
                return 'succeeded';
            case 'error':
                if ( stripos($message, 'skipped') !== false ) {
                    return 'skipped';
                }
                if ( stripos($message, 'incomplete') !== false ) {
                    return 'incomplete';
                }
                return 'failed';
            case 'fail':
                return 'failed';
            default:
                return '';
        }
    }

   /**
    * Filters the stack trace from a PHPUnit test result to exclude VPU's
    * trace.
    *
    * @param string $stack      The stack trace.
    * @param string $source     The executing source (web or cli).
    * @access protected
    * @return string
    */
    protected function _get_trace($stack, $source) {
        if ( !$stack ) {
            return '';
        }

        ob_start();
        if ( $source == 'web' ) {
            print_r(array_slice($stack, 0, -6));
        } else {
            print_r(array_slice($stack, 0, -2));
        }
        $trace = trim(ob_get_contents());
        ob_end_clean();

        return $trace;
    }

   /**
    * Serves as the error handler.
    *
    * @param int $number        The level of the error raised.
    * @param string $message    The error message.
    * @param string $file       The file in which the error was raised.
    * @param int $line          The line number at which the error was raised.
    * @access public
    * @return bool
    */
    public function handle_errors($number, $message, $file, $line) {
        if ( $number > error_reporting() ) {
            return true;
        }

        switch ( $number ) {
            case E_WARNING:
                $type = 'E_WARNING';
                break;
            case E_NOTICE:
                $type = 'E_NOTICE';
                break;
            case E_USER_ERROR:
                $type = 'E_USER_ERROR';
                break;
            case E_USER_WARNING:
                $type = 'E_USER_WARNING';
                break;
            case E_USER_NOTICE:
                $type = 'E_USER_NOTICE';
                break;
            case E_STRICT:
                $type = 'E_STRICT';
                break;
            case E_RECOVERABLE_ERROR:
                $type = 'E_RECOVERABLE_ERROR';
                break;
            case E_DEPRECATED:
                $type = 'E_DEPRECATED';
                break;
            case E_USER_DEPRECATED:
                $type = 'E_USER_DEPRECATED';
                break;
            default:
                $type = 'Unknown';
                break;
        }
        $this->_errors[] = compact('type', 'message', 'file', 'line');
        return true;
    }

   /**
    * Parses and formats the JSON output from PHPUnit into an associative array.
    *
    * @param string $pu_output    The JSON output from PHPUnit.
    * @access protected
    * @return array
    */
    protected function _parse_output($pu_output) {
        $results = '';
        foreach ( $this->_convert_json($pu_output) as $elem ) {
            $elem = '{' . $elem . '}';
            $pos = strpos($pu_output, $elem);
            $pu_output = substr_replace($pu_output, '|||', $pos, strlen($elem));
            $results .= $elem . ',';
        }

        $results = '[' . rtrim($results, ',') . ']';

        $results = json_decode($results, true);

        // For PHPUnit 3.5.x, which doesn't include test output in the JSON
        $pu_output = explode('|||', $pu_output);
        foreach ( $pu_output as $key => $data ) {
            if ( $data ) {
                $results[$key]['output'] = $data;
            }
        }

        return $results;
    }

   /**
    * Retrieves the files from any supplied directories, and filters
    * the list of tests by ensuring that the files exist and are PHP files.
    *
    * @param array $tests    The directories/filenames containing the tests to
    *                        be run through PHPUnit.
    * @access protected
    * @return array
    */
    protected function _parse_tests($tests) {
        $collection = array();

        foreach ( $tests as $test )  {
            if ( is_dir($test) ) {
                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(realpath($test)),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                while ( $it->valid() ) {
                    $ext = strtolower(pathinfo($it->key(), PATHINFO_EXTENSION));
                    if ( !$it->isDot() && $ext == 'php' ) {
                        $collection[] = $it->key();
                    }

                    $it->next();
                }
                continue;
            }

            $ext = strtolower(pathinfo($test, PATHINFO_EXTENSION));
            if ( file_exists($test) && $ext == 'php' )  {
                $collection[] = realpath($test);
            }
        }
        // Avoid returning duplicates
        return array_keys(array_flip($collection));
    }

   /**
    * Runs supplied tests through PHPUnit.
    *
    * @param array $tests    The directories/filenames containing the tests
    *                        to be run through PHPUnit.
    * @access public
    * @return string
    */
    public function run_tests($tests) {
        $suite = new \PHPUnit_Framework_TestSuite();

        $tests = $this->_parse_tests($tests);
        $original_classes = get_declared_classes();
        foreach ( $tests as $test ) {
            require $test;
        }
        $new_classes = get_declared_classes();
        $tests = array_diff($new_classes, $original_classes);
        foreach ( $tests as $test ) {
            if ( is_subclass_of($test, 'PHPUnit_Framework_TestCase') ) {
                $suite->addTestSuite($test);
            }
        }

        $result = new \PHPUnit_Framework_TestResult();
        $result->addListener(new \PHPUnit_Util_Log_JSON());

        // We need to temporarily turn off html_errors to ensure correct
        // parsing of test debug output
        $html_errors = ini_get('html_errors');
        ini_set('html_errors', 0);

        ob_start();
        $suite->run($result);
        $results = ob_get_contents();
        ob_end_clean();

        ini_set('html_errors', $html_errors);
        return $results;
    }

   /**
    * Checks that the provided XML configuration file contains the necessary
    * JSON listener.
    *
    * @param string $xml_config    The path to the PHPUnit XML configuration
    *                              file.
    * @access protected
    * @return void
    */
    protected function _check_xml_configuration($xml_config) {
        $configuration = \PHPUnit_Util_Configuration::getInstance($xml_config);
        $listeners = $configuration->getListenerConfiguration();

        $required_listener = 'PHPUnit_Util_Log_JSON';
        $found = false;
        foreach ( $listeners as $listener ) {
            if ( $listener['class'] === $required_listener ) {
                $found = true;
                break;
            }
        }
        if ( !$found ) {
            throw new \DomainException(
                "XML Configuration file doesn't contain the required " .
                "{$required_listener} listener."
            );
        }
    }

   /**
    * Runs PHPUnit with the supplied XML configuration file.
    *
    * @param string $xml_config    The path to the PHPUnit XML configuration
    *                              file.
    * @access public
    * @return string
    */
    public function run_with_xml($xml_config) {
        $this->_check_xml_configuration($xml_config);
        $command = new \PHPUnit_TextUI_Command();

        // We need to temporarily turn off html_errors to ensure correct
        // parsing of test debug output
        $html_errors = ini_get('html_errors');
        ini_set('html_errors', 0);

        ob_start();
        $command->run(array('--configuration', $xml_config, '--stderr'), false);
        $results = ob_get_contents();
        ob_end_clean();

        ini_set('html_errors', $html_errors);

        $start = strpos($results, '{');
        $end = strrpos($results, '}');
        return substr($results, $start, $end - $start + 1);
    }

}

?>
