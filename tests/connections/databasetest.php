<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */
require_once 'autoload.php';
class DatabaseTest extends ezpTestCase
{
    public function testDatabaseExist()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $this->assertTrue($db->query("select 0"));
        $db->close();
    }

    /**
     * default config
     * @date   2015-01-21T22:25:07+0100
     */
    public function testDatabaseNumber()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $result = $db->query("config get databases");
        $this->assertEquals("16", $result['databases']);
        $db->close();
    }

    public function testCheckAllDatabase()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $result = $db->query("config get databases");
        for ($i=0; $i < $result['databases']; $i++) {
            $this->assertTrue($db->query("select ".$i));
        }
        $db->close();
    }

    public function testDatabaseDidntExist()
    {
        eZExtension::activateExtensions('default');
        $db = eZNoSqlDB::instance();
        $result = $db->query("config get databases");
        $this->assertFalse($db->query("select ".$result['databases']));
        $db->close();
    }
}
