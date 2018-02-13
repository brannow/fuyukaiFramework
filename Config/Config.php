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

namespace Config;

abstract class Config
{
    public const DB_HOST = 'host';
    public const DB_USER = 'user';
    public const DB_PASS = 'pass';
    public const DB_DATABASE = 'db';
    public const DB_PORT = 'port';
    
    public const CONTROLLER = 'c';
    public const METHOD = 'm';
    
    public const ROOT_TEMPLATE = 'rootTemplate';
    public const LOGIN_TEMPLATE = 'loginTemplate';
    public const CSS_ROOT_DIR = 'cssRoot';
    public const JS_ROOT_DIR = 'jsRoot';
    
    /**
     * @var array
     */
    private static $config = [
        // database config
        self::DB_HOST => '127.0.0.1',
        self::DB_USER => 'bk_user',
        self::DB_PASS => 'bk_pass',
        self::DB_DATABASE => 'bk_fuyukai',
        self::DB_PORT => 3306,
        
        // template config
        self::ROOT_TEMPLATE => '/Resources/Template/base.html',
        self::LOGIN_TEMPLATE => '/Resources/Template/login.html',
        
        self::CSS_ROOT_DIR => '/Resources/css',
        self::JS_ROOT_DIR => '/Resources/js',
    ];
    
    private static $routing = [
        // routing
        // Main Site
        '/' => [
            self::CONTROLLER => 'Src\Frontend\Test\Controller\TestController',
            self::METHOD => 'index'
        ],
        '/logout' => [
            self::CONTROLLER => 'Src\Frontend\Test\Controller\TestLoginController',
            self::METHOD => 'logoutUser'
        ],
        '/admin' => [
            self::CONTROLLER => 'Src\Frontend\Test\Controller\TestLoginController',
            self::METHOD => 'index'
        ]
    ];
    
    /**
     * @var array
     */
    private static $cliRouting = [
        'fancy-cronjob-stuff' => [
            self::CONTROLLER => 'Src\CLI\Test\Controller\UpdateStuffController',
            self::METHOD => 'updateAction'
        ]
    ];
    
    /**
     * @param string $nodeName
     * @return mixed
     */
    public static function getConfigEntry(string $nodeName)
    {
        if(isset(static::$config[$nodeName])) {
            return static::$config[$nodeName];
        }
        
        return '';
    }
    
    /**
     * @param string $path
     * @return array
     */
    public static function getRoutingEntry(string $path): array
    {
        $config = [];
        if (isset(static::$routing[$path])) {
            $config = static::$routing[$path];
        }
        
        return $config;
    }
    
    /**
     * @param string $path
     * @return array
     */
    public static function getCLIRoutingEntry(string $path): array
    {
        $config = [];
        if (isset(static::$cliRouting[$path])) {
            $config = static::$cliRouting[$path];
        }
    
        return $config;
    }
}
