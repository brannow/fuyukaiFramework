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

namespace Fuyukai\Userspace\Domain\Repository;


use Fuyukai\Core\Database\Connection;
use Fuyukai\Userspace\Domain\Model\BaseModel;

abstract class BaseRepository
{
    public const MYSQL_DATETIME_FORMAT = 'Y-m-d H:i:s';
    
    protected const INSERTS = 'i';
    protected const UPDATES = 'u';
    
    /**
     * @var Connection
     */
    private static $connection = null;
    
    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {
    
    }
    
    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        if (self::$connection === null) {
            self::$connection = new Connection();
        }
        
        return self::$connection;
    }
    
    /**
     * @param string $class
     * @param string $table
     * @param array $select
     * @param array $where
     * @param array $order
     * @param int $limit
     * @return array
     */
    protected function selectModel(string $class, string $table, array $select = [], array $where = [], array $order = [], int $limit = 0): array
    {
        $whereString = '';
        $orderString = '';
        $limitString = '';
        $params = [];
        $selectLine = [];
        if (!$select) {
            $selectLine[] = 't1.*';
        }
    
        $whereLine = [];
        foreach ($where as $column => $value) {
            if (is_array($value)) {
                $whereLine[] = 't1.`'.$column.'` IN ('. implode(',', $value) .')';
            } else {
                $whereLine[] = 't1.`'.$column.'`=?';
            }
            $params[] = $value;
        }
        if ($whereLine) {
            $whereString = ' WHERE '.implode(' AND ',$whereLine).' ';
        }
        unset($whereLine);
    
        $orderLine = [];
        foreach ($order as $column => $value) {
            $orderLine[] = 't1.`'.$column.'` '. $value;
        }
        if ($orderLine) {
            $orderString = ' ORDER BY '.implode(',',$orderLine).' ';
        }
        unset($orderLine);
        
        if ($limit > 0) {
            $limitString = ' LIMIT ? ';
            $params[] = $limit;
        }
        
        $resultData = $this->getConnection()->fetchQuery(
            '
            SELECT '. implode(',', $selectLine) .'
            FROM `'.$table.'` t1
            '.$whereString.$orderString.$limitString,
            ...$params
        );
        
        return $this->hydrateArray($class, $resultData);
    }
    
    /**
     * @param string $table
     * @param array $columns
     * @param array $where
     * @param BaseModel[] ...$baseModels
     */
    protected function updateModel(string $table, array $columns, array $where = [], BaseModel ...$baseModels): void
    {
        /** @var BaseModel $baseModel */
        foreach ($baseModels as $baseModel) {
            $baseModel->preDatabaseHook();
            $params = [];
            $whereParams = [];
            $whereLine = [];
            $setLine = [];
            foreach ($columns as $column) {
                $suggestedGetter = 'get' . $this->databaseColumnNameToMethodName($column);
                if (method_exists($baseModel, $suggestedGetter)) {
                    $value = $baseModel->$suggestedGetter();
                    if ($value instanceof \DateTime) {
                        $value = $value->format(static::MYSQL_DATETIME_FORMAT);
                    }
                    if(substr($column, -3) === '_id' && $value === 0) {
                        $value = NULL;
                    }
                    
                    $params[] = $value;
                    $setLine[] = '`'.$column.'`=?';
                }
            }
    
            foreach ($where as $w) {
                $suggestedGetter = 'get' . $this->databaseColumnNameToMethodName($w);
                if (method_exists($baseModel, $suggestedGetter)) {
                    $value = $baseModel->$suggestedGetter();
                    if ($value instanceof \DateTime) {
                        $value = $value->format(static::MYSQL_DATETIME_FORMAT);
                    }
                    $whereParams[] = $value;
                    $whereLine[] = '`'.$w.'`=?';
                }
            }
            
            if ($whereLine && $whereParams) {
                $this->getConnection()->updateQuery(
                    'UPDATE `'.$table.'`
                              SET '.implode(',', $setLine).'
                              WHERE '.implode(' AND ', $whereLine),
                    ...$params,
                    ...$whereParams
                );
            } else {
                $this->getConnection()->updateQuery(
                    'UPDATE `'.$table.'`
                              SET '.implode(',', $setLine),
                    ...$params
                );
            }
            $baseModel->updateSnapshotId();
            $baseModel->postDatabaseHook();
        }
    }
    
    /**
     * @param string $table
     * @param array $columns
     * @param BaseModel[] ...$baseModels
     * @return bool
     */
    protected function insertModel(string $table, array $columns, BaseModel ...$baseModels): bool
    {
        if ($baseModels && $table && $columns) {
            $columnList = '(`'. implode('`,`', $columns) .'`)';
            $valueList = '('. implode(',', array_fill(0, count($columns), '?')) .')';
            $insertStatementValue = [];
            $params = [];
            /** @var BaseModel $baseModel */
            foreach ($baseModels as $baseModel) {
                
                $baseModel->preDatabaseHook();
                
                foreach ($columns as $column) {
                    $suggestedGetter = 'get' . $this->databaseColumnNameToMethodName($column);
                    if (method_exists($baseModel, $suggestedGetter)) {
                        $value = $baseModel->$suggestedGetter();
                        if ($value instanceof \DateTime) {
                            $value = $value->format(static::MYSQL_DATETIME_FORMAT);
                        }
                    } else {
                        $value = 0;
                    }
    
                    if(substr($column, -3) === '_id' && $value === 0) {
                        $value = NULL;
                    }
                    $params[] = $value;
                }
                $insertStatementValue[] = $valueList;
            }
        
            if ($insertStatementValue && $params) {
                $createdIds = $this->getConnection()->insertQuery(
                    'INSERT INTO `'. $table .'` '. $columnList .' VALUES '.implode(',', $insertStatementValue),
                    ...$params
                );
                if (count($createdIds) === count($baseModels)) {
                    foreach ($baseModels as $key => $baseModel) {
                        if (isset($createdIds[$key])) {
                            $baseModel->__setId((int)$createdIds[$key]);
                            $baseModel->updateSnapshotId();
                        }
                        
                        $baseModel->postDatabaseHook();
                    }
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * @param BaseModel[] ...$baseModels
     * @return array
     */
    protected function splitIntoInsertUpdate(BaseModel ...$baseModels): array
    {
        $inserts = [];
        $updates = [];
        /** @var BaseModel $baseModel */
        foreach ($baseModels as $baseModel) {
        
            // no changes, skip this one for updates
            if($baseModel->compareSnapshotId()) {
                continue;
            }
        
            if ($baseModel->getId() > 0) {
                $updates[] = $baseModel;
            } else {
                $inserts[] = $baseModel;
            }
        }
        
        return [
            self::INSERTS => $inserts,
            self::UPDATES => $updates,
        ];
    }
    
    /**
     * @param string $class
     * @param array $dataSets
     * @return array
     */
    protected function hydrateArray(string $class, array $dataSets): array
    {
        $hydratedList = [];
        foreach ($dataSets as $dataSet) {
            $hydratedList[] = $this->hydrateObject($class, $dataSet);
        }
        
        return array_filter($hydratedList);
    }
    
    /**
     * @param string $class
     * @param array $dataSet
     * @return object|null
     */
    protected function hydrateObject(string $class, array $dataSet): ?BaseModel
    {
        /** @var BaseModel $object */
        $object = null;
        if (class_exists($class)) {
            
            if (!empty($dataSet['id'])) {
                $object = new $class((int)$dataSet['id']);
                unset($dataSet['id']);
            } else {
                $object = new $class();
            }
            
            foreach ($dataSet as $property => $value) {
                
                $suggestedSetter = 'set' . $this->databaseColumnNameToMethodName($property);
                if ($value && method_exists($object, $suggestedSetter)) {
                    $object->$suggestedSetter($value);
                }
            }
            
            $object->updateSnapshotId();
        }
        
        return $object;
    }
    
    /**
     * @param string $columnName
     * @return string
     */
    private function databaseColumnNameToMethodName(string $columnName): string
    {
        $methodName = '';
        $camels = explode('_',  $columnName);
        foreach ($camels as $camel) {
            $methodName .= ucfirst($camel);
        }
        
        return $methodName;
    }
}