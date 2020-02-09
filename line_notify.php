<?php
define('TOKEN', '');
define('MESSAGE_NOTIFY_API_URL', 'https://notify-api.line.me/api/notify');
define('WEATHER_API_URL', 'http://weather.livedoor.com/forecast/webservice/json/v1?city=');

$city_list = [
    130010,
    140010,
    140020
];

foreach ($city_list as $city) {
    //天気予報を取得
    $weather_data = get_weather_data($city);
    if (empty($weather_data)) {
        $msg = "天気予報取得失敗です。";
    } else {
        //通知メッセージを作成
        $msg = create_message($weather_data);
    }
    //通知
    send_msg($msg);
}
 

function get_weather_data($city) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, WEATHER_API_URL.$city);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    $http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($http_code != 200) {
        return false;
    }
    if (curl_error($ch)) {
        return false;
    }

    $weather_data = json_decode($response, true);

    curl_close($ch);

    return $weather_data;

}

function create_message($arr) {
    $msg = '';
    $title = $arr['title'] ?? '';
    $msg .= $title."\n\n";
    for ($i = 0; $i < 2; $i++) {
        $date = $arr['forecasts'][$i]['date'] ?? '';
        $date_label = $arr['forecasts'][$i]['dateLabel'] ?? '';
        $weather = $arr['forecasts'][$i]['image']['title'] ?? '';
        $min = $arr['forecasts'][$i]['temperature']['min']['celsius'] ?? '';
        $max = $arr['forecasts'][$i]['temperature']['max']['celsius'] ?? '';
        $msg .= $date_label.'('.$date.')'."\n";
        $msg .= '天気: '.$weather."\n";
        $msg .= '最高気温: '.$max."\n";
        $msg .= '最低気温: '.$min;
        if ($i < 1) {
            $msg .= "\n\n";
        }
    }

    return $msg;
}

function send_msg($msg) {
    $post_data = http_build_query(['message' => $msg]);

    $ch = curl_init(MESSAGE_NOTIFY_API_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Bearer '.TOKEN,
        'Content-Length: '.strlen($post_data)
    ]);
    curl_exec($ch);

    $http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($http_code != 200) {
        return false;
    }
    if (curl_error($ch)) {
        return false;
    }

    curl_close($ch);
    return true;
}