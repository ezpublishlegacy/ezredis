<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 * example :
 * phpunit --colors --debug extension/ezredis/tests/strings/stringtest.php
 */
class SetTest extends ezpTestCase
{
    public function testFlushDB()
    {
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        $db->query("flushdb");
        $this->assertEmpty($db->query("keys *"));
        $db->close();
    }

    public function testSet()
    {
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        $db->query("set foo bar");
        $this->assertEquals("bar", $db->query("get foo"));
        $db->query("set mykey \"hello world\"");
        $this->assertEquals("hello world", $db->query("get mykey"));
        $db->query("set mykey2 \"hello world 2\"");
        $this->assertEquals("hello world 2", $db->query("get mykey2"));
        $db->close();
    }

    public function testSetArray()
    {
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        $valueArray = array("hello", "world");
        $db->query("set mykey3 " . $db->escapeString($valueArray));
        $this->assertEquals($valueArray, $db->query("get mykey3"));
        $db->close();
    }

    public function testDidntSet()
    {
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        $this->assertFalse($db->query("get foodidntset"));
        $db->close();
    }

    public function testExist()
    {
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        $db->query("set foo2 bar2");
        $this->assertTrue($db->query("exists foo2"));
        $db->close();
    }

    public function testDidntExist()
    {
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        $db->query("set foo3 bar3");
        $this->assertFalse($db->query("exists foo4"));
        $db->close();
    }

    public function testDel()
    {
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        //case 1 : one insert with one delete
        $db->query("set mykey301 bar3");
        $this->assertEquals('bar3', $db->query("get mykey301"));
        $db->query("del mykey301");
        $this->assertFalse($db->query("get mykey301"));
        $db->close();
    }
}
