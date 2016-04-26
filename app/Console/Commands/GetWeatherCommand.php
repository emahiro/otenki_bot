<?php
namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Logger;
use Slack;

class GetWeatherCommand extends Command{

    protected $name = 'weather:get';
    protected $description = 'request Weatherhack Api and get weather Data';

    public function fire(){
        // お天気のAPIを取得する

        $channel = config('slack.channel');
        $user_name = config('slack.user_name');
        $webhook_url = config('slack.webhook_url');
        $payload = $payload = [
            "channel" => $channel,
            "user_name" => $user_name,
            "text" => 'test',
        ];

        $weather = $this->weaherHacks();
        $weather['rainfallchance'] = $this->getRainfallchance();

        foreach ($weather as $k => $v) {
            Logger::out($k);
        }

        // Slack::send($payload, $webhook_url);
    }


    /**
     * [weaherHacks description]
     * @return [type] [description]
     */
    private function weaherHacks():array
    {
        $res = [];
        $json = file_get_contents(config('area.tokyo.weather_hack'), true);
        $data = json_decode($json);
        $area = $data->location->area;
        $title = $data->title;
        $description = $data->description->text;

        $today_max_temperature = 0;
        $today_min_temperature = 0;

        foreach ($data->forecasts as $k => $item) {

            if ($item->dateLabel === '明日') {

                $max_temperature = $item->temperature->max->celsius;
                $min_temperature = $item->temperature->min->celsius;

                $res = [
                    'description' => (string)$description,
                    'min_temperature' => $min_temperature,
                    'max_temperature' => $max_temperature,
                ];
            }
            // elseif($item->dateLabel === '今日') {
            //     $res = [
            //         'description' => (string)$description,
            //     ];
            // }
        }

        return $res;
    }

    /**
     * getRainfallchance 降水確率の取得
     * @return [type] [description]
     * http://www.drk7.jp/weather/xml/13.xml
     */
    private function getRainfallchance(): array
    {
        $xml = simplexml_load_string(file_get_contents(config('area.tokyo.drk7'), true));
        foreach ($xml->pref->area as $k => $item) {
            // 東京以外の地域はスキップする
            if ($item->attributes()["id"] != "東京地方") {
                continue;
            }

            $hour = date('H');
            $period = get_object_vars($item->info->rainfallchance)["period"];
            switch (true) {
                case $hour < "12":
                    $rain = [
                        'morning' => $period[1],
                        'afternoon' => $period[2],
                        'evening' => $period[3]
                    ];
                    break;
                case $hour < "18":
                    // 12 ~ 18
                    $rain = [
                        'afternoon' => $period[2],
                        'evening' => $period[3]
                    ];
                    break;
                default:
                    //18 ~ 24
                    $rain = [
                        'evening' => $period[3]
                    ];
                    break;
            }
        }

        return $rain;
    }
}
