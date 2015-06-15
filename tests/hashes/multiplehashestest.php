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
        // case 2 : multiple case with complex string with simple quote
        $db->query('hmset myhash field10 "hello work" field11 "lorem ipsum"');
        $this->assertequals(
            json_encode(array(
                'field10' => 'hello work',
                'field11' => 'lorem ipsum'
            )),
            json_encode($db->query('hmget myhash field10 field11'))
        );
        // case 3 : multiple case with complex string with double quote
        $db->query('hmset myhash field12 "hello work" field13 "lorem ipsum"');
        $this->assertequals(
            json_encode(array(
                'field12' => "hello work",
                'field13' => 'lorem ipsum'
            )),
            json_encode($db->query('hmget myhash field12 field13'))
        );

        // case 4 : multiple case with complex string and escapeString function
        $fieldValue15 = $db->escapeString('hello work guys. What\'s up?');
        $db->query("hmset myhash field15 $fieldValue15 field14 \"lorem ipsum\"");
        $this->assertequals(
            json_encode(array(
                'field15' => 'hello work guys. What\'s up?',
                'field14' => 'lorem ipsum'
            )),
            json_encode($db->query('hmget myhash field15 field14'))
        );


        // case 5 : multiple case with simple string with no quotes
        $db->query('hmset myhash field16 hello field17 world field18 Lorem');
        $this->assertEquals('hello', $db->query('hget myhash field16'));
        $this->assertEquals('world', $db->query('hget myhash field17'));
        $this->assertFalse($db->query('hget myhash nofield'));
        $this->assertequals(
            json_encode(array(
                'field16' => 'hello',
                'field17' => 'world',
                'field18' => 'Lorem',
                'nofield' => false
            )),
            json_encode($db->query('hmget myhash field16 field17 field18 nofield'))
        );
        $db->close();
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
        $db->close();
    }
}
