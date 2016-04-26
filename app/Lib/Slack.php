<?php
namespace App\Lib;

use Config;

class Slack{

    const CURL_COMMAND = 'curl -X POST --data-urlencode';

    public function __construct(){

    }

    public static function send(array $payload, string $webhook)
    {
        exec(Self::CURL_COMMAND." 'payload=".json_encode($payload)."' ".$webhook, $output, $result);
        if ($result === 0) {
            Logger::info('slack success');
        }else{
            Logger::info('slack error');
        }
    }
}
