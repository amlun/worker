<?php
namespace Amlun\Worker;

use Amlun\Worker;
use Amlun\Exception;

/**
 * Created by PhpStorm.
 * User: lunweiwei
 * Date: 16/3/11
 * Time: 上午11:17
 */
abstract class Simple extends Worker
{
    /**
     * run the worker
     */
    protected function startWorker()
    {
        $run_count = 0;
        while (!$this->stop_work) {
            pcntl_signal_dispatch();
            try {
                $this->_do();
            } catch (Exception $e) {
                $this->logger->err($e);
            }
            if ($this->stop_work) {
                $this->logger->debug("process get exit signo, pid: {$this->pid}");
                break;
            }
            if (time() - $this->start_time >= $this->max_run_time) {
                $this->logger->debug("process running over the max run time: {$this->max_run_time}");
                break;
            }
            if (++$run_count >= $this->max_job_count) {
                $this->logger->debug("process running over the max run count: {$this->max_job_count}");
                break;
            }
            usleep(50000);
        }
    }

    /**
     * real job to do
     * @return mixed
     */
    abstract function _do();
}