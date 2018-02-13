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


use Config\Config;

class View
{
    private const BASE_CONTENT_PLACEHOLDER = 'base_content';
    private const BASE_STYLE_PLACEHOLDER = 'base_style';
    private const BASE_SCRIPT_PLACEHOLDER = 'base_script';
    
    /**
     * @var bool
     */
    public static $supportHTML = true;
    
    /**
     * @var array
     */
    private $viewPlaceholder = [];
    
    /**
     * @var string
     */
    private $templatePath = '';
    
    /**
     * @var array
     */
    private $cssFiles = [];
    
    /**
     * @var array
     */
    private $jsFiles = [];
    
    /**
     * View constructor.
     * @param string $templateRawPath
     */
    public function __construct(string $templateRawPath)
    {
        $this->templatePath = $templateRawPath;
    }
    
    /**
     * @param string $path
     * @param bool $external
     */
    public function injectJs(string $path, bool $external = false): void
    {
        if ($path) {
            if (!$external) {
                $rootDir = Config::getConfigEntry(Config::JS_ROOT_DIR);
                $relPath = rtrim($rootDir, '/') . DIRECTORY_SEPARATOR . ltrim($path, '/');
                $this->jsFiles[] = $relPath;
            } else {
                $this->jsFiles[] = $path;
            }
        }
    }
    
    /**
     * @param string $path
     * @param bool $external
     */
    public function injectCss(string $path, bool $external = false): void
    {
        if ($path) {
            if (!$external) {
                $rootDir = Config::getConfigEntry(Config::CSS_ROOT_DIR);
                $relPath = rtrim($rootDir, '/') . DIRECTORY_SEPARATOR . ltrim($path, '/');
                $this->cssFiles[] = $relPath;
            } else {
                $this->cssFiles[] = $path;
            }
        }
    }
    
    /**
     * @return string
     */
    public function getTemplatePath(): string
    {
        return $this->templatePath;
    }
    
    /**
     * @param string $key
     * @param string $value
     */
    private function addViewPlaceholder(string $key, string $value): void
    {
        $this->viewPlaceholder[$key] = (string)$value;
    }
    
    /**
     * @param string $key
     * @param mixed $value
     */
    public function assign(string $key, $value): void
    {
        if ($key) {
            $this->addViewPlaceholder($key, (string)$value);
        }
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
     * @return array
     */
    public function getVariables(): array
    {
        return $this->viewPlaceholder;
        
    }
    
    /**
     * @return array
     */
    public function getHeader(): array
    {
        return [];
    }
    
    /**
     * @return string
     */
    public function render(): string
    {
        $content = '';
    
        if ($this->getTemplatePath()) {
            // load action template
            $templateBuffer = '';
            $templatePath = $_SERVER['DOCUMENT_ROOT'] . $this->getTemplatePath();
            if ($templatePath && file_exists($templatePath) && !is_dir($templatePath)) {
                $templateBuffer = file_get_contents($templatePath);
            }
    
            // look for a baseTemplate config
            $baseTemplateConfig = [];
            preg_match('/{@base:(.+?)}/s', $templateBuffer, $baseTemplateConfig);
            if (count($baseTemplateConfig) > 1) {
                $baseTemplatePath = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . trim($baseTemplateConfig[1], '/');
            } else {
                $baseTemplatePath = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . trim(Config::getConfigEntry(Config::ROOT_TEMPLATE));
            }
            
            // load base template and merge with action template
            if ($baseTemplatePath && file_exists($baseTemplatePath) && !is_dir($baseTemplatePath)) {
                
                $baseTemplate = file_get_contents($baseTemplatePath);
                // inject CSS FILES
                $baseTemplate = str_replace(
                    '{@'. static::BASE_STYLE_PLACEHOLDER .'}',
                    $this->generateCssTags(),
                    $baseTemplate
                );
    
                // inject JS FILES
                $baseTemplate = str_replace(
                    '{@'. static::BASE_SCRIPT_PLACEHOLDER .'}',
                    $this->generateJsTags(),
                    $baseTemplate
                );
                
                $templateBuffer = str_replace(
                    '{@'. static::BASE_CONTENT_PLACEHOLDER .'}',
                    $templateBuffer,
                    $baseTemplate
                );
            }
            // replace placeholder with assign config
            $patterns = [];
            foreach ($this->getVariables() as $key => $value) {
                $patterns['/{@('.$key.')}/'] = $value;
            }
            if ($patterns) {
                $templateBuffer = preg_replace(array_keys($patterns), array_values($patterns), $templateBuffer);
            }
            
            // remove all un-replaced placeholder
            $re = '/{@(.+?)}/';
            $content = preg_replace($re, '', $templateBuffer);
        }

        return $content;
    }
    
    /**
     * @return string
     */
    private function generateCssTags(): string
    {
        $styleTags = '';
        foreach ($this->cssFiles as $cssFile) {
            $styleTags .= '<link rel="stylesheet"  href="'.$cssFile.'">';
        }
        
        return $styleTags;
    }
    
    /**
     * @return bool
     */
    public function supportHTML(): bool
    {
        return static::$supportHTML;
    }
    
    /**
     * @return string
     */
    private function generateJsTags(): string
    {
        $scriptTags = '';
        foreach ($this->jsFiles as $jsFile) {
            $scriptTags .= '<script src="'.$jsFile.'"></script>';
        }
    
        return $scriptTags;
    }
}
