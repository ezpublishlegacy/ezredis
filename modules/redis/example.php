<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */
// header('Content-Type: text/xhtml; charset=utf-8');


$db = eZNoSqlDB::instance();
$db->selectDatabase(1);
// Simple key -> value set
$db->query('set voiture18 maison86 ex 100');

// // Simple key -> value get
$result = $db->arrayQuery('get voiture18');
echo $result . "<br /> ";

// // Transaction Redis
$db->begin();
$db->selectDatabase(3);
$db->query('keys *');
$db->query('sort myslist desc');
$result = $db->commit();
$db->close();
var_dump($result) . "<br />";

echo "<pre><br />";
eZDebug::printReport(false, false);
echo "<br /><pre>";
eZDB::checkTransactionCounter();
eZExecution::cleanExit();
