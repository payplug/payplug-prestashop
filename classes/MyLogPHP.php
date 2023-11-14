<?php
/**
 * MyLogPHP 1.2.6.
 *
 * NOTICE OF LICENSE
 *
 * MyLogPHP is a single PHP class to easily keep log files in CSV format.
 *
 * @author     Lawrence Lagerlof <llagerlof@gmail.com>
 * @copyright  2014 Lawrence Lagerlof
 * @license    http://opensource.org/licenses/BSD-3-Clause New BSD License
 *
 * @see       http://github.com/llagerlof/MyLogPHP
 */

namespace PayPlug\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class MyLogPHP
{
    // @const Default tag.
    const DEFAULT_TAG = '--';
    /**
     * Name of the file where the message logs will be appended.
     */
    private $LOGFILENAME;

    /**
     * Define the separator for the fields. Default is comma (,).
     */
    private $SEPARATOR;

    /**
     * The first line of the log file.
     */
    private $HEADERS;

    /**
     * @description Constructor.
     *
     * @param string $logfilename
     * @param string $separator
     */
    public function __construct($logfilename = './install-log.csv', $separator = ',')
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

    public static function factory($installLog)
    {
        return new \Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . $installLog);
    }

    /**
     * @description Function to write non INFOrmation messages that will be written into $LOGFILENAME.
     *
     * @param string $value
     * @param string $tag
     * @param null $line_n
     */
    public function info($value = '', $tag = self::DEFAULT_TAG, $line_n = null)
    {
        self::log('INFO', $value, $tag, $line_n);
    }

    /**
     * @description Function to write WARNING messages that will be written into $LOGFILENAME.
     *
     * @param string $value
     * @param string $tag
     * @param null $line_n
     */
    public function warning($value = '', $tag = self::DEFAULT_TAG, $line_n = null)
    {
        self::log('WARNING', $value, $tag, $line_n);
    }

    /**
     * @description Function to write ERROR messages that will be written into $LOGFILENAME.
     *
     * @param string $value
     * @param string $tag
     * @param null $line_n
     */
    public function error($value = '', $tag = self::DEFAULT_TAG, $line_n = null)
    {
        self::log('ERROR', $value, $tag, $line_n);
    }

    /**
     * @description Function to write DEBUG messages that will be written into $LOGFILENAME.
     *
     * @param string $value
     * @param string $tag
     * @param null $line_n
     */
    public function debug($value = '', $tag = self::DEFAULT_TAG, $line_n = null)
    {
        self::log('DEBUG', $value, $tag, $line_n);
    }

    /**
     * @description Private method that will write the text messages into the log file.
     *
     * @param string $errorlevel
     * @param string $value
     * @param string $tag
     * @param null $line_n
     */
    private function log($errorlevel = 'INFO', $value = '', $tag = '', $line_n = null)
    {
        $datetime = @date('Y-m-d H:i:s');
        if (!file_exists($this->LOGFILENAME)) {
            $headers = $this->HEADERS . "\n";
        }
        if ($fd = @fopen($this->LOGFILENAME, 'a')) {
            if (@$headers) {
                fwrite($fd, $headers);
            }
            $debugBacktrace = debug_backtrace();
            if (null === $line_n) {
                $line = $debugBacktrace[1]['line'];
            } else {
                $line = $line_n;
            }
            $file = $debugBacktrace[1]['file'];
            $value = preg_replace('/\s+/', ' ', trim($value));
            $entry = [$datetime, $errorlevel, $tag, $value, $line, $file];
            fputcsv($fd, $entry, $this->SEPARATOR);
            fclose($fd);
        }
    }
}
