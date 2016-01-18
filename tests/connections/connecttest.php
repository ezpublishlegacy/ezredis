<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */
class ConnectTest extends ezpTestCase
{
    public function testConstructorOpenconnection()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $this->assertEquals('eZRedisDB', get_class($db));
        $db->close();
    }

    public function testCheckRedis()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $this->assertEquals('Redis', get_class($db->useRedis()));
        $db->close();
    }

    public function testConstructorDoestNotOpenconnection()
    {
        eZExtension::activateExtensions('default');
        $databaseImplementation = "noredis";
        $db = eZNoSqlDB::instance($databaseImplementation);
        $this->assertEquals('eZNullDB', get_class($db));
        $db->close();
    }

    public function testPingRedis()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $this->assertTrue($db->isConnected());
        $db->close();
    }

    public function testCloseConnection()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $db->close();
        $this->assertFalse($db->isConnected());
    }
}
