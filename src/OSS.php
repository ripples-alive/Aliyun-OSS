<?php

namespace Ripples\Aliyun;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'sdk' . DIRECTORY_SEPARATOR . 'sdk.class.php';

use ALIOSS;
use OSS_Exception;
use ResponseCore;

class OSS
{

    /**
     * @var ALIOSS Internal client for OSS.
     */
    protected $internal_client;

    /**
     * @var ALIOSS External client for OSS.
     */
    protected $external_client;

    /**
     * @var Array Extra headers that is valid in put operation.
     */
    protected $put_headers = ['Content-Type', 'Cache-Control', 'Content-Disposition', 'Content-Encoding', 'Content-MD5', 'Expires'];

    public function __construct($key, $secret, $external_endpoint = NULL, $internal_endpoint = NULL)
    {
        if (empty($external_endpoint)) {
            $external_endpoint = ALIOSS::DEFAULT_OSS_ENDPOINT;
        }
        // If no internal endpoint is specific, use the external endpoint instead.
        if (empty($internal_endpoint)) {
            $internal_endpoint = $external_endpoint;
        }
        // TODO: Maybe need lazy loading. However it doesn't matter now because it cost little to create a OSS client.
        $this->external_client = new ALIOSS($key, $secret, $external_endpoint);
        $this->internal_client = new ALIOSS($key, $secret, $internal_endpoint);
    }

    public function signGetUrl($bucket, $key, $timeout = 60, $headers = null, $internal = false)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        return $client->get_sign_url($bucket, $key, $timeout, $headers);
    }

    public function signPutUrl($bucket, $key, $timeout = 60, $headers = null, $internal = false)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        return $client->presign_url($bucket, $key, $timeout, ALIOSS::OSS_HTTP_PUT, $headers);
    }

    public function signUrl($bucket, $key, $timeout = 60, $method = ALIOSS::OSS_HTTP_GET, $headers = null, $internal = false)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        return $client->presign_url($bucket, $key, $timeout, $method, $headers);
    }

    public function getObject($bucket, $key, $internal = true)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        $res = $client->get_object($bucket, $key);
        $this->assertOK($res);
        return $res->body;
    }

    public function getObjectToFile($bucket, $key, $file_path, $internal = true)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        $res = $client->get_object($bucket, $key, [ALIOSS::OSS_FILE_DOWNLOAD => $file_path]);
        $this->assertOK($res);
        return $res->header;
    }

    public function putObject($bucket, $key, $content, $headers = null, $internal = true)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        $res = $client->upload_file_by_content($bucket, $key, [
            'content' => $content,
            'length' => strlen($content),
            ALIOSS::OSS_HEADERS => $headers,
        ]);
        $this->assertOK($res);
        return $res->header;
    }

    public function putObjectFromFile($bucket, $key, $file_path, $headers = null, $internal = true)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        $res = $client->upload_file_by_file($bucket, $key, $file_path, [
            ALIOSS::OSS_HEADERS => $headers,
        ]);
        $this->assertOK($res);
        return $res->header;
    }

    public function copyObject($from_bucket, $from_object, $to_bucket, $to_object, $headers = null, $internal = true)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        $res = $client->copy_object($from_bucket, $from_object, $to_bucket, $to_object, [
            ALIOSS::OSS_HEADERS => $headers,
        ]);
        $this->assertOK($res);
        return $res->header;
    }

    /**
     * Set the headers of object, discard all old headers.
     *
     * @param $bucket
     * @param $key
     * @param $headers
     * @param bool|true $internal
     *
     * @return array
     */
    public function setMeta($bucket, $key, $headers, $internal = true)
    {
        return $this->copyObject($bucket, $key, $bucket, $key, $headers, $internal);
    }

    /**
     * Update the headers based on the old headers.
     * Be cautious to use this function. I'm not sure it's the right way.
     *
     * @param $bucket
     * @param $key
     * @param $headers
     * @param bool|true $internal
     *
     * @return array
     */
    public function modifyMeta($bucket, $key, $headers, $internal = true)
    {
        // Append original headers.
        $original_headers = $this->getMeta($bucket, $key, $internal);
        foreach ($original_headers as $header_key => $header_value) {
            if (substr($header_key, 0, 6) === 'x-oss-') {
                $headers[$header_key] = $header_value;
            }
        }
        foreach ($this->put_headers as $header_key) {
            $lower_key = strtolower($header_key);
            if (array_key_exists($lower_key, $original_headers)) {
                $headers[$header_key] = $original_headers[$lower_key];
            }
        }

        return $this->copyObject($bucket, $key, $bucket, $key, $headers, $internal);
    }

    public function getMeta($bucket, $key, $internal = true)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        $res = $client->get_object_meta($bucket, $key);
        $this->assertOK($res);
        return $res->header;
    }

    public function deleteObject($bucket, $key, $internal = true)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        $res = $client->delete_object($bucket, $key);
        $this->assertOK($res);
        return $res->header;
    }

    public function deleteObjects($bucket, $keys, $internal = true)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        $res = $client->delete_objects($bucket, $keys);
        $this->assertOK($res);
        return $res->header;
    }

    public function isObjectExists($bucket, $key, $internal = true)
    {
        $client = $internal ? $this->internal_client : $this->external_client;
        $res = $client->is_object_exist($bucket, $key);
        if ($res->status === 404) {
            return false;
        } elseif ($res->status === 200) {
            return true;
        }
        // The program shouldn't be running here.
        // Default throw exception.
        throw new OSS_Exception('Unexpected return status.');
    }

    protected function assertOK(ResponseCore $response) {
        if (!$response->isOK()) {
            throw new OSS_Exception('Operation failed.');
        }
        return $response;
    }

}
