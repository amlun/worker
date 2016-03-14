<?php
namespace Amlun;
/**
 * Created by PhpStorm.
 * User: lunweiwei
 * Date: 16/3/11
 * Time: 上午11:12
 */
set_time_limit(0);
declare(ticks = 1);
/**
 * Use supervisord to control multi process
 *
 *
 * Created by PhpStorm.
 * User: lunweiwei
 * Date: 16/3/1
 * Time: 下午6:01
 */

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

abstract class Worker
{
    /**
     * Worker name
     * @var string
     */
    protected $worker_name;
    /**
     * When true, worker will stop
     * @var bool
     */
    protected $stop_work = false;
    /**
     * The timestamp when the worker start run
     * @var int
     */
    protected $start_time = 0;
    /**
     * The timestamp when the worker stop working
     * @var int
     */
    protected $stop_time = 0;
    /**
     * The PID of the running process
     * @var int
     */
    protected $pid = 0;
    /**
     * Maximum time worker will run
     * @var int
     */
    protected $max_run_time = 60;
    /**
     * Maximum number of jobs this worker will do before quitting
     * @var int
     */
    protected $max_job_count = 10;
    /**
     * Worker config info
     * @var array
     */
    protected $config = array();
    /**
     * Logger of the worker
     * @var Logger
     */
    protected $logger;

    /**
     * Creates the manager and gets things going
     * @param string $name
     */
    public function __construct($name)
    {
        if (!defined('PHP_SAPI') || PHP_SAPI != 'cli') {
            $this->showHelp("This worker can only run in cli mod");
        }
        if (!function_exists("posix_setuid")) {
            $this->showHelp("The function posix_setuid was not found. Please ensure POSIX functions are installed");
        }
        if (!function_exists("pcntl_signal")) {
            $this->showHelp("The function pcntl_signal was not found. Please ensure Process Control functions are installed");
        }
        $this->worker_name = $name;
        /**
         * Set the start time
         */
        $this->start_time = microtime(true);
        /**
         * Set pid
         */
        $this->pid = getmypid();
        /**
         * Parse command line options. Loads the config file as well
         */
        $this->getOpt();
        /**
         * Init Logger
         */
        $this->initLogger();
        /**
         * Register signal listeners
         */
        $this->registerTicks();
        /**
         * Start the worker
         */
        $this->startWorker();
        /**
         * Set the stop time
         */
        $this->stop_time = microtime(true);
    }

    /**
     * Parses the command line options
     *
     */
    protected function getOpt()
    {
        $opts = getopt("c:H:l:v:r:x:Z");
        if (isset($opts["H"])) {
            $this->showHelp();
        }
        if (isset($opts["c"])) {
            $this->config['file'] = $opts['c'];
        }
        if (isset($this->config['file'])) {
            if (file_exists($this->config['file'])) {
                $this->parseConfig($this->config['file']);
            } else {
                $this->showHelp("Config file {$this->config['file']} not found.");
            }
        }
        if (isset($opts["l"])) {
            $this->config['log_file'] = $opts["l"];
        }
        if (isset($opts["v"])) {
            switch ($opts["v"]) {
                case false:
                    $this->config['log_level'] = Logger::INFO;
                    break;
                case "v":
                    $this->config['log_level'] = Logger::ERROR;
                    break;
                case "vv":
                    $this->config['log_level'] = Logger::WARNING;
                    break;
                case "vvv":
                    $this->config['log_level'] = Logger::NOTICE;
                    break;
                case "vvvv":
                default:
                    $this->config['log_level'] = Logger::DEBUG;
                    break;
            }
        }
        if (isset($opts['x'])) {
            $this->config['max_worker_lifetime'] = (int)$opts['x'];
        }
        if (isset($this->config['max_worker_lifetime']) && (int)$this->config['max_worker_lifetime'] > 0) {
            $this->max_run_time = (int)$this->config['max_worker_lifetime'];
        }
        if (isset($opts['r'])) {
            $this->config['max_runs_per_worker'] = (int)$opts['r'];
        }
        if (isset($this->config['max_runs_per_worker']) && (int)$this->config['max_runs_per_worker'] > 0) {
            $this->max_job_count = (int)$this->config['max_runs_per_worker'];
        }
        /**
         * Debug option to dump the config and exit
         */
        if (isset($opts["Z"])) {
            print_r($this->config);
            exit();
        }
    }

    /**
     * Init the Logger
     */
    protected function initLogger()
    {
        $this->logger = new Logger(__CLASS__);
        $log_level = Logger::INFO;
        if (isset($this->config['log_level']) && !empty($this->config['log_level'])) {
            $log_level = Logger::toMonologLevel($this->config['log_level']);
        }
        if (isset($this->config['log_file'])) {
            $handler = new StreamHandler($this->config['log_file'], $log_level);
            $this->logger->pushHandler($handler);
        }
    }

    /**
     * Parses the config file
     * @param string $file The config file.
     */
    protected function parseConfig($file)
    {
        $config = parse_ini_file($file, true);
        if (empty($config)) {
            $this->showHelp("No configuration found in $file");
        }
        if (isset($config['base'])) {
            $this->config = $config['base'];
        }
        if (isset($config[$this->worker_name])) {
            $this->config = array_merge($this->config, $config[$this->worker_name]);
        }
    }

    /**
     * Registers the process signal listeners
     */
    protected function registerTicks()
    {
        $this->logger->debug("Registering signals");
        pcntl_signal(SIGTERM, array($this, "signal"));
        pcntl_signal(SIGINT, array($this, "signal"));
    }

    /**
     * Handles signals
     * @param $signo
     */
    public function signal($signo)
    {
        switch ($signo) {
            case SIGTERM:
            case SIGINT:
            case SIGQUIT:
                $this->logger->debug("Shutting down...");
                $this->stop_work = true;
                break;
            default:
                // handle all other signals
        }
    }

    /**
     * Worker main function
     * @return mixed
     */
    abstract protected function startWorker();

    /**
     * Shows help info with optional error message
     * @param string $message
     */
    protected function showHelp($message = "")
    {
        if ($message) {
            echo "ERROR:\n";
            echo "  " . wordwrap($message, 72, "\n  ") . "\n\n";
        }
        echo "Worker manager script\n\n";
        echo "USAGE:\n";
        echo "  # " . basename(__FILE__) . " -H | -c CONFIG [-v] [-l LOG_FILE] [-r MAX_NUM] [-x MAX_TIME] [-Z]\n\n";
        echo "OPTIONS:\n";
        echo "  -c CONFIG      Worker configuration file\n";
        echo "  -H             Shows this help\n";
        echo "  -l LOG_FILE    Log output to LOG_FILE or use keyword 'syslog' for syslog support\n";
        echo "  -r NUMBER      Maximum job iterations per worker\n";
        echo "  -x SECONDS     Maximum seconds for a worker to live\n";
        echo "  -Z             Parse the command line and config file then dump it to the screen and exit.\n";
        echo "\n";
        exit();
    }

    /**
     * Handles anything we need to do when we are shutting down
     */
    public function __destruct()
    {
        $up_time = date_diff(new \DateTime(date(DATE_ISO8601, $this->stop_time)), new \DateTime(date(DATE_ISO8601, $this->start_time)));
        $memory_usage = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 2) . 'MB';
        $message = sprintf('uptime: %s, memory usage: %s', $up_time->format('%h hours, %i minutes, %s seconds'), $memory_usage);
        if (isset($this->logger)) {
            $this->logger->debug($message);
        }
    }
}