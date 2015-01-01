<?php
/**
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */
header('Content-Type: text/xhtml; charset=utf-8');
$http = eZHTTPTool::instance();

$db = eZNoSqlDB::instance();
$query = array(
    'database' => 3,
    'command' => 'get',
    'query' => 'voiture'
);
$nodeListArray = $db->arrayQuery($query);
echo $nodeListArray;

echo "\r\n";
eZDebug::printReport(false, false);
echo "\r\n";
eZDB::checkTransactionCounter();
eZExecution::cleanExit();
