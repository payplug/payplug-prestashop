<?php
/**
 * MyLogPHP 1.2.6
 *
 * NOTICE OF LICENSE
 *
 * MyLogPHP is a single PHP class to easily keep log files in CSV format.
 *
 * @author     Lawrence Lagerlof <llagerlof@gmail.com>
 * @copyright  2014 Lawrence Lagerlof
 * @license    http://opensource.org/licenses/BSD-3-Clause New BSD License
 * @package    MyLogPHP
 * @subpackage Common
 * @link       http://github.com/llagerlof/MyLogPHP
 */

class MyLogPHP
{
    /**
     * Name of the file where the message logs will be appended.
     * @access private
     */
    private $LOGFILENAME;

    /**
     * Define the separator for the fields. Default is comma (,).
     * @access private
     */
    private $SEPARATOR;

    /**
     * The first line of the log file.
     * @access private
     */
    private $HEADERS;

    /* @const Default tag. */
    const DEFAULT_TAG = '--';

    /**
     * Constructor
     * @param string $logfilename Path and name of the file log.
     * @param string $separator Character used for separate the field values.
     */
    public function __construct($logfilename = './_MyLogPHP-1.2.log.csv', $separator = ',')
    {
        $this->LOGFILENAME = $logfilename;
        $this->SEPARATOR = $separator;
        $this->HEADERS =
            'DATETIME' . $this->SEPARATOR .
            'ERRORLEVEL' . $this->SEPARATOR .
            'TAG' . $this->SEPARATOR .
            'VALUE' . $this->SEPARATOR .
            'LINE' . $this->SEPARATOR .
            'FILE';
    }

    /**
     * Private method that will write the text messages into the log file.
     *
     * @param string $errorlevel There are 4 possible levels: INFO, WARNING, DEBUG, ERROR
     * @param string $value The value that will be recorded on log file.
     * @param string $tag Any possible tag to help the developer to find specific log messages.
     */
    private function log($errorlevel = 'INFO', $value = '', $tag = '', $line_n = null)
    {
        $datetime = @date("Y-m-d H:i:s");
        if (!file_exists($this->LOGFILENAME)) {
            $headers = $this->HEADERS . "\n";
        }
        $fd = fopen($this->LOGFILENAME, "a");
        if (@$headers) {
            fwrite($fd, $headers);
        }
        $debugBacktrace = debug_backtrace();
        if ($line_n === null) {
            $line = $debugBacktrace[1]['line'];
        } else {
            $line = $line_n;
        }
        $file = $debugBacktrace[1]['file'];
        $value = preg_replace('/\s+/', ' ', trim($value));
        $entry = array($datetime,$errorlevel,$tag,$value,$line,$file);
        fputcsv($fd, $entry, $this->SEPARATOR);
        fclose($fd);
    }

    /**
     * Function to write non INFOrmation messages that will be written into $LOGFILENAME.
     *
     * @param string $value
     * @param string $tag
     */
    public function info($value = '', $tag = self::DEFAULT_TAG, $line_n = null)
    {
        self::log('INFO', $value, $tag, $line_n);
    }

    /**
     * Function to write WARNING messages that will be written into $LOGFILENAME.
     *
     * Warning messages are for non-fatal errors, so, the script will work properly even
     * if WARNING errors appears, but this is a thing that you must ponderate about.
     *
     * @param string $value
     * @param string $tag
     */
    public function warning($value = '', $tag = self::DEFAULT_TAG, $line_n = null)
    {
        self::log('WARNING', $value, $tag, $line_n);
    }

    /**
     * Function to write ERROR messages that will be written into $LOGFILENAME.
     *
     * These messages are for fatal errors. Your script will NOT work properly if an ERROR happens, right?
     *
     * @param string $value
     * @param string $tag
     */
    public function error($value = '', $tag = self::DEFAULT_TAG, $line_n = null)
    {
        self::log('ERROR', $value, $tag, $line_n);
    }

    /**
     * Function to write DEBUG messages that will be written into $LOGFILENAME.
     *
     * DEBUG messages are for variable values and other technical issues.
     *
     * @param string $value
     * @param string $tag
     */
    public function debug($value = '', $tag = self::DEFAULT_TAG, $line_n = null)
    {
        self::log('DEBUG', $value, $tag, $line_n);
    }
}

function truncateCSV($glob) {
    foreach (glob($glob) as $path) {
        $file = fopen($path, "w");
        ftruncate($file, 0);
        fclose($file);
    }
}
