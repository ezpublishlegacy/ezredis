<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 * example :
 * phpunit --colors --debug extension/ezredis/tests/strings/multiplehashestest.php
 */
require_once 'autoload.php';
class MultipleHashesTest extends ezpTestCase
{
    public function testFlushDB()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $db->query("select 6");
        $db->query("flushdb");
        $this->assertEmpty($db->query("keys *"));
        $db->close();
    }

    public function testHMSet()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $db->query("select 6");
        // case 1 : multiple case with simple string
        $db->query('hmset myhash field1 "hello" field2 "world"');
        $this->assertEquals('hello', $db->query('hget myhash field1'));
        $this->assertEquals('world', $db->query('hget myhash field2'));
        $this->assertFalse($db->query('hget myhash nofield'));
        $this->assertequals(
            json_encode(array(
                'field1' => 'hello',
                'field2' => 'world',
                'nofield' => false
            )),
            json_encode($db->query('hmget myhash field1 field2 nofield'))
        );
        $this->assertequals(
            json_encode(array(
                'field3' => false,
                'field4' => false,
                'field5' => false
            )),
            json_encode($db->query('hmget myhash field3 field4 field5'))
        );
        // case 1 : multiple case with complex string
        $db->query('hmset myhash field10 "hello work" field11 "lorem ipsum"');
        $this->assertequals(
            json_encode(array(
                'field10' => 'hello work',
                'field11' => 'lorem ipsum'
            )),
            json_encode($db->query('hmget myhash field10 field11'))
        );
    }

    public function testHDel()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $db->query("select 6");
        $db->query('hmset myhash field21 "hello" field22 "world"');
        $this->assertEquals('hello', $db->query('hget myhash field21'));
        $db->query('hdel myhash field21 field22');
        $this->assertequals(
            json_encode(array(
                'field21' => false,
                'field22' => false
            )),
            json_encode($db->query('hmget myhash field21 field22'))
        );
    }
}
