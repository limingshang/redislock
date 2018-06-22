# redislock

    根据大佬修车工写的一个渣渣类
    基于predis类
    如需自己安装predis请执行
    composer require predis/predis
## 单队列调用

    Lock::lock(
        function ($redis){
            echo "hello word!";
            sleep(10);
        },
        "hello",
        50,
        3
    );
    
## 多队列调用

    Lock::queueLock(
        function ($redis){
            echo "hello word!";
            sleep(10);
        },
        "hello",
        50,
        3
    );

#### 备注
    6-22  2：15修改命名空间