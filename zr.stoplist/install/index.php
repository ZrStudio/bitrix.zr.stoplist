<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Zrstudio\StopList\UserActionsTable;
use Zrstudio\StopList\UserIpRuleTable;

Loc::loadMessages(__FILE__);

class zr_stoplist extends CModule
{
    private $moduleId = 'zr.stoplist';

    /** @var array */
    protected array $files = [
        '/js/' => '/bitrix/js/zr_stoplist/',
        '/themes/' => '/bitrix/themes/',

        '/admin/zr_stoplist_403.php' => '/bitrix/admin/zr_stoplist_403.php',
        '/admin/zr_stoplist_users_list.php' => '/bitrix/admin/zr_stoplist_users_list.php',
        '/admin/zr_stoplist_users_list-edit.php' => '/bitrix/admin/zr_stoplist_users_list-edit.php',
        '/admin/zr_stoplist_user_activity_list.php' => '/bitrix/admin/zr_stoplist_user_activity_list.php',
        '/admin/zr_stoplist_user_activity_list-edit.php' => '/bitrix/admin/zr_stoplist_user_activity_list-edit.php',
    ];

    public function __construct()
    {
        $arModuleVersion = array();
        
        include __DIR__ . '/version.php';

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }
        
        $this->MODULE_ID = $this->moduleId;
        $this->MODULE_NAME = Loc::getMessage('ZR_STOP_LIST_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('ZR_STOP_LIST_MODULE_DESCRIPTION');
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME = Loc::getMessage('ZR_STOP_LIST_MODULE_PARTNER_NAME');
        $this->PARTNER_URI = 'https:/zrstudio.com/';
    }

    public function doInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->requireInstallLibs();

        \CAgent::AddAgent('\Zrstudio\StopList\UserActionsTable::clearOldActions();', $this->MODULE_ID, 'N', 86400, '', 'Y', '', 100);
        \CAgent::AddAgent('\Zrstudio\StopList\UserIpRuleTable::clearUsers();', $this->MODULE_ID, 'N', 86400 * 3, '', 'Y', '', 100);

        $this->installDB();
        $this->copyFiles();
        $this->InstallEvents();
    }

    public function doUninstall()
    {
        global $DOCUMENT_ROOT, $APPLICATION, $step;

		$step = intval($step);
		if($step<2)
		{   
			$APPLICATION->IncludeAdminFile(GetMessage("ZR_STOP_LIST_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/zr.stoplist/install/unstep1.php");
		}
		elseif($step==2)
		{
            $this->requireInstallLibs();
        
        
            if (!$_REQUEST["savedata"])
            {
                $this->uninstallDB();
            }
			
            \CAgent::RemoveModuleAgents($this->MODULE_ID);
            
            $this->removeFiles();
            $this->UnInstallEvents();

            ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(GetMessage("ZR_STOP_LIST_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/zr.stoplist/install/unstep2.php");
		}
    }

    public function InstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler('main', 'OnPageStart', $this->MODULE_ID, 'Zrstudio\\StopList\\UserCur', 'getInstance');
    }

    public function UnInstallEvents()
    {
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler('main', 'OnPageStart', $this->MODULE_ID, 'Zrstudio\\StopList\\UserCur', 'getInstance');
    }

    public function installDB()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            if (!Application::getConnection()->isTableExists(
                Bitrix\Main\Entity\Base::getInstance('Zrstudio\StopList\UserIpRuleTable')->getDBTableName())
            )
            {
                UserIpRuleTable::getEntity()->createDbTable();
            }

            if (!Application::getConnection()->isTableExists(
                Bitrix\Main\Entity\Base::getInstance('Zrstudio\StopList\UserActionsTable')->getDBTableName())
            )
            {
                UserActionsTable::getEntity()->createDbTable();
            }
        }
    }

    public function uninstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            $connection = Application::getInstance()->getConnection();
            $connection->dropTable(UserIpRuleTable::getTableName());
            $connection->dropTable(UserActionsTable::getTableName());
        }
    }

    /**
     * @return array
     */
    private function copyFiles(): array
    {
        $documentRoot = Application::getDocumentRoot();
        $errors       = [];

        foreach ($this->files as $from => $to) 
        {
            if (!CopyDirFiles(__DIR__ . $from, $documentRoot . $to, true, true)) 
            {
                $errors[] = $from.':'.$to.'<br/>';
            }
        }

        return $errors;
    }

    private function removeFiles()
    {
        foreach ($this->files as $to) 
        {
            DeleteDirFilesEx($to);
        }
    }

    /**
     * @author ZtStudio (Alexandr Drachenin)
     */
    protected function requireInstallLibs()
    {
        require_once __DIR__ . '/../lib/useractions.php';
        require_once __DIR__ . '/../lib/useraiprule.php';
    }

}
