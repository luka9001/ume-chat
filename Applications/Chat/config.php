<?php
require __DIR__ . '/../autoload.php';

use JPush\Client as JPush;

//$app_key = getenv('app_key');
//$master_secret = getenv('master_secret');
$app_key = '27837b1c1fed6927c288e3df';
$master_secret = 'c7664b0d3f55056db560ecab';
$registration_id = getenv('registration_id');

$client = new JPush($app_key, $master_secret);
