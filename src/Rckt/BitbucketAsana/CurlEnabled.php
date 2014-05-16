<?php
/**
 * CURL enabled class
 *
 * @version 1.0
 * @author John Noel <john.noel@rckt.co.uk>
 * @package BitbucketAsana
 */

namespace Rckt\BitbucketAsana;

abstract class CurlEnabled
{
    protected $curl;

    protected function curlInit()
    {
        if ($this->curl === null) {
            $this->curl = curl_init();
            curl_setopt_array($this->curl, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_USERAGENT => 'php',
            ));
        }
    }

    protected function httpRequest($method, $url, $params = array(), $headers = array())
    {
        $this->curlInit();

        $method = strtoupper($method);

        if ($method == 'POST') {
            curl_setopt_array($this->curl, array(
                CURLOPT_POST => true,
                CURLOPT_URL => $url,
            ));

            if (!empty($params)) {
                // not an array so no content-type: multipart/form-data
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($params));
            }
        } else if ($method == 'PUT') {
            curl_setopt_array($this->curl, array(
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => http_build_query($params),
                CURLOPT_URL => $url,
            ));
        } else {
            $requestUrl = (!empty($params)) ? $url.'?'.http_build_query($params) : $url;

            curl_setopt_array($this->curl, array(
                CURLOPT_HTTPGET => true,
                CURLOPT_URL => $requestUrl,
            ));
        }

        if (!empty($headers)) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($this->curl);

        if ($response === false) {
            throw new \Exception(sprintf('cURL error: [%d] %s', curl_errno($this->curl), curl_error($this->curl)));
        }

        $httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if ($httpCode > 399) {
            throw new \Exception(sprintf('%s request for %s unsuccessful, server said [%d] %s, headers were %s', $method, $url, $httpCode, $response, implode(',', $headers)));
        }

        if ($method == 'PUT') {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, null);
        }

        return $response;
    }
}
