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

namespace Fuyukai\Userspace\Controller;


use Fuyukai\Core\Request;
use Fuyukai\Kernel;
use Fuyukai\Userspace\View\View;

abstract class AbstractController
{
    /**
     * @var int
     */
    protected $menuIndex = 0;
    
    /**
     * @var View
     */
    private $view = null;
    
    /**
     * @var string
     */
    protected $viewClass = '';
    
    /**
     * @var null|Request
     */
    private $request = null;
    
    /**
     * AbstractController constructor.
     * @param string $methodName
     * @param Request|null $request
     * @param int $kernelMode
     */
    public function __construct(string $methodName, Request $request = null, int $kernelMode = Kernel::MODE_DEFAULT)
    {
        $this->request = $request;
        $templatePath = '';
        if ($kernelMode === Kernel::MODE_DEFAULT) {
            $templatePath = static::class . '\\' . $methodName;
        }
        
        $this->initialize($templatePath);
    }
    
    /**
     * @param string $path
     */
    protected function redirect(string $path): void
    {
        Kernel::redirectResponse($path);
    }
    
    /**
     * @param string $templatePath
     */
    protected function initialize(string $templatePath = ''): void
    {
        if ($templatePath) {
            $segments = explode('\\', $templatePath);
            $modifiedSegments = [];
            foreach ($segments as $segment) {
                if ($segment === 'Controller') {
                    $modifiedSegments[] = 'Template';
                    continue;
                }
                $modifiedSegments[] = $segment;
            }
    
            $templatePath = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $modifiedSegments) . '.html';
        }
        
        $this->initializeView($templatePath);
    }
    
    /**
     * @param string $templatePath
     */
    protected function initializeView(string $templatePath = ''): void
    {
        if ($this->viewClass !== '' && class_exists($this->viewClass)) {
            $vc = $this->viewClass;
        } else {
            $vc = View::class;
        }
        
        $customView = new $vc($templatePath);
        if ($customView && $customView instanceof View) {
            $this->view = $customView;
        }
    }
    
    /**
     * @param string $methodName
     * @return string
     */
    public function callAction(string $methodName): string
    {
        if (method_exists($this, $methodName)) {
            return (string)$this->$methodName();
        }
        
        return '';
    }
    
    /**
     * @return View|null
     */
    public function getView(): ?View
    {
        return $this->view;
    }
    
    /**
     * @param string $key
     * @param mixed $value
     */
    public function assign(string $key, $value): void
    {
        if ($key && $this->getView()) {
            $this->getView()->assign($key, $value);
        }
    }
    
    /**
     * @param array $keyValueArray
     */
    public function assignMultiple(array $keyValueArray): void
    {
        if ($keyValueArray && $this->getView()) {
            $this->getView()->assignMultiple($keyValueArray);
        }
    }
    
    /**
     * @return Request|null
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }
    
    /**
     *
     */
    public function willRenderView(): void
    {
    
    }
    
    /**
     *
     */
    public function didRenderView(): void
    {
    
    }
}