<?php
namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

use Logger;

class GetWeatherCommand extends Command{

    protected $name = 'weather:get';
    protected $description = 'request Weatherhack Api and get weather Data';

    public function fire(){
        // お天気のAPIを取得する

    }
}
