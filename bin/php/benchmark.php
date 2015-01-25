<?php

/**
 * This benchmark is based on redisent library benchmark
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */

require 'autoload.php';

$bench = isset($argv[1])? $argv[1]: null;
$fast_iterations = isset($argv[2])? $argv[2]: 5000;
$slow_iterations = isset($argv[3])? $argv[3]: 2500;
$repeat = isset($argv[4])? $argv[4]: 3;
$verbose = isset($argv[5])? true: false;

$script = eZScript::instance(
    array(
        'description' => "Remove archived content object versions according to "
            . "[VersionManagement/DefaultVersionHistoryLimit and "
            . "[VersionManagement]/VersionHistoryClass settings",
        'use-session' => false,
        'use-modules' => true,
        'use-extensions' => true
    )
);
$script->startup();
$sys = eZSys::instance();
$script->initialize();

$totalexecutiontime=0;

$keyscommand="keys";
$deletecommand="del";
$mgetkeys =  array('1', 'foo');

switch ($bench) {
    case "redisent":
        $redis = new \redisent\Redis("redis://localhost/");
        $mgetkeys="1 foo";
        $redis->select(2);
        $resultRedis = $redis->get('voiture');
        break;
    // case "redisentwrap":
    //     require '../redisent.php';
    //     $redis = new RedisentWrap('localhost', 6379, true);
    //     $keyscommand="getkeys";
    //     $deletecommand="delete";
    //     break;
    case "phpredis":
        $redis = new Redis();
        $redis->connect('localhost', 6379);

        $keyscommand="getkeys";
        $deletecommand="delete";
        break;
    case "predis":
        Predis\Autoloader::register();
        $single_server = array(
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'database' => 2
        );
        $redis = new Predis\Client($single_server);
        $mgetkeys =  array('1', 'foo');

        break;
    case "ezredis":
        $db = eZNoSqlDB::instance();
        
        $mgetkeys =  array('1', 'foo');

        break;
    case "rediska":
        echo "Not yet supported.. the interface is really different...\n";
    /*
    include '../library/Rediska.php';
    $redis = new Rediska(array(
        'servers' => array(
            array('host' => '127.0.0.1', 'port' => 6379),
        )
    ));
    */
    default:
        echo "\nusage:\nphp benchmark.php redisent|redisentwrap|ezredis|phpredis|predis numberoffastiterations numberofslowiterations repeatbench verbose\n";
        echo "example:\nphp benchmark.php redisent 5000 500 3 verbose\n";
        exit;
        break;
}
for ($j = 1; $j <= $repeat; $j++) {
    if ($verbose) {
        echo "\n--- Benchmark for $bench ---\n";
    }
    $start_time = microtime(true);
    if ($bench == "ezredis") {
        $db->selectDatabase(2);
        $db->query('flushdb');
    } else {
        $redis->select(2);
        $redis->flushdb();
    }
    
    if ($verbose) {
        echo "Fast stuff\n";
    }
    if ($bench == "ezredis") {
        $db->query('set foo bar');
    } else {
        $redis->set("foo", 'bar');
    }
    for ($i = 1; $i <= $fast_iterations; $i++) {
        if ($bench == "ezredis") {
            $db->query('set '.$i.' '.'bar' .$i);
            $result = $db->query('get bar');
            $result = $db->query('get foo');
            $db->query('del foo');
            $result = $db->query('mget 1 foo');
        } else {
            $redis->set($i, 'bar' .$i);
            $res = $redis->get('bar') ;
            $res = $redis->get('foo') ;
            $redis->$deletecommand('foo');
            $res = $redis->mget($mgetkeys);
        }
        
        if ($verbose && !($i % 100)) {
            echo ".";
        }
    }
    $end_time_fast = microtime(true);

    if ($verbose) {
        echo sprintf("\nFast stuff completed in %f seconds\n", $end_time_fast-$start_time);
    }
    if ($verbose) {
        echo "Slow stuff:\n";
    }
    if ($bench == "ezredis") {
        $db->query('flushdb');
    } else {
        $redis->flushdb();
    }
    
    for ($i = 1; $i <=  $slow_iterations; $i++) {
        if ($bench == "ezredis") {
            $db->query('set '.$i.' '.'bar' .$i);
            $result = $db->query('keys *');
            $result = $db->query('randomkey');
        } else {
            $redis->set($i, 'bar' .$i);
            $res = $redis->$keyscommand('*');
            $res = $redis->randomkey();
        }
        
        if ($verbose && !($i % 10)) {
            echo ".";
        }
    }
    $end_time = microtime(true);
    if ($verbose) {
        echo sprintf("\nSlow stuff completed in %f seconds\n", $end_time-$end_time_fast);
    }
    $totalexecutiontime+=$end_time-$start_time;
    if ($verbose) {
        echo sprintf("Tests a completed in %f seconds\n", $end_time-$start_time);
    }
    if ($verbose) {
        echo sprintf("Memory Usage %s bytes\n", memory_get_peak_usage(true));
    }
}
echo sprintf("-- Bottom Line for $bench: Tests completed a in %f seconds in average, with %.2f mb memory usage\n", $totalexecutiontime/$j, memory_get_peak_usage(true)/1000000);
$script->shutdown();
