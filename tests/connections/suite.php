<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */

class eZRedisConnectionsSuite extends ezpTestSuite
{
    public function __construct()
    {
        parent::__construct();
        $this->setName("eZ Publish Redis Connections Test Suite");

        $this->addTestSuite('ConnectTest');
        $this->addTestSuite('DatabaseTest');
    }

    public static function suite()
    {
        return new self();
    }
}
