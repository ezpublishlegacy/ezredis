<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */

class phpRedisQueryHandler
{
    private function __construct()
    {
        eZDebug::writeError('This class should not be instantiated', __METHOD__);
    }

    /**
     * Set the string value in argument as value of the key.
     * If you're using Redis >= 2.6.12, you can pass extended options as explained below
     * @param Redis                    &$connection [description]
     * @param string                   $query       [description]
     * @date  2015-01-05T13:31:05+0100
     * @additionnal parameter :
     * EX seconds -- Set the specified expire time, in seconds.
     * PX milliseconds -- Set the specified expire time, in milliseconds.
     * NX -- Only set the key if it does not already exist.
     * XX -- Only set the key if it already exist.
     */
    public static function set(Redis &$connection, $query)
    {
        $start_time = microtime(true);
        $matches = array();
        if (preg_match("/\"(.+)\"/", $query, $matches)) {
            $subQuery = explode($matches[0], $query);
            $args = array();
            $args[0] = trim($subQuery[0]);
            $args[1] = $matches[1];
            if (!empty($subQuery[1])) {
                $resultInt = explode(" ", $subQuery[1]);
                $args = array_merge($args, $resultInt);
            }
        } else {
            $args = ArrayTools::explodeStringInArray($query);
        }

        $additionnalParams = array();
        if (isset($args[2]) && is_numeric($args[2])) {
            static::setex($connection, $args[0].' '.$args[2].' '.$args[1]);
        }
        foreach ($args as $ind => $parameter) {
            if (in_array(strtolower($parameter), array("nx", "xx"))) {
                array_unshift($additionnalParams, $args[$ind]);
            } elseif (in_array(strtolower($parameter), array("px", "ex"))) {
                if (
                    isset($args[$ind])
                    && isset($args[$ind+1])
                    && is_numeric($args[$ind+1])
                ) {
                    $additionnalParams[$args[$ind]] = (int)$args[$ind+1];
                }
            }
        }
        $end_time = microtime(true);
        if (empty($additionnalParams)) {
            return $connection->set($args[0], $args[1]);
        } else {
            return $connection->set($args[0], $args[1], $additionnalParams);
        }
    }

    /**
     * Set the string value in argument as value of the key, with a time to live
     * @param  Redis                    &$connection [description]
     * @param  string                   $query       [description]
     * @date   2015-01-05T13:38:39+0100
     * @example
     * $redis->setex('key', 3600, 'value'); // sets key → value, with 1h TTL.
     */
    public static function setex(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        return $connection->setex($args[0], $args[1], $args[2]);
    }

    /**
     * Set the string value in argument as value of the key, with a time to live
     * PSETEX uses a TTL in milliseconds.
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return [type]                                [description]
     * @date   2015-01-07T23:00:36+0100
     * example
     * $redis->psetex('key', 100, 'value'); // sets key → value, with 0.1 sec TTL.
     */
    public static function psetex(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        return $connection->psetex($args[0], $args[1], $args[2]);
    }

    /**
     * Get the values of all the specified keys.
     * If one or more keys dont exist,
     * the array will contain `FALSE` at the position of the key.
     * @param  Redis                    &$connection [description]
     * @param  string                   $query       [description]
     * @date   2015-01-07T23:36:42+0100
     */
    public static function mget(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        return $connection->mget($args);
    }

    /**
     * Scan the keyspace for keys
     * @param  Redis                    &$connection [description]
     * @param  string                   $query       [description]
     * @return array                                 This function will return an array of keys or FALSE if there are no more keys
     * @date   2015-01-08T01:31:06+0100
     * @example :
     * // Initialize our iterator to NULL
     * $it = NULL;
     * // retry when we get no keys back
     * $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
     * while($arr_keys = $redis->scan($it)) {
     *     foreach($arr_keys as $str_key) {
     *         echo "Here is a key: $str_key\n";
     *     }
     *     echo "No more keys to scan!\n";
     * }
     *
     * scan() function
     * @param LONG (reference)*:  Iterator, initialized to NULL
     * @param STRING, Optional*:  Pattern to match
     * @param LONG, Optional*: Count of keys per iteration (only a suggestion to Redis)
     */
    public static function scan(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $parameters = array(
            "query" => null,
            "match" => false,
            "count" => false
        );
        foreach ($args as $ind => $parameter) {
            if (strtolower($parameter) == "match") {
                $parameters["match"] = $args[$ind+1];
            } elseif (strtolower($parameter) == "count") {
                $parameters["count"] = $args[$ind+1];
            }
        }
        return $connection->scan($parameters['query'], $parameters['match'], $parameters['count']);
    }

    /**
     * Sort the elements in a list, set or sorted set.
     * @param  Redis                    &$connection [description]
     * @param  string                   $query       [description]
     * @return array                    An array of values, or a number corresponding
     *                                  to the number of elements stored if that was used.
     * @date   2015-01-08T23:46:48+0100
     */
    public static function sort(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $options = array();
        foreach ($args as $ind => $parameter) {
            switch (strtolower($parameter)) {
                case "limit":
                    if (isset($args[$ind+1]) && is_numeric($args[$ind+1]) && isset($args[$ind+2]) && is_numeric($args[$ind+2])) {
                        $options["limit"] = array($args[$ind+1], $args[$ind+2]);
                    } else {
                        throw new Exception("It's not a limit, you must have 2 parameters");
                    }
                    break;
                case "desc":
                case "asc":
                    $options['sort'] = strtolower($parameter);
                    break;
                /// When mylist contains string values and you want to sort them lexicographically
                case "alpha":
                    $options['alpha'] = true;
                    break;
                /// Sorting by external keys
                /// Skip sorting the elements with nosort
                case "by":
                    if (isset($args[$ind+1])) {
                        $options['by'] = $args[$ind+1];
                    } else {
                        throw new Exception("By parameter, you must have 1 parameter");
                    }
                    break;
                /// Retrieving external keys
                case "get":
                    if (isset($args[$ind+1])) {
                        $options['get'] = $args[$ind+1];
                    } else {
                        throw new Exception("Get parameter, you must have 1 parameter");
                    }
                    break;
                /// Storing the result of a SORT operation
                case "store":
                    if (isset($args[$ind+1])) {
                        $options['store'] = $args[$ind+1];
                    } else {
                        throw new Exception("Store parameter, you must have 1 parameter");
                    }
                    break;

                default:
                    # code...
                    break;
            }
        }
        return $connection->sort($args[0], $options);
    }

    /**
     * Sets multiple key-value pairs in one atomic command.
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return boolean                                *Bool* `TRUE` in case of success, `FALSE` in case of failure.
     * @date   2015-01-24T00:43:33+0100
     */
    public static function mset(Redis &$connection, $query)
    {
        $args     = array();
        if (preg_match('/"([^"]+)"/', $query)) {
            $args = ArrayTools::explodeComplexStringInArray($query);
        } else {
            $args = ArrayTools::explodeStringInArray($query);
        }
        if ((count($args) % 2) !== 0) {
            throw new Exception("you must have a key for a value");
        }
        $parameter = array();
        for ($i=0; $i < count($args); $i+=2) {
            $parameter[$args[$i]] = $args[$i+1];
        }
        return $connection->mset($parameter);
    }

    /**
     * Sets multiple key-value pairs in one atomic command.
     * MSETNX only returns TRUE if all the keys were set (see SETNX).
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return boolean                                *Bool* `TRUE` in case of success, `FALSE` in case of failure.
     * @date   2015-01-24T00:43:33+0100
     */
    public static function msetnx(Redis &$connection, $query)
    {
        $args     = array();
        if (preg_match('/"([^"]+)"/', $query)) {
            $args = ArrayTools::explodeComplexStringInArray($query);
        } else {
            $args = ArrayTools::explodeStringInArray($query);
        }
        if ((count($args) % 2) !== 0) {
            throw new Exception("you must have a key for a value");
        }
        $parameter = array();
        for ($i=0; $i < count($args); $i+=2) {
            $parameter[$args[$i]] = $args[$i+1];
        }
        return $connection->msetnx($parameter);
    }

    /**
     * Migrates a key to a different Redis instance.
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @date   2015-01-09T22:35:15+0100
     */
    public static function migrate(Redis &$connection, $query)
    {
        $args    = explode(" ", $query);
        $copy    = false;
        $replace = false;
        foreach ($args as $ind => $parameter) {
            if (strtolower($parameter) == "copy") {
                $copy = true;
            } elseif (strtolower($parameter) == "replace") {
                $replace = true;
            }
        }
        return $connection->migrate($args[0], $args[1], $args[2], $args[3], $args[4], $copy, $replace);
    }

    /**
     * Fills in a whole hash.
     * Non-string values are converted to string,
     * using the standard `(string)` cast. NULL values are stored
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return Bool
     * @date   2015-01-09T22:56:50+0100
     */
    public static function hmset(Redis &$connection, $query)
    {
        $args    = explode(" ", $query);
        $key = array_shift($args);
        if ((count($args) % 2) !== 0) {
            throw new Exception("you must have a key for a value multiple");
        }
        $parameter = array();
        for ($i=0; $i < count($args); $i+=2) {
            $parameter[$args[$i]] = $args[$i+1];
        }
        return $connection->hmset($key, $parameter);
    }

    /**
     * Retrieve the values associated to the specified fields in the hash.
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return Array                    An array of elements,
     * the values of the specified fields in the hash, with the hash keys as array keys.
     * @date   2015-01-09T23:08:54+0100
     */
    public static function hmget(Redis &$connection, $query)
    {
        $args    = explode(" ", $query);
        $key = array_shift($args);
        return $connection->hmget($key, $args);
    }

    /**
     * Scan a HASH value for members, with an optional pattern and count
     * @param  Redis                    &$connection [description]
     * @param  string                   $query       [description]
     * @return array                    An array of members that match our pattern
     * @date   2015-01-09T23:16:43+0100
     * @example :
     * // Initialize our iterator to NULL
     * $it = NULL;
     * // Don't ever return an empty array until we're done iterating
     * $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
     * while($arr_keys = $redis->hscan('hash', $it)) {
     *     foreach($arr_keys as $str_field => $str_value) {
     *         echo "$str_field => $str_value\n";
     *     }
     * }
     *
     * scan() function
     * @param LONG (reference)*:  Iterator, initialized to NULL
     * @param STRING, Optional*:  Pattern to match
     * @param LONG, Optional*: Count of keys per iteration (only a suggestion to Redis)
     */
    public static function hscan(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $parameters = array(
            "query" => null,
            "match" => false,
            "count" => false
        );
        $key = array_shift($args);
        foreach ($args as $ind => $parameter) {
            if (strtolower($parameter) == "match") {
                $parameters["match"] = $args[$ind+1];
            } elseif (strtolower($parameter) == "count") {
                $parameters["count"] = $args[$ind+1];
            }
        }
        return $connection->hscan($key, $parameters['query'], $parameters['match'], $parameters['count']);
    }

    /**
     * Insert value in the list before or after the pivot value.
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return The number of the elements in the list, -1 if the pivot didn't exists.
     * @date   2015-01-10T00:09:02+0100
     */
    public static function linsert(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        if (count($args) != 4) {
            throw new Exception("Need 4 parameters");
        }
        switch (strtolower($args[1])) {
            case 'before':
                $position = Redis::BEFORE;
                break;
            case 'after':
                $position = Redis::AFTER;
                break;
            default:
                throw new Exception("Need 4 parameters");
                break;
        }
        return $connection->linsert($args[0], $position, $args[2], $args[3]);
    }

    /**
     * Scan a set for members
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return array PHPRedis will return an array of keys or FALSE when we're done iterating
     * @date   2015-01-11T00:21:55+0100
     * @example :
     * $it = NULL;
     * $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
     * // don't return empty results until we're done
     * while($arr_mems = $redis->sscan('set', $it, "*pattern*")) {
     *     foreach($arr_mems as $str_mem) {
     *         echo "Member: $str_mem\n";
     *     }
     * }
     * $it = NULL;
     * while(($arr_mems = $redis->sscan('set', $it, "*pattern*"))!==FALSE) {
     *     if(count($arr_mems) > 0) {
     *         foreach($arr_mems as $str_mem) {
     *             echo "Member found: $str_mem\n";
     *         }
     *     } else {
     *         echo "No members in this iteration, iterator value: $it\n";
     *     }
     * }
     */
    public static function sscan(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $parameters = array(
            "query" => null,
            "match" => false,
            "count" => false
        );
        $key = array_shift($args);
        foreach ($args as $ind => $parameter) {
            if (strtolower($parameter) == "match") {
                $parameters["match"] = $args[$ind+1];
            } elseif (strtolower($parameter) == "count") {
                $parameters["count"] = $args[$ind+1];
            }
        }
        return $connection->sscan($key, $parameters['query'], $parameters['match'], $parameters['count']);
    }

    /**
     * Creates an intersection of sorted sets given in second argument.
     * The result of the union will be stored in the sorted set
     * defined by the first argument.
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return long The number of values in the new sorted set.
     * @date   2015-01-11T00:26:45+0100
     */
    public static function zinter(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $destination = array_shift($args);
        $parameters = array(
            "keys" => array(),
            "weight" => null,
            "aggregate" => null
        );
        $weight = false;
        $aggregate = false;
        foreach ($args as $ind => $parameter) {
            if (
                strtolower($parameter) == "weight" ||
                (
                    (strtolower($parameter) != "aggregate") &&
                    $weight &&
                    !$aggregate
                )
            ) {
                if ($weight) {
                    $parameters["weight"][] = $args[$ind];
                }
                $weight = true;
                $aggregate = false;
            } elseif (
                strtolower($parameter) == "aggregate" ||
                (
                    (strtolower($parameter) != "weight") &&
                    $aggregate &&
                    !$weight
                )
            ) {
                if ($aggregate) {
                    if (in_array(strtolower($args[$ind]), array("max", "min"))) {
                        $parameters["aggregate"] = $args[$ind];
                    } else {
                        throw new Exception("Aggregate, Only Max and Min parameter exist");
                    }
                }
                $aggregate = true;
            } else {
                $parameters["keys"][] = $args[$ind];
            }
        }
        return $connection->zinter(
            $destination,
            $parameters['keys'],
            $parameters['weight'],
            $parameters['aggregate']
        );
    }

    /**
     * Returns a range of elements from the ordered set stored
     * at the specified key, with values in the range [start, end].
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return array containing the values in specified range.
     * @date   2015-01-11T01:31:52+0100
     */
    public static function zrange(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $withscores = false;
        if (isset($args[3]) && ("withscores" == strtolower($args[3]))) {
            $withscores = true;
        }
        return $connection->zrange($args[0], $args[1], $args[2], $withscores);
    }

    /**
     * Returns the elements of the sorted set stored at the specified key
     * which have scores in the range [start,end]. Adding a parenthesis
     * before `start` or `end` excludes it from the range. +inf and -inf
     * are also valid limits.
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return array containing the values in specified range.
     * @date   2015-01-11T01:48:13+0100
     */
    public static function zrangebyscore(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $options = array();
        foreach ($args as $ind => $parameter) {
            if (strtolower($parameter) == "limit") {
                $options['limit'] = array($args[$ind+1], $args[$ind+2]);
            } elseif (strtolower($parameter) == "withscores") {
                $options['withscores'] = true;
            }
        }
        return $connection->zrangebyscore($args[0], $args[1], $args[2], $options);
    }

    /**
     * Returns the elements of the sorted set stored at the specified key
     * which have scores in the range [start,end]. Adding a parenthesis
     * before `start` or `end` excludes it from the range. +inf and -inf
     * are also valid limits. zRevRangeByScore returns the same items in
     * reverse order, when the `start` and `end` parameters are swapped.
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return array containing the values in specified range.
     * @date   2015-01-11T01:48:13+0100
     */
    public static function zrevrangebyscore(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $options = array();
        foreach ($args as $ind => $parameter) {
            if (strtolower($parameter) == "limit") {
                $options['limit'] = array($args[$ind+1], $args[$ind+2]);
            } elseif (strtolower($parameter) == "withscores") {
                $options['withscores'] = true;
            }
        }
        return $connection->zrangebyscore($args[0], $args[1], $args[2], $options);
    }

    /**
     * Returns the elements of the sorted set stored at the specified
     * key in the range [start, end] in reverse order. start and stop
     * are interpretated as zero-based indices:
     *     0 the first element, 1 the second ...
     *     -1 the last element, -2 the penultimate ...
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return [type] containing the values in specified range.
     * @date   2015-01-11T02:03:50+0100
     */
    public static function zrevrange(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $withscores = false;
        if (isset($args[3]) && ("withscores" == strtolower($args[3]))) {
            $withscores = true;
        }
        return $connection->zrevrange($args[0], $args[1], $args[2], $withscores);
    }

    /**
     * [zinter description]
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return long The number of values in the new sorted set.
     * @date   2015-01-11T02:05:15+0100
     */
    public static function zinterzunionstore(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $destination = array_shift($args);
        $parameters = array(
            "keys" => array(),
            "weight" => null,
            "aggregate" => null
        );
        $weight = false;
        $aggregate = false;
        foreach ($args as $ind => $parameter) {
            if (
                strtolower($parameter) == "weight" ||
                (
                    (strtolower($parameter) != "aggregate") &&
                    $weight &&
                    !$aggregate
                )
            ) {
                if ($weight) {
                    $parameters["weight"][] = $args[$ind];
                }
                $weight = true;
                $aggregate = false;
            } elseif (
                strtolower($parameter) == "aggregate" ||
                (
                    (strtolower($parameter) != "weight") &&
                    $aggregate &&
                    !$weight
                )
            ) {
                if ($aggregate) {
                    if (in_array(strtolower($args[$ind]), array("max", "min", "sum"))) {
                        $parameters["aggregate"] = $args[$ind];
                    } else {
                        throw new Exception("Aggregate, Only Max and Min parameter exist");
                    }
                }
                $aggregate = true;
            } else {
                $parameters["keys"][] = $args[$ind];
            }
        }
        return $connection->zunionstore(
            $destination,
            $parameters['keys'],
            $parameters['weight'],
            $parameters['aggregate']
        );
    }

    /**
     * Scan a sorted set for members, with optional pattern and count
     * @param  Redis                    &$connection [description]
     * @param  [type]                   $query       [description]
     * @return array PHPRedis will return an array of keys or FALSE when we're done iterating
     * @date   2015-01-11T02:09:31+0100
     * @example :
     * $it = NULL;
     * $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
     * while($arr_matches = $redis->zscan('zset', $it, '*pattern*')) {
     *     foreach($arr_matches as $str_mem => $f_score) {
     *         echo "Key: $str_mem, Score: $f_score\n";
     *     }
     * }
     */
    public static function zscan(Redis &$connection, $query)
    {
        $args = ArrayTools::explodeStringInArray($query);
        $parameters = array(
            "query" => null,
            "match" => false,
            "count" => false
        );
        $key = array_shift($args);
        foreach ($args as $ind => $parameter) {
            if (strtolower($parameter) == "match") {
                $parameters["match"] = $args[$ind+1];
            } elseif (strtolower($parameter) == "count") {
                $parameters["count"] = $args[$ind+1];
            }
        }
        return $connection->zscan($key, $parameters['query'], $parameters['match'], $parameters['count']);
    }
}
