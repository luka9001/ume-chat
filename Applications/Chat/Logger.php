<?php
require "dbconfig.php";

class Logger
{
    public static function printStr($des, $str)
    {
        if (DEBUG) {
            print_r($des . ":\n");
            print_r($str . "\n");
            print_r("#########\n");
        }
    }

    public static function printObject($des, $object)
    {
        if (DEBUG) {
            print_r($des . ":\n");
            var_dump($object);
            print_r("#########\n");
        }
    }
}
