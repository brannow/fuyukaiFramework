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


namespace Fuyukai;

use Config\Config;
use Fuyukai\Core\Database\Connection;
use Fuyukai\Core\Request;
use Fuyukai\Core\Response;
use Fuyukai\Userspace\Controller\AbstractController;
use Fuyukai\Userspace\View\View;

class Kernel
{
    public const MODE_DEFAULT = 0;
    public const MODE_CLI = 1;
    
    public const ENV_PROD = 0;
    public const ENV_DEV = 1;
    
    /**
     * @var Request
     */
    private $request = null;
    
    /**
     * @var Response
     */
    private $response = null;
    
    /**
     * @var int
     */
    private $mode = self::MODE_DEFAULT;
    
    /**
     * @var int
     */
    private static $env = self::ENV_PROD;
    
    /**
     * @var float
     */
    private $processingTime = 0.0;
    
    /**
     * Kernel constructor.
     * @param int $mode
     */
    public function __construct(int $mode = self::MODE_DEFAULT)
    {
        $this->processingTime = microtime(true);
        
        $this->request = new Request();
        if ($mode === static::MODE_DEFAULT || $mode === static::MODE_CLI) {
            $this->mode = $mode;
        }
        
        if ($this->request->getEnv() === Request::ENV_DEV) {
            static::$env = self::ENV_DEV;
        }
    }
    
    /**
     * @param string $path
     */
    public function setCLIRoutePath(string $path): void
    {
        if ($this->mode === self::MODE_CLI) {
            $this->request->setPath($path);
        }
    }
    
    /**
     * Main Logic
     */
    public function execute(): void
    {
        $result = '';
        $view = null;
        
        if ($this->mode === self::MODE_CLI) {
            $routingConfig = Config::getCLIRoutingEntry($this->request->getPath());
        } else {
            $routingConfig = Config::getRoutingEntry($this->request->getPath());
        }
        
        if ($routingConfig && class_exists($routingConfig[Config::CONTROLLER])) {
            $className = (string)$routingConfig[Config::CONTROLLER];
            $methodName = (string)$routingConfig[Config::METHOD];
            $object = new $className($methodName, $this->request, $this->mode);
            
            if ($object && $object instanceof AbstractController && $methodName) {
                $controllerResult = (string)$object->callAction($methodName);
                
                if ($controllerResult) {
                    $result = $controllerResult;
                } elseif ($this->mode === self::MODE_DEFAULT) {
                    $object->willRenderView();
                    $view = $object->getView();
                    if ($view && $view instanceof View) {
                        $result = $view->render();
                    }
                    $object->didRenderView();
                }
            }
        }
        
        $this->response = new Response($result);
        if ($this->mode === self::MODE_DEFAULT && $view && $view instanceof View) {
            $this->response->setCustomHeader($view->getHeader());
        }
    }
    
    /**
     * Called at the end of the lifetime
     */
    public function shutdown(): void
    {
        Connection::shutdown();
        
        if ($this->response && $this->mode === self::MODE_DEFAULT) {
            if (static::isDev()) {
                $this->response->setCustomHeader(['Fuyukai-Processing-Time' => (microtime(true) - $this->processingTime)]);
            }
            $this->response->send();
        } elseif ($this->response && $this->mode === self::MODE_CLI) {
            $this->response->sendContent();
        }
    }
    
    /**
     * @return bool
     */
    public static function isDev(): bool
    {
        return (bool)(static::$env === self::ENV_DEV);
    }
    
    /**
     * @param int $seconds
     */
    public static function increaseExecutionTime(int $seconds): void
    {
        set_time_limit($seconds);
        ini_set('max_execution_time', (string)$seconds);
    }
    
    /**
     * @param string $path
     */
    public static function redirectResponse(string $path): void
    {
        if ($path) {
            $redirectResponse = new Response('', 302);
            $redirectResponse->setCustomHeader(['Location' => $path]);
            $redirectResponse->send();
            die();
        }
    }
}
