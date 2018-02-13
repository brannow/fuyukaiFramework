<?php declare(strict_types=1);
/*
 * This file is part of Fuyukai Framework.

 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Benjamin Rannow <rannow@emerise.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
 * OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Fuyukai\Userspace\Curl;


trait CurlLoader
{
    /**
     * @param string $url
     * @param array $queries
     * @param array $params
     * @param string $contentType
     * @param array $customCookieData
     * @return string
     */
    private static function executeRequest(string $url,  array $queries = [], array $params = [], $contentType = 'application/json', array $customCookieData = []): string
    {
        self::flushOutdatedCookie($url);
        if ($queries) {
            $url .= '?' . http_build_query($queries);
        }
        
        $header = [
            'Content-Type: ' . $contentType . '; charset=UTF-8',
            'DNT: 1',
            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Encoding:gzip, deflate',
            'Cache-Control:no-cache',
            'Connection:keep-alive',
            'Pragma:no-cache',
            'Upgrade-Insecure-Requests:1',
        ];
        
        $cookieData = self::extractCookieInformation($url);
        foreach ($customCookieData as $key => $value) {
            $cookieData[$key] = $value;
        }
        if ($cookieData) {
            $header[] = 'Cookie: ' . http_build_query($cookieData, '', '; ');
        }
        
        // setup curl
        $ch = curl_init();
 
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36');
    
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 7);
        curl_setopt($ch, CURLOPT_ENCODING , "gzip");
        
        $cookieJar = self::getCookieJar($url);
        curl_setopt( $ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookieJar );
        curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookieJar );
        
        if ($params) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
        }
        
        if (($result = curl_exec($ch))) {
            $http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code == 200) {
                return $result;
            }
        }
        
        return '';
    }
    
    /**
     * @param string $url
     * @return array
     */
    private static function extractCookieInformation(string $url): array
    {
        $cookieCustomData = [];
        $cookieJar = self::getCookieJar($url);
        if (file_exists($cookieJar) && !is_dir($cookieJar)) {
            $cookieData = file_get_contents($cookieJar);
            $lines = explode("\n", $cookieData);
            foreach ($lines as $cookieLine) {
                if (!empty($cookieLine)) {
                    $cookieSegments = explode("\t", $cookieLine);
                    if (count($cookieSegments) === 7) {
                        $name = $cookieSegments[5];
                        $value = $cookieSegments[6];
                        $cookieCustomData[trim($name)] = trim($value);
                    }
                }
            }
        }
        
        return $cookieCustomData;
    }
    
    /**
     * @param string $url
     * @return string
     */
    private static function getCookieJar(string $url): string
    {
        $urlData = parse_url($url);
        $host = '';
        if (isset($urlData['host'])) {
            $host = $urlData['host'];
        }
        
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'PHP_CURL_COOKIE_' . (string)crc32($host);
    }
    
    /**
     * @param string $url
     */
    private static function flushOutdatedCookie(string $url): void
    {
        $cookiePath = self::getCookieJar($url);
        if (file_exists($cookiePath) && !is_dir($cookiePath)) {
            // if file older than 20min (1200sec) delete it
            if(filemtime($cookiePath) < (time() - 700)) {
                unlink($cookiePath);
            }
        }
    }
}