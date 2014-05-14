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

    protected function httpRequest($method, $url, $params = array(), $headers = array())
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
        $httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if ($httpCode > 399) {
            throw new \Exception(sprintf('%s request for %s unsuccessful, server said [%d] %s, headers were %s', $method, $url, $httpCode, $response, implode(',', $headers)));
        }

        return $response;
    }
}
