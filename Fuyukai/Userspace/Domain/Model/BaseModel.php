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

namespace Fuyukai\Userspace\Domain\Model;


abstract class BaseModel
{
    /**
     * @var int
     */
    private $id = 0;
    
    private $snapshotId = 0;
    
    /**
     * BaseModel constructor.
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        $this->id = $id;
    }
    
    /**
     * @param int $id
     */
    public function __setId(int $id): void
    {
        $this->id = $id;
    }
    
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * @return bool
     */
    public function compareSnapshotId(): bool
    {
        return ($this->snapshotId === $this->generateSnapshotId());
    }
    
    /**
     *
     */
    public function updateSnapshotId(): void
    {
        $this->snapshotId = $this->generateSnapshotId();
    }
    
    /**
     *
     */
    public function preDatabaseHook(): void
    {
    
    }
    
    /**
     *
     */
    public function postDatabaseHook(): void
    {
    
    }
    
    /**
     * @return int
     */
    private function generateSnapshotId(): int
    {
        $methodNames = get_class_methods(static::class);
        $values = [];
        foreach ($methodNames as $methodName) {
            if(strpos($methodName, 'get') === 0) {
                $var = $this->$methodName();
                if ($var === null || is_scalar($var) || (is_object($var) && method_exists($var, '__toString'))) {
                    $values[] = crc32((string)$var . '//' . $methodName);
                }
            }
        }
    
        return crc32(json_encode($values));
    }
}