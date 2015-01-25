<?php

/**
 * This benchmark is based on redisent library benchmark
 * @author Dany RALANTONISAINANA <lendormi1984@gmail.com>
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or any later version)
 */

require 'autoload.php';

$cli = eZCLI::instance();

$script = eZScript::instance(array(
    'description' => ("eZ Publish database No Sql."),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true )
);

$script->startup();

$options = $script->getOptions(
    "",
    "",
    array()
);

$script->initialize();

$db = eZNoSqlDB::instance();
$db->selectDatabase(0);

$params = array(
    'IgnoreVisibility' => true,
    'Depth'            => 8,
    'SortBy'           => array( 'published', false )
);
$params['ClassFilterType'] = 'include';
$params['ClassFilterArray'] = array('article', 'image', 'folder');

// $nodesCount = \eZContentObjectTreeNode::subTreeCountByNodeID($params, 2);
$offset = 0;
if ($nodesCount > 0) {
    $cli->notice('There are '.$nodesCount.' datas!');
    $cli->notice('You have 5 seconds to break the script (press Ctrl-C)');
    sleep(5);
    $i = $offset;
    for ($o=$offset; $o<$nodesCount; $o=$o+100) {
        $params['Offset'] = $o;
        $params['Limit'] = 100;
        $nodes = \eZContentObjectTreeNode::subTreeByNodeID($params, 2);
        if (count($nodes) > 0) {
            foreach ($nodes as $node) {
                $i++;
                $db->begin();
                $redis = $db->useRedis();
                $key = "node:".$node->attribute('node_id');
                $result = $db->query("hmset ".$key."
                    node_id ".$node->attribute('node_id')."
                    name \"".htmlentities($node->attribute('name'), ENT_QUOTES)."\"
                    class_identifier ".$node->attribute('class_identifier')."
                    url_alias ".$node->urlAlias()."
                    data_map \"".htmlentities(serialize($node->dataMap()), ENT_QUOTES)."\"
                ");
                if (!$result) {
                    echo "-";
                } else {
                    echo "+";
                }
                $db->commit();
                unset($GLOBALS['eZContentObjectContentObjectCache']);
                unset($GLOBALS['eZContentObjectDataMapCache']);
                unset($GLOBALS['eZContentObjectVersionCache']);
            }
        }
        echo "  ".(100*($o+$i)/$nodesCount)."%".PHP_EOL;
    }
}
$db->close();
$script->shutdown();
