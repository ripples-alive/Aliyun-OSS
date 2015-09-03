<?php

require_once dirname(__FILE__) . '/../src/OSS.php';
require_once 'config.php';

use Ripples\Aliyun\OSS;

$oss = new OSS(ACCESS_KEY_ID, ACCESS_KEY_SECRET, ENDPOINT);

$bucket = BUCKET;
$key1 = 'test-key1';
$key2 = 'test-key2';
$key3 = 'test-key3';
$tmp = 'example.tmp';

echo $oss->signGetUrl($bucket, $key1);
echo "\n";

echo $oss->signPutUrl($bucket, $key2);
echo "\n";

echo $oss->signUrl($bucket, $key3, 60, 'GET');
echo "\n";

$oss->putObject($bucket, $key1, '<h1>Just for Test</h1>');
$oss->getObjectToFile($bucket, $key1, $tmp);

echo $oss->getObject($bucket, $key1);
echo "\n";

$oss->putObjectFromFile($bucket, $key2, $tmp, ['Content-Type'=>'text/plain']);
unlink($tmp);

$oss->copyObject($bucket, $key1, $bucket, $key3, [
    'x-oss-metadata-directive' => 'REPLACE',
    'Content-Type' => 'text/html',
]);
echo $oss->getMeta($bucket, $key3)['content-type'];
echo "\n";

$oss->modifyMeta($bucket, $key3, ['x-oss-meta-user-data' => 'This-is-user-meta-data.']);
echo $oss->getMeta($bucket, $key3)['content-type'];
echo "\n";

$oss->setMeta($bucket, $key3, ['x-oss-meta-user-data' => 'This-is-user-meta-data.']);
echo $oss->getMeta($bucket, $key3)['content-type'];
echo "\n";

$oss->deleteObject($bucket, $key1);
$oss->deleteObjects($bucket, [$key2]);

var_dump($oss->isObjectExists($bucket, $key1));
var_dump($oss->isObjectExists($bucket, $key2));
var_dump($oss->isObjectExists($bucket, $key3));
