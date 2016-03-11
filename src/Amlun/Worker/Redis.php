<?php
namespace Amlun\Worker;

/**
 * Created by PhpStorm.
 * User: lunweiwei
 * Date: 16/3/11
 * Time: 上午11:18
 */
use Amlun\Worker;
use Amlun\Exception;
use Amlun\Config;

abstract class Redis extends Worker
{
    /**
     * Redis connection
     * @var \Redis
     */
    protected $_redis;

    protected function startWorker()
    {
        $this->_connectRedis();
        $run_count = 0;
        $queue = Config::get('queue', $this->worker_name);
        while (!$this->stop_work) {
            pcntl_signal_dispatch();
            $job_info = $this->_redis->lPop($queue);
            if (!empty($job_info)) {
                try {
                    $this->_do($job_info);
                } catch (Exception $e) {
                    $this->logger->err($e);
                }
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
        $this->_closeRedis();
    }

    /**
     * @param $job_info
     * @return mixed | bool
     * @throws Exception
     */
    abstract protected function _do($job_info);

    /**
     * 初始化Redis实例
     * @return mixed
     * @throws Exception
     */
    protected function _connectRedis()
    {
        $redis_config = Config::get('redis', $this->worker_name);
        $this->_redis = new \Redis();
        $link = $this->_redis->pconnect($redis_config['host'], $redis_config['port'], $redis_config['timeout']);
        if (!$link) {
            throw new Exception("can not connect the redis server {$redis_config['host']}");
        }
    }

    protected function _closeRedis()
    {
        if (isset($this->_redis)) {
            $this->_redis->close();
        }
    }

}