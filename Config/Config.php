<?php declare(strict_types=1);

namespace Config;

abstract class Config
{
    public const SYSTEM_KEY = 'systemKey';
    
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
