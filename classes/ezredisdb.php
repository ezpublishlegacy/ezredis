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
                eZDebug::writeWarning('mysqli only supports persistent connections when using php 5.3 and higher', __METHOD__);
            }
        }

        $oldHandling = eZDebug::setHandleType(eZDebug::HANDLE_EXCEPTION);
        eZDebug::accumulatorStart('mysqli_connection', 'mysqli_total', 'Database connection');
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

    public function query($noSql, $server = false)
    {
        if (!is_array($noSql) || !isset($noSql['command'])) {
            eZDebug::writeWarning('No Redis query', 'eZRedisDB');
            return false;
        }
        if ($this->IsConnected) {
            eZDebug::accumulatorStart('redis_query', 'redis_total', 'Redis_queries');
        }
        if ($this->OutputSQL) {
            $this->startTimer();
        }
        $selectDatabase = isset($noSql['database']) ? $noSql['database'] : 0;
        $phpMethod = isset($noSql['command']) ? $noSql['command'] : '';
        $query = isset($noSql['query']) ? $noSql['query'] : '';

        // Check if we need to use the master or slave server by default
        if ($server === false) {
            // $server = strncasecmp($noSql, 'set', 6) === 0 && $this->TransactionCounter == 0 ? eZDBInterface::SERVER_SLAVE : eZDBInterface::SERVER_MASTER;
        }

        $connection = ($server == eZDBInterface::SERVER_SLAVE) ? $this->DBConnection : $this->DBWriteConnection;

        $connection->select($selectDatabase);
        $result = call_user_func_array(array($connection, $phpMethod), array($query));
        return $result;
    }

    public function arrayQuery($sql, $params = array(), $server = false)
    {
        $retArray = array();
        if ($this->IsConnected) {
            $result = $this->query($sql, $server);
        }
        return $result;
    }

    public function eZTableList($server = eZDBInterface::SERVER_MASTER)
    {
    }
}
