<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */

class eZRedisDB extends eZDBInterface
{
    public $timeout = 1;
    public $delayConnexion = 0.01;

    public function version()
    {
        return "0.0.1";
    }

    public function __construct($parameters)
    {
        $this->eZDBInterface($parameters);

        if (!extension_loaded('redis')) {
            if (function_exists('eZAppendWarningItem')) {
                eZAppendWarningItem(array( 'error' => array( 'type' => 'ezredisdb',
                                                              'number' => eZDBInterface::ERROR_MISSING_EXTENSION ),
                                            'text' => 'eZRedis extension was not found, the DB handler will not be initialized.' ));
                $this->IsConnected = false;
            }
            eZDebug::writeWarning('eZRedis extension was not found, the DB handler will not be initialized.', 'eZRedisDB');
            return;
        }
        eZDebug::createAccumulatorGroup('redis_total', 'Redis Total');
        /// Connect to master server
        if (!$this->DBWriteConnection) {
            $connection = $this->connect($this->Server, $this->Port);
            if ($this->IsConnected) {
                $this->DBWriteConnection = $connection;
            }
        }

        // Initialize TempTableList
        $this->TempTableList = array();
    }
    /**
     * [connect description]
     * @param  [type]                   $server     [description]
     * @param  [type]                   $user       [description]
     * @param  [type]                   $password   [description]
     * @param  [type]                   $socketPath [description]
     * @param  boolean                  $port       [description]
     * @return [type]                               [description]
     * @date   2015-01-01T16:57:10+0100
     * $redis = new Redis();
     * $redis->connect('127.0.0.1', 6379);
     * $redis->connect('127.0.0.1'); // port 6379 by default
     * $redis->connect('127.0.0.1', 6379, 2.5); // 2.5 sec timeout.
     * $redis->connect('/tmp/redis.sock'); // unix domain socket.
     * $redis->connect('127.0.0.1', 6379, 1, NULL, 100); // 1 sec timeout, 100ms delay between reconnection attempts.
     */
    public function connect($server, $port = false)
    {
        $connection = false;
        if ($this->UsePersistentConnection == true) {
            // Only supported on PHP 5.3 (mysqlnd)
            if (version_compare(PHP_VERSION, '5.3') > 0) {
                $this->Server = 'p:' . $this->Server;
            } else {
                eZDebug::writeWarning('ezredis only supports persistent connections when using php 5.3 and higher', __METHOD__);
            }
        }

        $oldHandling = eZDebug::setHandleType(eZDebug::HANDLE_EXCEPTION);
        eZDebug::accumulatorStart('redis_connection', 'redis_total', 'Database connection');
        $redis = new Redis();
        try {
            if ($this->UsePersistentConnection == true) {
                $connection = $redis->pconnect($server, $port, $this->timeout, null, $this->delayConnexion);
            } else {
                $connection = $redis->connect($server, $port, $this->timeout, null, $this->delayConnexion);
            }
        } catch (ErrorException $e) {
        }

        eZDebug::accumulatorStop('redis_connection');
        eZDebug::setHandleType($oldHandling);

        $maxAttempts = $this->connectRetryCount();
        $waitTime = $this->connectRetryWaitTime();
        $numAttempts = 1;
        while (!$connection && $numAttempts <= $maxAttempts) {
            sleep($waitTime);

            $oldHandling = eZDebug::setHandleType(eZDebug::HANDLE_EXCEPTION);
            eZDebug::accumulatorStart('redis_connection', 'redis_total', 'Database connection');
            try {
                $connection = $redis->connect($server, $port, $this->timeout, null, $this->delayConnexion);
            } catch (ErrorException $e) {
            }
            eZDebug::accumulatorStop('redis_connection');
            eZDebug::setHandleType($oldHandling);

            $numAttempts++;
        }
        $this->setError();

        $this->IsConnected = true;

        if (!$connection) {
            eZDebug::writeError("Connection error: Couldn't connect to database server. Please try again later or inform the system administrator.\n{$this->ErrorMessage}", __CLASS__);
            $this->IsConnected = false;
            throw new eZDBNoConnectionException($server, $this->ErrorMessage, $this->ErrorNumber);
        }

        return $redis;
    }

    public function databaseName()
    {
        return 'redis';
    }

    public function query($sql, $server = false)
    {
        if (!$sql) {
            eZDebug::writeWarning('No Redis query', 'eZRedisDB');
            return false;
        }
        if (!$this->IsConnected) {
            eZDebug::writeError("Trying to do a query without being connected to a redis database!", __CLASS__);
            return false;
        }
        eZDebug::accumulatorStart('redis_query', 'redis_total', 'Redis_queries');
        if ($this->OutputSQL) {
            $this->startTimer();
        }
        $sql = explode(' ', $sql);
        $phpMethod = array_shift($sql);
        if (!$phpMethod) {
            eZDebug::writeWarning('No Redis command', 'eZRedisDB');
            return false;
        }
        $query = implode(' ', $sql);

        // Check if we need to use the master or slave server by default
        if ($server === false) {
            // $server = strncasecmp($sql, 'set', 6) === 0 && $this->TransactionCounter == 0 ? eZDBInterface::SERVER_SLAVE : eZDBInterface::SERVER_MASTER;
        }
        $connection = ($server == eZDBInterface::SERVER_SLAVE) ? $this->DBConnection : $this->DBWriteConnection;
        $result = static::buildRedisCommand($connection, $phpMethod, $query);
        if ($this->OutputSQL) {
            $this->endTimer();
        }
        eZDebug::accumulatorStop('redis_query');
        return $result;
    }

    /**
     * Build the Redis unified protocol command
     * @param  Redis                    &$connection [description]
     * @param  string                   $phpMethod   [description]
     * @param  string                   $query       [description]
     * @date   2015-01-01T20:41:51+0100
     */
    public static function buildRedisCommand(Redis &$connection, $phpMethod, $query = false)
    {
        $redisIni = eZINI::instance('redis.ini');
        if ($redisIni->hasVariable('PHPRedisSettings', 'PhpRedisMethod')) {
            $phpMethods = $redisIni->variable('PHPRedisSettings', 'PhpRedisMethod');
            if (isset($phpMethods[$phpMethod])) {
                $phpMethod = $phpMethods[$phpMethod];
            }
        }

        $handlerClass = "";
        if ($redisIni->hasVariable('DatabaseSettings', 'RedisQueryHandler')) {
            $handlerClass = $redisIni->variable('DatabaseSettings', 'RedisQueryHandler');
        }
        if (method_exists($handlerClass."QueryHandler", $phpMethod)) {
            return call_user_func_array(array($handlerClass."QueryHandler", $phpMethod), array($connection, $query));
        } else {
            if (!$query) {
                return $connection->{$phpMethod}();
            } else {
                return call_user_func_array(array($connection, $phpMethod), explode(' ', $query));
            }
        }
    }

    public function arrayQuery($sql, $params = array(), $server = false)
    {
        $retArray = array();
        if ($this->IsConnected) {
            // check for array parameters
            $limit = (isset($params["limit"]) and is_numeric($params["limit"])) ? $params["limit"] : false;
            $offset = (isset($params["offset"]) and is_numeric($params["offset"])) ? $params["offset"] : false;
            if ($limit !== false and is_numeric($limit)) {
                $sql .= " LIMIT $offset, $limit ";
            } elseif ($offset !== false and is_numeric($offset) and $offset > 0) {
                $sql .= " LIMIT $offset, 18446744073709551615"; // 2^64-1
            }
            $result = $this->query($sql, $server);
        }
        return $result;
    }

   /**
    * The query to start the transaction.
    *
    * @return bool
    * usage :
    * > MULTI
    * OK
    * > INCR foo
    * QUEUED
    * > INCR bar
    * QUEUED
    * > EXEC
    * 1) (integer) 1
    * 2) (integer) 1
    */
    public function beginQuery()
    {
        return $this->query('multi');
    }

    /**
     * The query to commit the transaction.
     */
    public function commitQuery()
    {
        return $this->query('exec');
    }

    public function close()
    {
        if ($this->IsConnected) {
            return $this->query("quit");
        }
    }

    public function selectDatabase($dataBaseSelected)
    {
        $connection = $this->DBWriteConnection;
        if ($this->IsConnected) {
            $ret = $connection->select($dataBaseSelected);
        }
    }

    public function availableDatabases()
    {
        $databaseArray = $this->query('info keyspace');
        $databases = array();

        if (count($databaseArray) == 0) {
            return false;
        }
        foreach ($databaseArray as $key => $database) {
            $infoBase = array();
            foreach (explode(',', $database) as $value) {
                $infoTemp = explode('=', $value);
                $infoBase[$infoTemp[0]] = $infoTemp[1];
            }
            $databases[$key] = $infoBase;
        }
        return $databases;
    }

    public function commit()
    {
        $ini = eZINI::instance();
        if ($ini->variable("DatabaseSettings", "Transactions") == "enabled") {
            if ($this->TransactionCounter <= 0) {
                eZDebug::writeError('No transaction in progress, cannot commit', __METHOD__);
                return false;
            }

            --$this->TransactionCounter;
            if ($this->TransactionCounter == 0) {
                if (is_array($this->TransactionStackTree)) {
                    // Reset the stack debug tree since the top commit was done
                    $this->TransactionStackTree = array();
                }
                if ($this->isConnected()) {
                    // Check if we have encountered any problems, if so we have to rollback
                    if (!$this->TransactionIsValid) {
                        $oldRecordError = $this->RecordError;
                        // Turn off error handling while we rollback
                        $this->RecordError = false;
                        $this->rollbackQuery();
                        $this->RecordError = $oldRecordError;

                        return false;
                    } else {
                        return $this->commitQuery();
                    }
                }
            } else {
                if (is_array($this->TransactionStackTree)) {
                    // Close the last open nested transaction
                    $bt = debug_backtrace();
                    // Store commit trace
                    $subLevels =& $this->TransactionStackTree['sub_levels'];
                    for ($i = 1; $i < $this->TransactionCounter; ++$i) {
                        $subLevels =& $subLevels[count($subLevels) - 1]['sub_levels'];
                    }
                    // Find last entry and add the commit trace
                    $subLevels[count($subLevels) - 1]['commit_trace'] = $bt;
                }
            }
        }
        return true;
    }

    /**
     * Allow to use redis socket directly
     * @return Redis
     * @date   2015-01-05T14:49:54+0100
     * @example:
     *    $db = eZNoSqlDB::instance();
     *    $redis = $db->useRedis();
     *    $redis->set("key", "value", array("xx", "ex" => 100));
     */
    public function useRedis()
    {
        return $this->DBWriteConnection;
    }
}
