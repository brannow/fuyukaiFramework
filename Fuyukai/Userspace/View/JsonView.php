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

namespace Fuyukai\Userspace\View;


class JsonView extends View
{
    /**
     * @var bool
     */
    public static $supportHTML = false;
    
    /**
     * @var array
     */
    private $jsonData = [];
    
    /**
     * @return array
     */
    public function getHeader(): array
    {
        return [
            'Content-Type' => 'application/json;charset=utf-8'
        ];
    }
    
    /**
     * @param array $jsonData
     */
    public function setJsonData(array $jsonData): void
    {
        $this->jsonData = $jsonData;
    }
    
    /**
     * @param string $key
     * @param mixed $value
     */
    public function assign(string $key, $value): void
    {
        $this->jsonData[$key] = $value;
    }
    
    /**
     * @param array $keyValueArray
     */
    public function assignMultiple(array $keyValueArray): void
    {
        foreach ($keyValueArray as $key => $value) {
            $this->assign($key, $value);
        }
    }
    
    /**
     * @return string
     */
    public function render(): string
    {
        return json_encode($this->jsonData);
    }
}