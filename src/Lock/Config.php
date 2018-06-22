<?php
/**
 * User:       ： lims
 * Date:       ： 2018/6/22
 * Time:       ： 上午11:07
 * Effect      ：
 */
namespace redislock\Lock;

class Config
{
    public static function returnConfig(){
        return config('lockredis');
    }
}