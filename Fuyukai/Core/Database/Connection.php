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

namespace Fuyukai\Core\Database;


use Config\Config;
use Fuyukai\Kernel;

class Connection
{
    /**
     * @var \mysqli
     */
    private static $mysql = null;
    
    private static $bindMap = [
        'boolean' => 'i',
        'integer' => 'i',
        'double' => 'd',
        'string' => 's',
        'array' => false,
        'object' => false,
        'resource' => false,
        'NULL' => 'i',
        'unknown type' => false
    ];
    
    private static $queryDebug = [];
    
    /**
     * Connection constructor.
     */
    public function __construct()
    {
        if (!static::$mysql) {
            $host = Config::getConfigEntry(Config::DB_HOST);
            $user = Config::getConfigEntry(Config::DB_USER);
            $pass = Config::getConfigEntry(Config::DB_PASS);
            $db = Config::getConfigEntry(Config::DB_DATABASE);
            $port = Config::getConfigEntry(Config::DB_PORT);
            static::$mysql = @new \mysqli($host, $user, $pass, $db, $port);
    
            if (!static::$mysql) {
                die('0x000004');
            }
            
            if (static::$mysql && static::$mysql->connect_errno !== 0) {
                if (Kernel::isDev()) {
                    print_r(static::$mysql->connect_error);
                }
                die('0x000004.1');
            }
            
            static::$mysql->set_charset('utf8');
        }
    }
    
    /**
     * @param string $statement
     * @param array ...$params
     * @return array
     */
    public function fetchQuery(string $statement, ...$params): array
    {
        $t = 0;
        if (Kernel::isDev()) {
            $t = microtime(true);
        }
        $stmt = $this->executeStatement($statement, ...$params);
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $result->close();
        $stmt->close();
    
        if (Kernel::isDev() && $t) {
            $time = microtime(true) - $t;
            self::$queryDebug[] = [
                'statement' => $statement,
                'time' => $time
            ];
        }
        
        return $data;
    }
    
    /**
     * @param string $statement
     * @param array ...$params
     */
    public function updateQuery(string $statement, ...$params): void
    {
        $t = 0;
        if (Kernel::isDev()) {
            $t = microtime(true);
        }
        $stmt = $this->executeStatement($statement, ...$params);
        $stmt->close();
        if (Kernel::isDev() && $t) {
            $time = microtime(true) - $t;
            self::$queryDebug[] = [
                'statement' => $statement,
                'time' => $time
            ];
        }
    }
    
    /**
     * @param string $statement
     * @param array ...$params
     * @return array
     */
    public function insertQuery(string $statement, ...$params): array
    {
        $t = 0;
        if (Kernel::isDev()) {
            $t = microtime(true);
        }
        
        $stmt = $this->executeStatement($statement, ...$params);
        $firstInsertId = $stmt->insert_id;
        $numOfInserts = $stmt->affected_rows;
        $stmt->close();
        
        $insertIds = [];
        if ($numOfInserts > 0) {
            $limit = $firstInsertId + $numOfInserts;
            if ($numOfInserts < 100000) {
                for ($i = $firstInsertId; $i < $limit; ++$i) {
                    $insertIds[] = $i;
                }
            }
        }
    
        if (Kernel::isDev() && $t) {
            $time = microtime(true) - $t;
            self::$queryDebug[] = [
                'statement' => $statement,
                'time' => $time
            ];
        }
        
        return $insertIds;
    }
    
    /**
     * @param $statement
     * @param array ...$params
     * @return \mysqli_stmt
     */
    private function executeStatement($statement, ...$params): \mysqli_stmt
    {
        $stmt = static::$mysql->prepare(str_replace(["\n","\r","\t"], '', $statement));
    
        if ($stmt === false) {
            if (Kernel::isDev()) {
                var_dump(self::$mysql->error);
                var_dump($statement, $params);
                var_dump('##########################################');
                var_dump(self::$queryDebug);
            } else {
                $data = print_r(self::$mysql, true);
                $data .= print_r($statement, true);
                $data .= print_r($params, true);
                $data .= "\n##########################################\n";
                file_put_contents('sqlError.log', $data, FILE_APPEND);
            }
            
            die('0x000006');
        }
        
        $bindTypeList = [];
        $bindValueList = [];
        // generate typeList
        foreach ($params as $param) {
        
            $mysqlType = static::$bindMap[gettype($param)];
            if ($mysqlType) {
                $bindTypeList[] = $mysqlType;
                $bindValueList[] = $param;
            }
        }
    
        // bind only if list not empty
        if ($bindTypeList) {
            @$stmt->bind_param(implode('', $bindTypeList), ...$bindValueList);
        }
    
        if (!$stmt->execute() || $stmt->errno !== 0) {
            if (Kernel::isDev()) {
                var_dump($stmt->error_list);
                var_dump($statement, $params);
                var_dump('##########################################');
                var_dump(self::$queryDebug);
            } else {
                $data = print_r($stmt->error_list, true);
                $data .= print_r($statement, true);
                $data .= print_r($params, true);
                $data .= "\n##########################################\n";
                file_put_contents('sqlError.log', $data, FILE_APPEND);
            }
            $stmt->close();
            die('0x000005');
        }
        
        return $stmt;
    }
    
    /**
     * called at the end
     */
    public static function shutdown(): void
    {
        if (static::$mysql) {
            static::$mysql->close();
            static::$mysql = null;
            static::$queryDebug = [];
        }
    }
    
    /**
     * @param string $str
     * @return string
     */
    public function escape(string $str): string
    {
        if (static::$mysql) {
            
            return static::$mysql->escape_string($str);
        }
        
        return '';
    }
}