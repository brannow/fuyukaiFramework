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

namespace Fuyukai\Userspace\Parser;


use Fuyukai\Userspace\Curl\CurlLoader;

abstract class BaseParser
{
    use CurlLoader;
    
    protected const CONTENT_TYPE = '';
    
    protected const DO_NOT_PARSE_ID = -99;
    
    protected static $resultCache = [];
    
    protected static $customCookieData = [];
    
    /**
     * @param string $url
     * @param array $queryParams
     * @param array $postParams
     * @param string $contentType
     * @param int $id
     * @return array
     */
    protected static function execute(string $url ,array $queryParams = [], array $postParams = [], $contentType = self::CONTENT_TYPE, int $id = 0): array
    {
        if ($contentType === self::CONTENT_TYPE) {
            $contentType = static::CONTENT_TYPE;
        }
        
        $rawResult = self::executeRequest($url, $queryParams, $postParams, $contentType, static::$customCookieData);
    
        $data = [];
        if ($id !== self::DO_NOT_PARSE_ID) {
            $data = static::parseResult($rawResult, $id);
        }
        
        unset($rawResult);
        return $data;
    }
    
    /**
     * @param string $rawResult
     * @param int $id
     * @return array
     */
    protected static function parseResult(string $rawResult, int $id): array
    {
        return [];
    }
}