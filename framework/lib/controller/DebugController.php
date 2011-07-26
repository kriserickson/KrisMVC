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
        $startTime = microtime(true);
        $this->_controllerPath = $controllerPath;
        $route =  RouteRequest::CreateFromUri($_SERVER['REQUEST_URI']);

        if ($route->Controller == 'KrisMVCDebug')
        {
            $this->EmitDebugAssets($route->Action);
        }
        else
        {
            ob_start();
            $this->ParseRequest($route->Controller, $route->Action, $route->Params);
            $endTime = microtime(true);
            $time = (int)(($endTime - $startTime) * 1000);
            $content = ob_get_clean();


            $db = AutoLoader::GetDatabaseHandle();
            /** @var $db DebugPDO */
            $databaseQueryCount = count($db->DatabaseLog);


            $webBar = '<div id="krisMvcWebBar"><ul><li>Config</li><li>Logs</li><li>Database: '.$databaseQueryCount.'</li><li>Time: '.$time.'ms</li></ul></div>'.
                '<link rel="stylesheet" href="'.KrisConfig::WEB_FOLDER.'/KrisMVCDebug/debug.css" type="text/css" media="screen" />'.
                '<script type="text/javascript" src="'.KrisConfig::WEB_FOLDER.'/KrisMVCDebug/debug.js"></script>';

            echo str_replace('</body>', $webBar.'</body>', $content);

        }

    }

    private function EmitDebugAssets($asset)
    {
        switch($asset)
        {
            case 'debug.js':
                header('Content-type: application/javascript');
                echo '';
                break;
            case 'debug.css' :
                header('Content-type: text/css');
                echo '#krisMvcWebBar
    {
    overflow: hidden;
    width: 100%;
    height: 30px;
    position: absolute;
    bottom: 0;
    left: 0;
    }

#krisMvcWebBar ul {
list-style-type:none;
}
#krisMvcWebBar ul li
{
float:left; padding:14px;
color:#666; margin-top:-3px;
}
#krisMvcWebBar ul li a
{
text-decoration:none;
color:#fff; padding: 10px;
font-size:12px;  font-weight:normal;
font-family:Arial;
}
#krisMvcWebBar ul li a:hover { color:#000033;}
    ';
                break;
            default:
                throw new Exception('Invalid Debug Asset: '.$asset);
        }
    }


}
