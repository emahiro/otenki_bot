<?php
declare(strict_types=1);
namespace App\Lib;
use Log;

class Logger{

    public static function out($str){
        Log::debug($str);
    }

    public static function info(string $str)
    {
        Log::info($str);
    }
}
