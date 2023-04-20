<?
$module_id = 'zr.stoplist';

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $module_id . "/prolog.php");

use Bitrix\Main\Localization\Loc;
use ZrStudio\StopList\UserActionsTable;

Loc::loadMessages(__FILE__);

\Bitrix\Main\Loader::includeModule($module_id);
$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);

if ($MOD_RIGHT < "W")
{
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$tableId = 'zr_stoplist_users_actions';
$sortPanel = new CAdminSorting($tableId, "ID", "DESC");
$adminPanel = new CAdminList($tableId, $sortPanel);

if(($arID = $adminPanel->GroupAction()))
{
	if($_REQUEST['action_target']=='selected')
	{
		$arID = array();
		$rsData = UserActionsTable::getList(array(
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
				UserActionsTable::delete($ID);
			break;
		}
	}

    unset($ID);
}

$arFilterPanel = ['ID', 'USER_ID', 'ACTION', 'TIMESTAMP_X', 'CUSER_' => 'USER'];
$adminPanel->InitFilter($arFilterPanel);

$arNavParams = array(
    "nPageSize" => CAdminResult::GetNavSize($tableId),
    "bDescPageNumbering" => Loc::getMessage('ZR_STOP_LIST_B_DESC'),
    "bShowAll" => false,
);

$adminPanel->bMultipart = true;

$arFilterUser = array();
if (strlen($ID) > 0)
{
    $arFilterUser['ID'] = $ID;
}
if (strlen($USER_ID) > 0)
{
    $arFilterUser['USER_ID'] = $USER_ID;
}
if (strlen($ACTION) > 0)
{
    $arFilterUser['ACTION'] = $ACTION;
}
if (strlen($TIMESTAMP_X) > 0)
{
    $arFilterUser['TIMESTAMP_X'] = $TIMESTAMP_X;
}

$rs = UserActionsTable::getList(array(
	'order' => array($by => $order),
	'select' => $arFilterPanel,
    'filter' => $arFilterUser
));

$arUserData = [];
while ($e = $rs->fetchObject())
{
    $arUser = [
        'ID' => $e->getId(),
        'USER' => [
            'IP' => 'Пользователь удален'
        ],
        'ACTION' => $e->getAction(),
        'TIMESTAMP_X' => $e->getTimestampX()
    ];

    $user = $e->getUser();
    if ($user)
    {
        $arUser['USER'] = [
            'ID' => $user->getId(),
            'IP' => $user->getIp(),
            'ACTIVE' => $user->getStatus() ? 'Y':'N',
        ];
    }
    $arUserData[] = $arUser;
}

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
        "id" => "USER_ID",
        "content" => Loc::getMessage('ZR_STOP_LIST_USER_ID'),
        "sort" => "USER_ID",
        "default" => true,
    ),
    array(
        "id" => "ACTION",
        "content" => Loc::getMessage('ZR_STOP_ACTION'),
        "sort" => "ACTION",
        "default" => true,
    ),
    array(
        "id" => "TIMESTAMP_X",
        "content" => Loc::getMessage('ZR_STOP_TIMESTAMP_X'),
        "sort" => "TIMESTAMP_X",
        "default" => true,
    ),
));

while($arUser = $rsData->NavNext())
{
    $row =& $adminPanel->AddRow($arUser['ID'], $arUser);
    $row->AddViewField("ID", $arUser['ID']);

    $rowUser = '<a href="/bitrix/admin/zr_stoplist_users_list-edit.php?lang='.LANGUAGE_ID.'&ID='.$arUser['USER']['ID'].'">['.$arUser['USER']['ID'].'] '.$arUser['USER']['IP'].' ('.$arUser['USER']['ACTIVE'].')'.'</a>';
    $row->AddViewField("USER_ID", $rowUser);

    $row->AddViewField("ACTION", $arUser['ACTION']);
    $row->AddViewField("TIMESTAMP_X", $arUser['TIMESTAMP_X']);

    $arActions = array();
    //$arActions[] = array(
    //    "ICON" => "edit",
    //    "TEXT" => Loc::getMessage("MAIN_ADMIN_LIST_EDIT"),
    //    "ACTION" => $adminPanel->ActionRedirect("zr_stoplist_users_list-edit.php?ID=".$arUser["ID"]."&lang=".LANGUAGE_ID),
    //    "DEFAULT" => true,
    //);
    $arActions[] = array(
        "ICON" => "delete",
        "DEFAULT" => false,
        "TEXT" => Loc::getMessage("MAIN_ADMIN_LIST_DELETE"),
        "ACTION" => "if(confirm('" . Loc::getMessage('ZR_STOP_TITLE_QUESTION_DELETE') . "')) " . $adminPanel->ActionDoGroup($arUser['ID'], "delete")
    );
    $row->AddActions($arActions);
}
$adminPanel->AddGroupActionTable(array(
    "edit" => Loc::getMessage("MAIN_ADMIN_LIST_EDIT"),
    "delete" => Loc::getMessage("MAIN_ADMIN_LIST_DELETE")
));

$adminPanel->AddAdminContextMenu(array());
$adminPanel->AddFooter(
    array(
        array("title" => Loc::getMessage('ZR_STOP_TITLE_USERS_SP'), "value" => $rsData->SelectedRowsCount()),
        array("counter" => true, "title" => Loc::getMessage('ZR_STOP_LIST_TITLE_SEL_ORDER_SP'), "value" => "0"),
    )
);
$oFilter = new CAdminFilter(
	$sTableID."_filter",
	array(
		'ID' => Loc::getMessage('ZR_STOP_LIST_FILTER_ID'),
		'USER_ID' => Loc::getMessage('ZR_STOP_LIST_FILTER_USER_ID'),
	)
);

$adminPanel->CheckListMode();
$APPLICATION->SetTitle(Loc::getMessage('ZR_STOP_TITLE'));
?>

<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");?>
<form name="zr_stop_list_users_activity" method="get" action="<?=$APPLICATION->GetCurPage()?>?">
<?
$oFilter->Begin();
?>
<tr valign="center">
	<td><b><?=Loc::getMessage('ZR_STOP_LIST_FILTER_ID')?>:</b></td>
	<td><input type="text" name="ID" value="<?=htmlspecialcharsEx($ID)?>" /></td>
</tr>
<tr valign="center">
	<td><?=Loc::getMessage('ZR_STOP_LIST_FILTER_USER_ID');?>:</td>
	<td><input type="text" name="USER_ID" value="<?=htmlspecialcharsEx($USER_ID)?>" /></td>
</tr>
<?
$oFilter->Buttons(array("table_id" => $sTableID,"url" => $APPLICATION->GetCurPage(),"form" => "zr_stop_list_users_activity"));
$oFilter->End();

$adminPanel->DisplayList();

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>