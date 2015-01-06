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
        $args = explode(" ", $query);
        $additionnalParams = array();
        if (isset($args[2]) && is_numeric($args[2])) {
            static::setex($connection, $query);
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
     */
    public static function setex(Redis &$connection, $query)
    {
        $args = explode(" ", $query);
        return $connection->set($args[0], $args[1], $args[2]);
    }

    /**
     * Set the string value in argument as value of the key, with a time to live
     * @param  Redis                    &$connection [description]
     * @param  string                   $query       [description]
     * @date   2015-01-05T13:38:39+0100
     */
    public static function mget(Redis &$connection, $query)
    {
        $args = explode(" ", $query);
        return $connection->mget($args);
    }
}
