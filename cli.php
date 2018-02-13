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

if (php_sapi_name() === 'cli' || php_sapi_name() === 'cgi-fcgi') {
    
    /**
     * @param array $argv
     * @param int $argc
     * @return array
     */
    function parseArguments(array $argv, int $argc): array
    {
        $list = [];
        for ($i = 1; $i < $argc; $i = $i + 2) {
            
            $rawKey = $argv[$i];
            $key = str_replace('-', '', trim($rawKey));
            
            $value = '';
            if (isset($argv[$i+1])) {
                $rawValue = $argv[$i+1];
                $value = trim($rawValue);
            }
            
            $list[$key] = $value;
        }
        
        return $list;
    }
    
    $compute = function(array $argv = [], int $argc = 0) {
        $arguments = parseArguments($argv, $argc);
        if (!empty($arguments['key']) && !empty($arguments['cmd'])) {
            $key = (string)$arguments['key'];
            $cmd = (string)$arguments['cmd'];
            unset($arguments['key']);
            unset($arguments['cmd']);
            
            // key can be hard coded cuz it's only on our local system used, if someone has access to the file direcly
            // and can execute a shell command, the key is worthless,
            // it's only for my peace of mind ;)
            if ($key === 'K3Pgjt6794A47qe43y8X') {
                // update doc-root for autoloader
                $currentRootDirectory = realpath(dirname(__FILE__));
                $_SERVER['DOCUMENT_ROOT'] = $currentRootDirectory;
                require $currentRootDirectory . DIRECTORY_SEPARATOR . 'Fuyukai' . DIRECTORY_SEPARATOR . 'autoloader.php';
                $kernel = new \Fuyukai\Kernel(\Fuyukai\Kernel::MODE_CLI);
                $kernel->setCLIRoutePath($cmd);
                $kernel->execute();
                $kernel->shutdown();
            }
        }
    };
    $compute($argv, $argc);
    unset($compute);
}
