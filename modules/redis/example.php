<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */
// header('Content-Type: text/xhtml; charset=utf-8');


$db = eZNoSqlDB::instance();
$db->selectDatabase(1);
// Simple key -> value set
$db->query('set voiture6 maison4 ex 100 xx');

// Simple key -> value get
$db = eZNoSqlDB::instance();
$db->selectDatabase(1);
$result = $db->arrayQuery('get voiture6');
var_dump($result);

// Transaction Redis
$db->begin();
$db->selectDatabase(3);
$db->query('keys *');
$db->query('sort myslist desc');
$result = $db->commit();
$db->close();
var_dump($result);

echo "\r\n";
eZDebug::printReport(false, false);
echo "\r\n";
eZDB::checkTransactionCounter();
eZExecution::cleanExit();
