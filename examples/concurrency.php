<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimaLandSdk\Client;

$data = require_once __DIR__ . '/../userData.php';

// Email of phone number can be used for authorization
$client = new Client([
    'email' => $data['email'],
    'password' => $data['password']
]);
$client->setRequestOptions(['debug' => false]);

$items = $client->getItem()->send();

$query = [];
foreach ($items as $it) {
    $query[] = $client->getItem($it['id'])->prepare();
}
// $query = array_slice($query, 0, 10);
$start = microtime(true);
print_r($client->sendAsync($query));
echo "Time for request: " . (microtime(true) - $start) . PHP_EOL . "Memory used: " . memory_get_peak_usage() . PHP_EOL;