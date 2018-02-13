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

namespace Fuyukai\Extensions\User\Controller;


use Config\Config;
use Fuyukai\Extensions\User\Domain\Enum\StatusCodes;
use Fuyukai\Extensions\User\Domain\Model\User;
use Fuyukai\Extensions\User\Service\LoginService;
use Fuyukai\Userspace\Controller\AbstractController;

abstract class LoginController extends AbstractController
{
    /**
     * @var User
     */
    private $user = null;
    
    /**
     * @var LoginService
     */
    private $loginService;
    
    /**
     * @var string
     */
    protected static $usernameKey = 'username';
    
    /**
     * @var string
     */
    protected static $passwordKey = 'password';
    
    /**
     * @return null|User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
    
    /**
     * @param string $templatePath
     */
    protected function initialize(string $templatePath = ''): void
    {
        if (!$this->getRequest()) {
            die('0x000007');
        }
        
        $this->getLoginService()->init();
        
        if ($this->tryLoginUser()) {
            // user is logged in - continue with actual workflow
            parent::initialize($templatePath);
        } else {
            // interrupt workflow, show login mask
            $this->initializeView((string)Config::getConfigEntry(Config::LOGIN_TEMPLATE));
        }
    }
    
    /**
     * @param string $methodName
     * @return string
     */
    public function callAction(string $methodName): string
    {
        // user found call actual method
        if ($this->getUser() !== null) {
            $this->assign('username', htmlentities($this->getUser()->getUsername()));
            return parent::callAction($methodName);
        }
    
        // call login method instead
        return (string)$this->loginAction();
    }
    
    /**
     *
     */
    public function logoutUser()
    {
        $this->getLoginService()->destroySession();
        $this->redirect('/');
    }
    
    /**
     *
     */
    protected function loginAction()
    {
        $this->getView()->injectCss('fuyukai.css');
        $this->getView()->injectJs('fuyukai.js');
        $this->assign('flashMessage', StatusCodes::statusCodeToMessage($this->getLoginService()->getErrorCode()));
        $this->assign('__RequestVerificationToken', $this->getLoginService()->generateCSRFToken());
    }
    
    /**
     *
     */
    private function tryLoginUser(): bool
    {
        // is already logged in look for a session and try to fetch user object
        $this->getLoginService()->setSpoofProtectionRequestKey($this->getRequest()->getClientIPHash());
        $this->user = $this->getLoginService()->recoverUserFromSession();
        if ($this->getUser()) {
            return true;
        }
        
        // not already logged in but maybe he tried to login?
        if ($this->getRequest()->isPostRequest()) {
            $csrf = $this->getRequest()->getPostData('__RequestVerificationToken');
            if ($this->getLoginService()->validateCSRFToken($csrf)) {
                
                $username = $this->getRequest()->getPostData('username');
                $password = $this->getRequest()->getPostData('password');
                if ($this->getLoginService()->tryCreateUserSession($username, $password)) {
                    $this->redirect($this->getRequest()->getPath());
                }
            }
        }
        
        return false;
    }
    
    /**
     * @return LoginService
     */
    private function getLoginService(): LoginService
    {
        if (!$this->loginService) {
            $this->loginService = new LoginService();
        }
    
        return $this->loginService;
    }
}