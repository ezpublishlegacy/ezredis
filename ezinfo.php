<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */

class eZRedisInfo
{
    public function info()
    {
        return array( 'Name' => "Redis database extension",
                      'Version' => eZRedis::version(),
                      'Copyright' => "Copyright (C) 2014 Ralantonisainana Dany",
                      'License' => "GPL Version 2 and higher"
                     );
    }
}
