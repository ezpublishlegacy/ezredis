<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */

class eZRedisSuite extends ezpTestSuite
{
    public function __construct()
    {
        parent::__construct();
        $this->setName("eZ Publish Redis Test Suite");

        $this->addTestSuite('eZRedisSetSuite');
        $this->addTestSuite('eZRedisHashesSuite');
        $this->addTestSuite('eZRedisConnectionsSuite');
    }

    public static function suite()
    {
        return new self();
    }

    public function setUp()
    {
        eZDir::recursiveDelete(eZINI::instance()->variable('FileSettings', 'VarDir'));
        eZContentLanguage::expireCache();
    }
}
