<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */

class eZNoSqlDB extends eZDB
{
    private function __construct()
    {
        eZDebug::writeError('This class should not be instantiated', __METHOD__);
    }

    public static function hasInstance()
    {
        return isset($GLOBALS['eZNoSQLDBGlobalInstance']) && $GLOBALS['eZNoSQLDBGlobalInstance'] instanceof eZDBInterface;
    }
    public static function setInstance($instance)
    {
        $GLOBALS['eZNoSQLDBGlobalInstance'] = $instance;
    }
    public static function instance($databaseImplementation = false, $databaseParameters = false, $forceNewInstance = false)
    {
        $ini = eZINI::instance('redis.ini');
        $databaseImplementation = "ezredis";
        list($server, $port, $user, $pwd, $usePersistentConnection) =
            $ini->variableMulti('DatabaseSettings', array( 'Server', 'Port', 'User', 'Password', 'UsePersistentConnection', ));
        $socketPath = false;
        $socket = $ini->variable('DatabaseSettings', 'Socket');
        if (trim($socket != "") and $socket != "disabled") {
            $socketPath = $socket;
        }
        list($charset, $retries) =
            $ini->variableMulti('DatabaseSettings', array( 'Charset', 'ConnectRetries' ));
        $builtinEncoding = ($ini->variable('DatabaseSettings', 'UseBuiltinEncoding') == 'true');
        $databaseParameters = array(
            'server' => $server,
            'port' => $port,
            'user' => $user,
            'password' => $pwd,
            'database' => '',
            'use_slave_server' => '',
            'slave_server' => '',
            'slave_port' => '',
            'slave_user' => '',
            'slave_password' => '',
            'slave_database' => '',
            'charset' => '',
            'is_internal_charset' => '',
            'socket' => $socketPath,
            'builtin_encoding' => $builtinEncoding,
            'connect_retries' => $retries,
            'use_persistent_connection' => $usePersistentConnection,
            'show_errors' => true
        );

        $optionArray = array( 'iniFile'       => 'redis.ini',
                              'iniSection'    => 'DatabaseSettings',
                              'iniVariable'   => 'ImplementationAlias',
                              'handlerIndex'  => $databaseImplementation,
                              'handlerParams' => array( $databaseParameters ) );

        $options = new ezpExtensionOptions($optionArray);

        $impl = eZExtension::getHandlerClass($options);

        if (!$impl) {
            $impl = new eZNullDB($databaseParameters);
            $impl->ErrorMessage = "No database handler was found for '$databaseImplementation'";
            $impl->ErrorNumber = -1;
            if ($databaseParameters['show_errors']) {
                eZDebug::writeError('Database implementation not supported: ' . $databaseImplementation, __METHOD__);
            }
        }

        $impl->setErrorHandling(self::$errorHandling);
        return $impl;
    }
}
