<?php
/**
 * User:       ： lims
 * Date:       ： 2018/6/20
 * Time:       ： 下午6:32
 * Effect      ： 并发操作
 */
namespace redislock;
use Predis\Client;
use redislock\Lock\Config;

class Lock
{
    private $redis;
    private $config;
    private $rand_member;
    private $queue_prefix="queue";
    private $lock_prefix="lock";
    private $wait_prefix="wait";
    private $lock_name;
    private $max_queue=5;
    private $queue_name;
    private $queue_wait_name;
    private $queue_list;

    /**
     * Lock constructor.
     * 事例话需要用到的变量
     */
    public function __construct($config=[],$arguments=[]){
        $this->config=Config::returnConfig();
        $this->redis=new Client($this->config);
    }

    /**
     * Function Name: lock
     * Create   User: lims
     * Create   Time: 2018/6/21 下午3:48
     * ---------------------------------
     * @param $callbacks     执行操作函数
     * @param $lock_value   传递过来的值
     * @param $expiration   等待时间隔
     */
    public function lock($callback,$lock_value,$expiration=60){
        self::makeRand();
        self::setLockName($lock_value);
        $status=$this->redis->set($this->lock_name,$this->rand_member,"nx","ex",$expiration);       //设置进入队列等待内容，失败则提示已占用
        if($status){
            $result=$callback($this->redis);
            self::delLock();        //执行完毕，执行删除队列操作，防止队列锁
            return $result;
        }else{
            Throw new \Exception("单队列已被占用！请稍后");
        }
    }

    /**
     * Function Name: queueLock
     * Create   User: lims
     * Create   Time: 2018/6/21 下午4:21
     * ---------------------------------
     * @param $callbacks         执行操作函数
     * @param $lock_value       传递过来的值
     * @param int $expiration   等待时间隔
     * @param $max_queue        等待执行的队列最大等待量
     */
    public function queueLock($callback,$queue_value,$expiration=60,$max_queue=null){
        self::makeRand();
        self::setQueueName($queue_value);
        $this->queue_wait_name=$this->wait_prefix.':'.$this->queue_name;
        self::setQueueWait($max_queue);

        self::initQueueList($callback,$queue_value);

        while(true){
            if($this->redis->set($this->queue_name,$this->rand_member,"nx",'ex',$expiration)){
                $status=$callback($this->redis);
                self::delQueueLock();        //执行完毕，执行删除队列操作，防止队列锁
                self::delQueueWait();        //执行完毕，执行删除队列操作，防止队列锁
                return $status;
                break;
            }
        }

    }
    /**
     * Function Name: initQueueList
     * Create   User: lims
     * Create   Time: 2018/6/21 下午5:25
     * ---------------------------------
     * @param $callback
     * @param $queue_value
     */
    public function initQueueList($callback,$queue_value){
        $this->queue_list='queue_list:'.$queue_value;
        $this->redis->lpush($this->queue_list,true);
        $this->redis->expire($this->queue_list,60);
    }
    /**
     * Function Name: setQueueWait
     * Create   User: lims
     * Create   Time: 2018/6/21 下午5:11
     * ---------------------------------
     * @param $max_queue       最大队列等待数量
     * @throws \Exception
     */
    public function setQueueWait($max_queue){
        if(!is_null($max_queue))$this->max_queue=$max_queue;
        if($this->redis->get($this->queue_wait_name)>=$this->max_queue)
            Throw new \Exception("队列已被占满，请等待处理完毕后重试");
        else
            $this->redis->incr($this->queue_wait_name);
    }
    /**
     * Function Name: setLockName
     * Create   User: lims
     * Create   Time: 2018/6/21 下午4:00
     * ---------------------------------
     * @param $value    接收lock值
     */
    public function setLockName($lock_value){
        $this->lock_name=$this->lock_prefix.':'.$lock_value;
    }
    /**
     * Function Name: setLockPrefix
     * Create   User: lims
     * Create   Time: 2018/6/21 下午4:00
     * ---------------------------------
     * @param $value    接收lock值
     */
    public function setQueueName($queue_value){
        $this->queue_name=$this->queue_prefix.':'.$queue_value;
    }
    /**
     * Function Name: makerand
     * Create   User: lims
     * Create   Time: 2018/6/21 下午3:52
     * ---------------------------------
     * 产生一个随机数
     */
    public function makeRand(){
        $this->rand_member = uniqid().rand(1000,9999);
    }

    /**
     * Function Name: delLock
     * Create   User: lims
     * Create   Time: 2018/6/21 下午4:37
     * ---------------------------------
     * @throws \Exception
     */
    public function delLock(){
        if($this->redis->exists($this->lock_name))
            $this->redis->del($this->lock_name);
        else
            Throw new \Exception('参数错误');
    }
    /**
     * Function Name: delQueueLock
     * Create   User: lims
     * Create   Time: 2018/6/21 下午4:37
     * ---------------------------------
     * @throws \Exception
     */
    public function delQueueLock(){
        if($this->redis->exists($this->queue_name))
            $this->redis->del($this->queue_name);
        else
            Throw new \Exception('参数错误');
    }

    /**
     * Function Name: delQueueWait
     * Create   User: lims
     * Create   Time: 2018/6/22 上午11:01
     * ---------------------------------
     * @return bool
     */
    public function delQueueWait(){

        if($this->redis->get($this->queue_wait_name)<=0)
            return true;
        else
            return $this->redis->decr($this->queue_wait_name);
    }
}