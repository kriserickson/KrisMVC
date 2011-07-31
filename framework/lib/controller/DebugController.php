<?php
/**
 * This file is part of the KrisMvc framework.
 *
 * (c) Kris Erickson 
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
class DebugController extends KrisController
{
    /**
     * @param string $controllerPath
     */
    public function Route($controllerPath)
    {
        $this->_controllerPath = $controllerPath;

        $route =  RouteRequest::CreateFromUri($this->GetRequestUri());

        if ($route->Controller == 'KrisMVCDebug')
        {
            $this->EmitDebugAssets($route->Action);
        }
        else
        {
            $startTime = microtime(true);
            ob_start();
            $this->ParseRequest($route->Controller, $route->Action, $route->Params);
            $endTime = microtime(true);
            $content = ob_get_clean();
            $elapsedTime = ($endTime - $startTime) * 1000;

            $jsVars = '';

            // Get Database Log
            $db = AutoLoader::GetDatabaseHandle();
            /** @var $db DebugPDO */
            $databaseQueryCount = count($db->DatabaseLog);
            $dbTime = 0;
            if ($databaseQueryCount > 0)
            {
                $jsVars .= 'dbLog = [';
                for ($i = 0; $i < $databaseQueryCount; $i++)
                {
                   $dbLogItem = $db->DatabaseLog[$i];
                   $jsVars .=  ($i > 0 ? ',' : '').'{ func: "'.$dbLogItem['function'].'", query: "'.$dbLogItem['query'].'", milliseconds: '.number_format($dbLogItem['microseconds'] * 1000, 2).'}';
                   $dbTime += $dbLogItem['microseconds'];
                }
                $jsVars .= '];';
            }
            $dbTime *= 1000;

            // Get Memory Log
            $peakUsage = memory_get_peak_usage(true);
            $currentUsage = memory_get_usage(true);
            $memory = NumberHelpers::BytesToHuman($peakUsage);
            $currentMemory = NumberHelpers::BytesToHuman($currentUsage);
            $jsVars .= "memoryLog = '<h2>Memory Info</h2><b>Peak Memory Usage: </b><code>$memory ($peakUsage Bytes)</code><br/>".
                    "<b>Current Usage: </b><code>$currentMemory ($currentUsage Bytes)</code><br/>';";

            // Get Time Log
            $jsVars .= "timeLog = '<h2>Time Info</h2><b>Time To Create Page: </b><code>".number_format($elapsedTime,4)." milliseconds</code><br/>".
                    "<b>Database Query Time: </b><code>".number_format($dbTime,4)." milliseconds</code><br/>".
                    "<b>Processing Time: </b><code>".number_format($elapsedTime - $dbTime, 2)." milliseconds</code><br/>';";

            $debugPrefix = KrisConfig::WEB_FOLDER.'/KrisMVCDebug/';

            $webBar = '<div id="krisMvcDebugDataHolder"><div id="krisMvcDebugData"></div></div>'.
                '<div id="krisMvcWebBarButton"><span class="showbar"><a href="#">show bar</a></span></div>'.
                '<div id="krisMvcWebBar"><div class="leftside"><ul id="debugConfig">'.
                '<li class="debugList" id="config"><img title="View Configuration" src="'.$debugPrefix.'plugin.png">Config</li>'.
                '<li class="debugList" id="logs"><img title="View Logs" src="'.$debugPrefix.'page_white_text.png">Logs</li>'.
                '<li class="debugList" id="database"><img title="Database Logs" src="'.$debugPrefix.'database.png">Database: '.$databaseQueryCount.'</li>'.
                '<li class="debugList" id="time"><img title="Time Elapsed" src="'.$debugPrefix.'clock_play.png">Time: '. (int)($elapsedTime) .'ms</li>'.
                '<li class="debugList" id="memory"><img title="Peak Memory" src="'.$debugPrefix.'chart_curve.png">Memory: '.$memory.'</li></ul></div>'.
                '<div class="rightside"><span class="downarr"><a href="#"></a></span></div></div>'.
                '<link rel="stylesheet" href="'.$debugPrefix.'debug.css" type="text/css" media="screen" />'.
                '<script type="text/javascript" src="'.$debugPrefix.'debug.js"></script>'.
                '<script type="text/javascript">'.$jsVars.'</script>';

            echo str_replace('</body>', $webBar.'</body>', $content);

        }

    }

    /**
     * @param string $asset
     * @return void
     */
    private function EmitDebugAssets($asset)
    {
        switch($asset)
        {
            case 'debug.js':
                header('Content-type: application/javascript');
                readfile(KrisConfig::FRAMEWORK_DIR.'/lib/debug/debug.js');
                break;
            case 'debug.css' :
                header('Content-type: text/css');
                readfile(KrisConfig::FRAMEWORK_DIR.'/lib/debug/debug.css');
                break;
            default:
                header('Content-type: image/png');
                readfile(KrisConfig::FRAMEWORK_DIR.'/lib/debug/'.$asset);

        }
    }


}
