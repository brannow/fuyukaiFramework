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

namespace Fuyukai\Extensions\User\Domain\Repository;


use Fuyukai\Extensions\User\Domain\Model\User;
use Fuyukai\Userspace\Domain\Repository\BaseRepository;

class UserRepository extends BaseRepository
{
    /**
     * @param string $username
     * @return bool
     */
    public function existUsername(string $username): bool
    {
        $result = $this->getConnection()->fetchQuery(
            'SELECT t1.`id`
                            FROM `user` t1
                            WHERE
                            t1.`username`=?
                            LIMIT 1',
            $username
        );
        
        return !empty($result);
    }
    
    /**
     * @param User $user
     */
    public function deleteUser(User $user): void
    {
        $this->getConnection()->updateQuery('DELETE FROM `user` WHERE `id` = ? ', $user->getId());
    }
    
    /**
     * @return array
     */
    public function findAll(): array
    {
        return $this->selectModel(
            User::class,
            'user'
        );
    }
    
    /**
     * @param int $id
     * @return null|User
     */
    public function findUserById(int $id): ?User
    {
        $user = $this->selectModel(
            User::class,
            'user',
            [],
            ['id' => $id],
            [],
            1
        );
    
        if ($user) {
            return $user[0];
        }
    
        return null;
    }
    
    /**
     * @param User[] ...$users
     * @return bool
     */
    public function updateUser(User ...$users) :bool
    {
        $split = $this->splitIntoInsertUpdate(...$users);
        if ($split[self::UPDATES]) {
            $this->updateModel(
                'user',
                ['email', 'password'],
                ['id'],
                ...$split[self::UPDATES]
            );
        }
        
        if ($split[self::INSERTS]) {
            $this->insertModel(
                'user',
                ['level', 'email', 'password', 'username'],
                ...$split[self::INSERTS]
            );
        }
        
        return true;
    }
    
    /**
     * @param string $username
     * @return array
     */
    public function findUserHashByUsername(string $username): array
    {
        $result = $this->getConnection()->fetchQuery(
            'SELECT t1.`id`, t1.`password` FROM `user` t1
            WHERE t1.`username` = ?
            LIMIT 1;',
            $username
        );
    
        if ($result) {
            return $result[0];
        }
    
        return [];
    }
}