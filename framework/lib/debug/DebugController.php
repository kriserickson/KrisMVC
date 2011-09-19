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
            /** @var $log DebugLog */
            $log = AutoLoader::$Container->get('Log');
            $log->Debug('Controller path: '.$controllerPath.' Controller: '.$route->Controller.' Action: '.$route->Action.' Params: ('.implode(',', $route->Params).')');

            $startTime = microtime(true);
            ob_start();
            try
            {
                $this->ParseRequest($route->Controller, $route->Action, $route->Params);
            }
            catch (Exception $ex)
            {
                $trace = $ex->getTrace();
                $message = '';
                foreach ($trace as $line)
                {
                    $message .= '&nbsp;&nbsp;'.(isset($line['file']) ? 'file: '.$line['file'].', ' : 'Anonymous: ').(isset($line['line']) ? ' line: '.$line['line'].' : ' : '').
                            (isset($line['class']) ? $line['class'].'->' : '').
                            $line['function'].'('.implode(',', array_map(create_function('$a', 'return gettype($a);'), $line['args'])).')'.PHP_EOL;
                }
                $log->Error('Uncaught exception: '.$ex->getMessage().PHP_EOL.$message);
            }
            $endTime = microtime(true);
            $content = ob_get_clean();

            if ($this->_request->IsHtml)
            {
                $elapsedTime = ($endTime - $startTime) * 1000;
                if (strpos($content, '</body>') !== false)
                {
                    echo str_replace('</body>', $this->GetWebBar($elapsedTime, $log) . '</body>', $content);
                }
                else
                {
                    echo '<html><body>'.$content.$this->GetWebBar($elapsedTime, $log).'</body></html>';
                }
            }
            else
            {
                echo $content;
            }
        }

    }

    /**
     * @param int $elapsedTime
     * @param DebugLog $log
     * @return string
     */
    public function GetWebBar($elapsedTime, $log)
    {
        $jsVars = '';
        $dbVars = '';

        // Get Database Log
        $db = AutoLoader::$Container->get('PDO');
        /** @var $db DebugPDO */
        $databaseQueryCount = count($db->DatabaseLog);
        $dbTime = 0;
        if ($databaseQueryCount > 0)
        {
            for ($i = 0; $i < $databaseQueryCount; $i++)
            {
                $dbLogItem = $db->DatabaseLog[$i];
                $dbVars .= ($i > 0 ? ','
                        : '') . '{ func: "' . $dbLogItem['function'] . '", query: "' . $dbLogItem['query'] . '", milliseconds: ' . number_format($dbLogItem['microseconds'] * 1000, 2) . '}';
                $dbTime += $dbLogItem['microseconds'];
            }
        }
        $dbTime *= 1000;

        // Get Memory Log
        $peakUsage = memory_get_peak_usage(true);
        $currentUsage = memory_get_usage(true);
        $memory = NumberHelpers::BytesToHuman($peakUsage);
        $currentMemory = NumberHelpers::BytesToHuman($currentUsage);

        $jsVars .= 'dbLog = [' . $this->CleanJSVar($dbVars) . '];' . PHP_EOL;
        $jsVars .= "memoryLog = '<h2>Memory Info</h2><b>Peak Memory Usage: </b><code>$memory ($peakUsage Bytes)</code><br/>" .
                "<b>Current Usage: </b><code>$currentMemory ($currentUsage Bytes)</code><br/>';" . PHP_EOL;

        // Get Time Log
        $jsVars .= "timeLog = '<h2>Time Info</h2><b>Time To Create Page: </b><code>" . number_format($elapsedTime, 4) . " milliseconds</code><br/>" .
                "<b>Database Query Time: </b><code>" . number_format($dbTime, 4) . " milliseconds</code><br/>" .
                "<b>Processing Time: </b><code>" . number_format($elapsedTime - $dbTime, 2) . " milliseconds</code><br/>';" . PHP_EOL;

        $jsVars .= "debugLog = '" . $this->CleanJSVar($log->GetErrorLog()) . "';" . PHP_EOL;

        $debugPrefix = KrisConfig::WEB_FOLDER . '/KrisMVCDebug/';

        $webBar = '<div id="krisMvcDebugDataHolder"><div id="krisMvcDebugData"></div></div>' .
                '<div id="krisMvcWebBarButton" style="display:none"><span class="showbar"><a href="#">show bar</a></span></div>' .
                '<div id="krisMvcWebBar" style="display:none"><div class="leftside"><ul id="debugConfig">' .
                '<li class="debugList" id="version"><strong>KrisMVC</strong> Version: ' . KRIS_MVC_VERSION . '</li>' .
                '<li class="debugList" id="config"><img title="View Configuration" src="' . $debugPrefix . 'plugin.png">Config</li>' .
                '<li class="debugList" id="logs"><img title="View Logs" src="' . $debugPrefix . 'page_white_text.png">Logs</li>' .
                '<li class="debugList" id="database"><img title="Database Logs" src="' . $debugPrefix . 'database.png">Database: ' . $databaseQueryCount . '</li>' .
                '<li class="debugList" id="time"><img title="Time Elapsed" src="' . $debugPrefix . 'clock_play.png">Time: ' . (int)($elapsedTime) . 'ms</li>' .
                '<li class="debugList" id="memory"><img title="Peak Memory" src="' . $debugPrefix . 'chart_curve.png">Memory: ' . $memory . '</li></ul></div>' .
                '<div class="rightside"><span class="downarr"><a href="#"></a></span></div></div>' .
                '<link rel="stylesheet" href="' . $debugPrefix . 'debug.css" type="text/css" media="screen" />' .
                '<script type="text/javascript" src="' . $debugPrefix . 'debug.js"></script>' .
                '<script type="text/javascript">' . $jsVars . '</script>';

        return $webBar;
    }

    private function CleanJSVar($debugLog)
    {
        return str_replace(PHP_EOL, '', nl2br(str_replace("'", "\\'", str_replace('\\', '\\\\', $debugLog))));
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
                readfile(KrisConfig::FRAMEWORK_DIR.'/lib/debug/js/debug.js');
                break;
            case 'debug.css' :
                header('Content-type: text/css');
                readfile(KrisConfig::FRAMEWORK_DIR.'/lib/debug/css/debug.css');
                break;
            default:
                header('Content-type: image/png');
                readfile(KrisConfig::FRAMEWORK_DIR.'/lib/debug/images/'.$asset);

        }
    }


}
