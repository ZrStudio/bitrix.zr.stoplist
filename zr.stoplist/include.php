<?
use Bitrix\Main\Loader;

$module_id = 'zr.stoplist';
Loader::registerAutoLoadClasses(
    'zr.stoplist', 
    [
        'ZrStudio\StopList\UserActionsTable' => 'lib/useractions.php',
        'ZrStudio\StopList\UserIpRuleTable' => 'lib/useraiprule.php',
        'ZrStudio\StopList\User' => 'classes/general/user.php',
        'ZrStudio\StopList\UserCur' => 'classes/general/usercur.php',
    ]
);
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->registerEventHandlerCompatible('main', 'OnPageStart', $module_id, '\ZrStudio\StopList\UserCur', 'checkAccess', '2');