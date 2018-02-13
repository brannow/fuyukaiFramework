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

namespace Fuyukai\Extensions\User\Service;


use Fuyukai\Extensions\User\Domain\Enum\StatusCodes;
use Fuyukai\Userspace\Secure\Encryption;
use Fuyukai\Userspace\Secure\PasswordHashing;
use Fuyukai\Userspace\Session\SessionHandler;
use Fuyukai\Extensions\User\Domain\Model\User;
use Fuyukai\Extensions\User\Domain\Repository\UserRepository;

class LoginService
{
    // in microseconds
    private const TIMEOUT = 1000000;
    
    /**
     * @var SessionHandler
     */
    private $session = null;
    
    /**
     * @var UserRepository
     */
    private $userRepository = null;
    
    /**
     * @var string
     */
    private $spoofProtectionRequestKey = '';
    
    /**
     * @var int
     */
    private $error = StatusCodes::STATUS_NONE;
    
    /**
     *
     */
    public function init(): void
    {
        $this->getSession()->init();
    }
    
    /**
     * @param string $spoofKey
     */
    public function setSpoofProtectionRequestKey(string $spoofKey): void
    {
        $this->spoofProtectionRequestKey = $spoofKey;
    }
    
    /**
     * @return null|User
     */
    public function recoverUserFromSession(): ?User
    {
        $userId = (int)$this->getSession()->getValue('id', 'user');
        if ($userId) {
            if (!$this->isSessionSpoofed()) {
                if (!$this->getSession()->isExpired()) {
                    $user = $this->getUserRepository()->findUserById($userId);
                    
                    if ($user) {
                        $this->getSession()->updateExpireTimeout();
                        return $user;
                    }
                } else {
                    $this->setErrorCode(StatusCodes::STATUS_LOGIN_EXPIRED);
                }
            }
            
            // wipe session if session data exist but they are invalid or expired
            $this->getSession()->wipe();
        }
        
        return null;
    }
    
    /**
     * @return bool
     */
    private function isSessionSpoofed(): bool
    {
        $spoofProtection = $this->getSession()->getValue('spoofProtection', 'user');
        if ($spoofProtection) {
            
            // md5 HTTP_X_FORWARDED_FOR + REMOTE_ADDR
            return !($spoofProtection === $this->spoofProtectionRequestKey);
        }
        
        return true;
    }
    
    /**
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function tryCreateUserSession(string $username, string $password): bool
    {
        $timingAttackPrevent = microtime(true);
        
        if (!empty($username) && !empty($password)) {
            $userHashData = $this->getUserRepository()->findUserHashByUsername($username);
            if ($userHashData && !empty($userHashData['id']) && !empty($userHashData['password'])) {
                $userId = (int)$userHashData['id'];
                $passwordHash = $userHashData['password'];
                if (PasswordHashing::validatePassword($password, $passwordHash)) {
                    unset($password);
                    if ($this->createUserSession($userId)) {
                        return true;
                    } else {
                        $this->setErrorCode(StatusCodes::STATUS_LOGIN_INTERNAL_ERROR);
                    }
                } else {
                    $this->setErrorCode(StatusCodes::STATUS_LOGIN_PASSWORD_MISMATCH);
                }
            } else {
                $this->setErrorCode(StatusCodes::STATUS_LOGIN_USERNAME_MISMATCH);
            }
        } else {
            $this->setErrorCode(StatusCodes::STATUS_LOGIN_INVALID_CREDENTIALS);
        }
        
        
        // timing attack protection - every fail request ends in a sleep
        $timingAttackPrevent = (microtime(true) - $timingAttackPrevent) * static::TIMEOUT;
        usleep((int)(static::TIMEOUT - $timingAttackPrevent));
        
        return false;
    }
    
    /**
     * @param int $userId
     * @return bool
     */
    public function createUserSession(int $userId): bool
    {
        if ($this->spoofProtectionRequestKey && $userId > 0) {
            $this->getSession()->updateExpireTimeout();
            $this->getSession()->setValue((string)$userId, 'id', 'user');
            $this->getSession()->setValue($this->spoofProtectionRequestKey, 'spoofProtection', 'user');
            
            return true;
        }
        
        return false;
    }
    
    /**
     *
     */
    public function destroySession(): void
    {
        $this->getSession()->wipe();
    }
    
    /**
     * @param string $requestedToken
     * @return bool
     */
    public function validateCSRFToken(string $requestedToken): bool
    {
        $sessionToken = $this->getSession()->getValue('csrf', 'login');
        $this->getSession()->setValue('', 'csrf', 'login');
        
        return (strlen($requestedToken) === 64 && $requestedToken === $sessionToken);
    }
    
    /**
     * @return string
     */
    public function generateCSRFToken(): string
    {
        // generates 64 byte (char[64]) token
        $newToken = Encryption::generateCSRFToken();
        $this->getSession()->setValue($newToken, 'csrf', 'login');
        return $newToken;
    }
    
    /**
     * @return SessionHandler
     */
    private function getSession(): SessionHandler
    {
        if (!$this->session) {
            $this->session = new SessionHandler();
        }
        return $this->session;
    }
    
    /**
     * @return UserRepository
     */
    private function getUserRepository(): UserRepository
    {
        if (!$this->userRepository) {
            $this->userRepository = new UserRepository();
        }
        return $this->userRepository;
    }
    
    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->error;
    }
    
    /**
     * @param int $code
     */
    public function setErrorCode(int $code): void
    {
        $this->error = $code;
    }
}