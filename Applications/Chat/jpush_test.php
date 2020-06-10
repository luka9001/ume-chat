<?php

$url = 'https://api.jpush.cn/v3/push';

$data = '{
    "cid": "27837b1c1fed6927c288e3df-02b4a65d-02ed-4dda-8741-73d8a8df7843",
    "platform": "all",
    "audience": {
        "alias": [
            "qy_89"
        ]
    },
    "notification": {
        "android": {
            "alert": "Hi, JPush!",
            "title": "Send to Android",
            "builder_id": 1,
            "large_icon": "http://www.jiguang.cn/largeIcon.jpg",
            "extras": {
                "newsid": 321
            }
        },
        "ios": {
            "alert": "Hi, JPush!",
            "sound": "default",
            "badge": "+1",
            "thread-id": "default",
            "extras": {
                "newsid": 321
            }
        }
    },
    "message": {
        "msg_content": "Hi,JPush",
        "content_type": "text",
        "title": "msg",
        "extras": {
            "key": "value"
        }
    },
    "options": {
        "time_to_live": 60,
        "apns_production": false,
        "apns_collapse_id":"jiguang_test_201706011100"
    }
}
';

$curl = curl_init();
// request url
curl_setopt($curl, CURLOPT_URL, $url);
// headers
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Connection: Keep-Alive',
    "Authorization: Basic ".base64_encode("27837b1c1fed6927c288e3df:c7664b0d3f55056db560ecab")
));

curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

// post fields
if (!empty($data)) { // post方式
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
}
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
// https
if (!empty($ignoreSsl)) {
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
}
// timeout
//curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
//curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

$output = curl_exec($curl);

echo $output;

if (curl_error($curl)) $output = curl_error($curl);
echo $output;
curl_close($curl);
