<?
$module_id = 'zr.stoplist';

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $module_id . "/prolog.php");

use Bitrix\Main\Localization\Loc,
    ZrStudio\StopList\UserIpRuleTable;

Loc::loadMessages(__FILE__);

\Bitrix\Main\Loader::includeModule($module_id);
$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);

if ($MOD_RIGHT < "W")
{
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$tableId = 'zr_stoplist_users';
$sortPanel = new CAdminSorting($tableId, "ID", "DESC");
$adminPanel = new CAdminList($tableId, $sortPanel);

if(($arID = $adminPanel->GroupAction()))
{
	if($_REQUEST['action_target']=='selected')
	{
		$arID = array();
		$rsData = UserIpRuleTable::getList(array(
			"select" => array("ID"),
		));

		while($arRes = $rsData->fetch())
		{
			$arID[] = $arRes['ID'];
		}
	}

	foreach($arID as $ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
			continue;

		switch($_REQUEST['action'])
		{
			case "delete":
				UserIpRuleTable::delete($ID);
			break;
		}
	}
}

$arFilterPanel = array('ID', 'STATUS', 'IP');
$adminPanel->InitFilter($arFilterPanel);

$arNavParams = array(
    "nPageSize" => CAdminResult::GetNavSize($tableId),
    "bDescPageNumbering" => Loc::getMessage('ZR_STOP_LIST_B_DESC'),
    "bShowAll" => false,
);

$adminPanel->bMultipart = true;

$arFilterUser = array();
if (strlen($SITE_ID) > 0)
{
    $arFilterUser['SITE_ID'] = $SITE_ID;
}
if (strlen($ID) > 0)
{
    $arFilterUser['ID'] = $ID;
}
if (strlen($STATUS) > 0)
{
    $arFilterUser['STATUS'] = $STATUS;
}
if (strlen($IP) > 0)
{
    $arFilterUser['IP'] = $IP;
}

$rs = UserIpRuleTable::getList(array(
	'order' => array($by => $order),
	"select" => $arFilterPanel,
    'filter' => $arFilterUser
));

while ($e = $rs->fetch())
    $arUserData[] = $e;

$rsData = new CDBResult;
$rsData->InitFromArray($arUserData);
$rsData = new CAdminResult($rsData, $tableId);

$rsData->NavStart();
$adminPanel->NavText($rsData->GetNavPrint(Loc::getMessage("PAGES")));

$adminPanel->AddHeaders(array(
    array(
        "id" => "ID",
        "content" => "ID",
        "sort" => "ID",
        "default" => true,
    ),
    array(
        "id" => "STATUS",
        "content" => Loc::getMessage('ZR_STOP_LIST_STATUS'),
        "sort" => "STATUS",
        "default" => true,
    ),
    array(
        "id" => "IP",
        "content" => Loc::getMessage('ZR_STOP_LIST_IP'),
        "sort" => "IP",
        "default" => true,
    ),
));

while($arUser = $rsData->NavNext())
{
    $row =& $adminPanel->AddRow($arUser['ID'], $arUser);
    $row->AddViewField("ID", $arUser['ID']);
    $row->AddViewField("STATUS", $arUser['STATUS'] == 'Y' ? '<span style="color:green;">Не заблокирован</span>': '<span style="color: red;">Заблокирован</span>');
    $row->AddViewField("IP", $arUser['IP']);

    $arActions = array();
    $arActions[] = array(
        "ICON" => "edit",
        "TEXT" => Loc::getMessage("MAIN_ADMIN_LIST_EDIT"),
        "ACTION" => $adminPanel->ActionRedirect("zr_stoplist_users_list-edit.php?ID=".$arUser["ID"]."&lang=".LANG),
        "DEFAULT" => true,
    );
    $arActions[] = array(
        "ICON" => "delete",
        "DEFAULT" => false,
        "TEXT" => Loc::getMessage("MAIN_ADMIN_LIST_DELETE"),
        "ACTION" => "if(confirm('" . Loc::getMessage('ZR_STOP_TITLE_QUESTION_DELETE') . "')) " . $adminPanel->ActionDoGroup($arRes['ID'], "delete")
    );
    $row->AddActions($arActions);
}
$adminPanel->AddGroupActionTable(array(
    "edit" => Loc::getMessage("MAIN_ADMIN_LIST_EDIT"),
    "delete" => Loc::getMessage("MAIN_ADMIN_LIST_DELETE")
));

$aContext = array(
	array(
		"TEXT"	=> Loc::getMessage('MAIN_ADMIN_ITEM_ADD'),
		"LINK"	=> "zr_stoplist_users_list-edit.php?lang=".LANG,
		"TITLE"	=> Loc::getMessage('MAIN_ADMIN_ITEM_ADD'),
		"ICON"=>"btn_new",
	),
);
$adminPanel->AddAdminContextMenu($aContext);
$adminPanel->AddFooter(
    array(
        array("title" => Loc::getMessage('ZR_STOP_LIST_TITLE_USERS_SP'), "value" => $rsData->SelectedRowsCount()),
        array("counter" => true, "title" => Loc::getMessage('ZR_STOP_TITLE_SEL_ORDER_SP'), "value" => "0"),
    )
);

$adminPanel->CheckListMode();
$APPLICATION->SetTitle(Loc::getMessage('ZR_STOP_TITLE'));

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

$adminPanel->DisplayList();

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>