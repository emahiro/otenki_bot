<?php
namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Logger;
use Slack;

class GetWeatherCommand extends Command{

    /**
     * バッチが動作する時間
     * 朝 8:00
     * 昼 12:00
     * 夜 18:00
     */

    const MORNING = 8;
    const AFTERNOON = 12;
    const EVENING = 18;

    protected $name = 'weather:get';
    protected $description = 'request Weatherhack Api and get weather Data';

    public function fire(){
        // お天気のAPIを取得する
        $hour = date('H') + 9;
        Logger::out($hour);
        $weather_data = [];

        switch (true) {
            case $hour <= Self::MORNING:
                $weather_data = $this->morning();
                break;

            case Self::AFTERNOON < $hour && $hour <= Self::EVENING:
                $weather_data = $this->afternoon();
                break;

            case Self::EVENING < $hour:
                $weather_data = $this->evening();
                break;

            default:
                die();
                break;
        }

        $this->sendSlack($weather_data);
    }

    private function morning(){
        Logger::info('morning');
        return [
            'weather_info' => $this->weatherHacks(Self::MORNING),
            'rainfallchance' => $this->getRainfallchance(Self::MORNING),
        ];
    }

    private function afternoon(){
        Logger::info('afternoon');
        return [
            'weather_info' => $this->weatherHacks(Self::AFTERNOON),
            'rainfallchance' => $this->getRainfallchance(Self::AFTERNOON),
        ];
    }

    private function evening(){
        Logger::info('evening');
        return [
            'weather_info' => $this->weatherHacks(Self::EVENING),
            'rainfallchance' => $this->getRainfallchance(Self::EVENING),
        ];
    }

    private function sendSlack($data){
        $channel = config('slack.channel');
        $user_name = config('slack.user_name');
        $webhook_url = config('slack.webhook_url');

        foreach ($data as $k => $items) {
            foreach ($items as $k => $item) {
                $payload = [
                    'text' => $item
                ];

                Slack::send($payload, $webhook_url);
            }
        }

        // $payload = [
        //     "channel" => $channel,
        //     "user_name" => $user_name,
        //     "text" => 'test'
        // ];
    }

    /**
     * [weaherHacks description]
     * @return [type] [description]
     */
    private function weatherHacks(int $day_time):array
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

            switch ($day_time) {
                case Self::MORNING:

                if ($item->dateLabel === '今日') {

                    $max_temperature = $item->temperature->max->celsius;

                    $res = [
                        'dayPeriod' => '今日の天気',
                        'description' => (string)$description,
                        'temperature' => '最高気温 : '.$max_temperature.'℃',
                    ];
                }

                break;

                case Self::AFTERNOON:

                    if ($item->dateLabel === '今日') {
                        $max_temperature = $item->temperature->max->celsius;

                        $res = [
                            'dayPeriod' => '午後の天気',
                            'temperature' => '最高気温 : '.$max_temperature.'℃',
                        ];
                    }

                    break;

                case Self::EVENING:

                    if ($item->dateLabel === '明日') {

                        $max_temperature = $item->temperature->max->celsius;
                        $min_temperature = $item->temperature->min->celsius;

                        $res = [
                            'dayPeriod' => '明日の天気',
                            'description' => (string)$description,
                            'temperature' =>$max_temperature.'℃ / '.$min_temperature.'℃',
                        ];
                    }

                    break;
            }
        }

        return $res;
    }

    /**
     * getRainfallchance 降水確率の取得
     * @return [type] [description]
     * http://www.drk7.jp/weather/xml/13.xml
     */
    private function getRainfallchance(int $day_time): array
    {
        $xml = simplexml_load_string(file_get_contents(config('area.tokyo.drk7'), true));
        foreach ($xml->pref->area as $k => $item) {
            // 東京以外の地域はスキップする
            if ($item->attributes()["id"] != "東京地方") {
                continue;
            }

            $period = get_object_vars($item->info->rainfallchance)["period"];
            switch ($day_time) {
                case $day_time <= Self::MORNING;

                    $rain = [
                        'morning' => '午前中の降水確率 : '.$period[1].'%',
                        'afternoon' => '午後の降水確率 : '.$period[2].'%',
                        'evening' => '帰る時間の降水確率 : '.$period[3].'%'
                    ];

                    break;

                case $day_time <= Self::AFTERNOON:

                    $rain = [
                        'afternoon' => '午後の降水確率 : '.$period[2].'%',
                        'evening' => '帰る時間の降水確率 : '.$period[3].'%'
                    ];

                    break;

                case $day_time <= Self::EVENING:

                    $rain = [
                        'evening' => '帰る時間の降水確率 : '.$period[3].'%'
                    ];

                    break;

                default:
                    die();
                    break;
            }
        }

        return $rain;
    }
}
