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

namespace Fuyukai\Core;


class Response
{
    /**
     * @var int
     */
    protected $status = 200;
    
    /**
     * @var string
     */
    protected $content = '';
    
    /**
     * @var string
     */
    protected $type = '';
    
    protected $headers = [];
    
    /**
     * Response constructor.
     * @param string $content
     * @param int $code
     */
    public function __construct(string $content, int $code = 200)
    {
        $this->setContent($content);
        $this->setStatusCode($code);
        
        $this->headers['Cache-Control'] = 'public';
        $this->headers['Pragma'] = 'no-cache';
        $this->headers['Expires'] = '0';
        $this->headers['Accept-Ranges'] = 'bytes';
        $this->headers['Date'] = gmdate('D, d M Y H:i:s T');
    }
    
    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
        if ($type) {
            $this->headers['Content-Type'] = $this->getType();
        }
    }
    
    /**
     * @param int $code
     */
    public function setStatusCode(int $code): void
    {
        $this->status = $code;
        $this->headers['statusCode'] = $code;
    }
    
    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }
    
    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->headers['Content-length'] = strlen($this->getContent());
    }
    
    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }
    
    /**
     * @param array $customHeader
     */
    public function setCustomHeader(array $customHeader): void
    {
        $this->headers = array_merge($this->headers, $customHeader);
    }
    
    /**
     *
     */
    protected function sendHeaders(): void
    {
        if (!headers_sent()) {
            
            if (isset($this->headers['statusCode'])) {
                http_response_code($this->headers['statusCode']);
                unset($this->headers['statusCode']);
            }
            
            foreach ($this->headers as $key => $value) {
                header($key . ': ' . $value);
            }
        }
    }
    
    /**
     *
     */
    public function sendContent(): void
    {
        echo $this->content;
    }
    
    /**
     *
     */
    public function send(): void
    {
        $this->sendHeaders();
        $this->sendContent();
    }
}