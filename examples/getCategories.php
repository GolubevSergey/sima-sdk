<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimaLandSdk\Client;

// Email of phone number can be used for authorization
$client = new Client([
    'email' => 'email address',
    'phone' => 'phone number',
    'password' => 'password'
]);
$client->setRequestOptions(['debug' => false]);
$client->setReturnFormat('json');

$catList = [];
foreach (getAllCategories($client) as $category) {
    print_r($category);
    $catList[] = $category;
}

function getAllCategories($client)
{
    $i = 1;
    while ($categories = $client->getCategory(page: $i)->send()) {
        $i++;
        foreach ($categories as $category) {
            yield $category;
        }
    }
}