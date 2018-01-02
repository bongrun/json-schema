<?php

include '../vendor/autoload.php';

ini_set('memory_limit', '6560M');
$storage = new \bongrun\JsonSchema\SchemaStorage();
$schema = $storage->addSchema('https://raw.githubusercontent.com/VKCOM/vk-api-schema/master/objects.json');

file_put_contents('objects.json', json_encode($schema->getData()));

$schema = $storage->addSchema('https://raw.githubusercontent.com/VKCOM/vk-api-schema/master/responses.json');

file_put_contents('responses.json', json_encode($schema->getData()));

$schema = $storage->addSchema('https://raw.githubusercontent.com/VKCOM/vk-api-schema/master/methods.json');

file_put_contents('methods.json', json_encode($schema->getData()));

