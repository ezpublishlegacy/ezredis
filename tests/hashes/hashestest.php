<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 * example :
 * phpunit --colors --debug extension/ezredis/tests/strings/hashestest.php
 */
class HashesTest extends ezpTestCase
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

    public function testHSet()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $db->query("select 6");
        // case 1 : add a new variable with a hash key
        $db->query('hset variable field1 "Hello world"');
        $this->assertEquals("Hello world", $db->query("hget variable field1"));
        // case 2 : add a new hash key
        $db->query('hset variable field2 Lorem');
        $this->assertEquals("Lorem", $db->query("hget variable field2"));
        // case 3 : add a new variable with a hash key
        $db->query('hset variable2 field1 "Hello world"');
        $this->assertEquals("Hello world", $db->query("hget variable2 field1"));
        // case 4 : hget false variables
        $this->assertFalse($db->query("hget variable2 field2"));
        $this->assertFalse($db->query("hget variable field3"));

        // case 5 : hget complex variables with quotes, html tag and espaceString function
        $htmlTag = $db->escapeString("<div class=\"lorem_ipsum\">LOREM IPSUM</div>");
        $db->query("hset variable field4 $htmlTag");
        $this->assertEquals('<div class="lorem_ipsum">LOREM IPSUM</div>', $db->query("hget variable field4"));
        $db->close();
    }

    public function testHExist()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $db->query("select 6");
        $db->query("hset foo field1 bar1");
        $this->assertTrue($db->query("hexists foo field1"));
        $this->assertFalse($db->query("hexists foo field3"));
        $db->close();
    }
}
