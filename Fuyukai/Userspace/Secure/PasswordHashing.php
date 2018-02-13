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

namespace Fuyukai\Userspace\Secure;


abstract class PasswordHashing
{
    private const ALGO = PASSWORD_BCRYPT;
    
    /**
     * @param string $rawPassword
     * @return string
     */
    public static function hashPassword(string $rawPassword): string
    {
        return password_hash($rawPassword, self::ALGO);
    }
    
    /**
     * @param string $rawPassword
     * @param string $hash
     * @return bool
     */
    public static function validatePassword(string $rawPassword, string $hash): bool
    {
        return password_verify($rawPassword, $hash);
    }
}