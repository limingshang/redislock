# redislock

    根据大佬修车工写的一个渣渣类
    基于predis类
    如需自己安装predis请执行
    composer require predis/predis
## 单队列调用  每次只执行一个消息，多余消息将直接返回队列被抛出异常：单队列已被占用！请稍后

    $lock=new Lock();
    $lock->queueLock(
        function ($redis){
            echo "hello word!";
            sleep(10);
        },
        "hello",
        50
    );
    
## 多队列调用 可持续写入消息，当超过最大队列消息，将会抛出异常：队列已被占满，请等待处理完毕后重试
    $lock->lock(
        function ($redis){
            echo "hello word!";
            sleep(10);
        },
        "hello",
        50,
        3
    );
    
#### 备注
    6-22  2：15 修改命名空间
    6-22  2：38 修改readme.me