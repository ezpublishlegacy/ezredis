<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 * example :
 * phpunit --colors --debug extension/ezredis/tests/strings/multiplestringtest.php
 */
require_once 'autoload.php';
class MultipleStringsTest extends ezpTestCase
{
    public function testFlushDB()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        $db->query("flushdb");
        $this->assertEmpty($db->query("keys *"));
        $db->close();
    }

    public function testMSet()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        //case 1
        $db->query("mset mykey4 \"bonjour doudou denise\"  mykey5 coucou mykey3  \"bonjour andy\" ");
        $result = $db->query("mget mykey4 mykey3 mykey5");
        $this->assertEquals(json_encode(array("bonjour doudou denise", "bonjour andy", "coucou")), json_encode($result));
        //case 2
        $db->query("mset mykey10 \"foo bar\"  mykey13 foobar mykey18  \"lorem ipsum\" mykey19 \"Lorem ipsum dolor sit amet\" mykey20 Lorem");
        $result = $db->query("mget mykey10 mykey13 mykey18 mykey19 mykey20");
        $this->assertEquals(json_encode(array("foo bar", "foobar", "lorem ipsum", "Lorem ipsum dolor sit amet", "Lorem")), json_encode($result));
        //case 3
        $db->query("mset mykey6 vendredi  mykey7 jeudi");
        $result = $db->query("mget mykey6 mykey7");
        $this->assertEquals(json_encode(array("vendredi", "jeudi")), json_encode($result));
        //case 4
        $db->query("mset mykey8 ".$db->escapeString('Lorem ipsum dolor sit amet')."  mykey9 ".$db->escapeString('Lorem ipsum dolor sit amet'));
        $result = $db->query("mget mykey8 mykey9");
        $this->assertEquals(json_encode(array("Lorem ipsum dolor sit amet", "Lorem ipsum dolor sit amet")), json_encode($result));
        $db->close();
    }

    public function testMSetNX()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        //case 1 : mutilpe insert
        ;
        $this->assertTrue($db->query("msetnx mykey101 \"foo bar\"  mykey131 foobar mykey181  \"lorem ipsum\" mykey191 \"Lorem ipsum dolor sit amet\" mykey201 Lorem"));
        $result = $db->query("mget mykey101 mykey131");
        $this->assertEquals(json_encode(array("foo bar", "foobar")), json_encode($result));
        //case 2 : one insert
        $this->assertTrue($db->query("msetnx mykey102 \"foo bar\""));
        $result = $db->query("mget mykey102");
        $this->assertEquals(json_encode(array("foo bar")), json_encode($result));
        //case 3 : multplie insert wih one false
        $this->assertFalse($db->query("msetnx mykey101 \"foo bar\"  mykey231 foobar mykey281  \"lorem ipsum\" mykey291 \"Lorem ipsum dolor sit amet\" mykey201 Lorem"));
        //case 4 : multplie insert false and the same of the case 1
        $this->assertFalse($db->query("msetnx mykey101 \"foo bar\"  mykey131 foobar mykey181  \"lorem ipsum\" mykey191 \"Lorem ipsum dolor sit amet\" mykey201 Lorem"));
        $db->close();
    }


    public function testDel()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $db->query("select 5");
        //case 1 : multiple insert with mutiple delete
        $db->query("mset mykey54 \"bonjour doudou denise\"  mykey55 coucou mykey53  \"bonjour andy\" ");
        $result = $db->query("mget mykey54 mykey53 mykey55");
        $this->assertEquals(json_encode(array("bonjour doudou denise", "bonjour andy", "coucou")), json_encode($result));
        $db->query("del mykey54 mykey55 mykey53");
        $result = $db->query("mget mykey54 mykey53 mykey55");
        $this->assertEquals(json_encode(array(false, false, false)), json_encode($result));
        //case 2: multiple insert with one delete
        $db->query("mset mykey64 \"bonjour doudou denise\"  mykey65 coucou mykey63  \"bonjour andy\" ");
        $result = $db->query("mget mykey64 mykey63 mykey65");
        $this->assertEquals(json_encode(array("bonjour doudou denise", "bonjour andy", "coucou")), json_encode($result));
        $db->query("del mykey63");
        $result = $db->query("mget mykey64 mykey63 mykey65");
        $this->assertEquals(json_encode(array("bonjour doudou denise", false, "coucou")), json_encode($result));
        $db->close();
    }
}
