<?
$module_id = 'zr.stoplist';

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/prolog.php");

use Bitrix\Main\Localization\Loc;
use ZrStudio\StopList\UserIpRuleTable;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/admin_tools.php');
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/tools.php');

\Bitrix\Main\Loader::includeModule($module_id);
$MOD_RIGHT = $APPLICATION->GetGroupRight($module_id);

if($MOD_RIGHT < "W")
{
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$userId = $_REQUEST['ID'] ?: -1;
$sid = $_REQUEST['SITE_ID'];

$curUser = UserIpRuleTable::getById($userId)->fetch();
if (!$curUser)
{
   //LocalRedirect('/bitrix/admin/zr_stoplist_users_list.php?lang='.LANG);
   $ID = -1;
}


$message = null;

$bVarsFromForm = false;
$aTabs = array(
    array(
        "DIV" => "edit", 
        "TAB" => Loc::getMessage('ZR_STOP_TAB_TITLE'), 
        "ICON"=>"main_user_edit", 
        "TITLE"=>Loc::getMessage('ZR_STOP_TAB_TITLE')
    ),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);
$bVarsFromForm=false;

if($REQUEST_METHOD == "POST" && ($save!="" || $apply!="") && $MOD_RIGHT=="W" && check_bitrix_sessid())
{
	$bVarsFromForm = true;
	$arFields = Array(
		"STATUS" => $_REQUEST['STATUS']=='Y'?'Y':'N',
        "IP"     => $_REQUEST['IP']
	);

	if($ID > 0)
    {
		$res = UserIpRuleTable::update($curUser['ID'], $arFields);
	}
    else
    {
		$resAdd = UserIpRuleTable::add($arFields);
		$res = $resAdd->isSuccess();
        $ID = $resAdd->getID();
	}

    if($res)
    {
        debugF($ID);
		$curUser = UserIpRuleTable::getById($ID)->fetch();

		if ($apply != "")
        {
            LocalRedirect("/bitrix/admin/zr_stoplist_users_list-edit.php?ID=".$ID."&SITE_ID=".SITE_ID."&mess=ok&lang=".LANG);
        }
		else
        {
            LocalRedirect("/bitrix/admin/zr_stoplist_users_list-edit.php?lang=".LANG);
        }
	}
    else
    {
		if($e = $APPLICATION->GetException())
        {
			$message = new CAdminMessage(Loc::getMessage('ZR_STOP_LIST_SAVE_ERROR'), $e);
		}

		$bVarsFromForm = true;
	}
    unset($ID);
}

$aMenu[] = array(
    "TEXT" => Loc::getMessage('MAIN_ADMIN_MENU_LIST'),
    "ICON" => "btn_list",
    "LINK" => "/bitrix/admin/zr_stoplist_users_list.php?lang=".LANG,
    "WARNING" => "N"
);

if($ID>0)
{
	$aMenu[] = array(
		"TEXT" => Loc::getMessage('MAIN_ADMIN_MENU_DELETE'),
		"ICON" => "btn_delete",
		"LINK" => "javascript:if(confirm(".Loc::getMessage('ZR_STOP_TITLE_QUESTION_DELETE').")) window.location='/bitrix/admin/zr_stoplist_users_list.php?ID=".$ID."&action=delete&lang=".LANG."&".bitrix_sessid_get()."';",
		"WARNING" => "Y"
	);
}

$context = new CAdminContextMenu($aMenu);

$APPLICATION->SetTitle(Loc::getMessage('ZR_STOP_TITLE', ['#USER_ID#' => '#'.$ID]));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if($_REQUEST["mess"] == "ok")
{
    CAdminMessage::ShowMessage(array("MESSAGE"=>Loc::getMessage('ZR_STOP_LIST_SAVE_SUCCESS'), "TYPE"=>"OK"));
}

if($message)
{
    echo $message->Show();
}

$context->Show();
if($bFileman)
{
	CMedialibTabControl::ShowScript();
    CJSCore::Init(array("jquery"));
}
?>
<form method="POST" Action="<?=$APPLICATION->GetCurPage()?>" ENCTYPE="multipart/form-data" name="zr_stoplist_user_edit">
    <?
        echo bitrix_sessid_post();
        $tabControl->Begin();
        $tabControl->BeginNextTab();
    ?>

    <? if ($ID !== -1): ?>
        <tr>
            <td width="40%"><?=Loc::getMessage('ZR_STOP_LIST_ID');?>:</td>
            <td width="60%">
                <input type="hidden" name="ID" value="<?=$ID?>"/>
                <b><?=$ID;?></b>
            </td>
        </tr>
    <? endif; ?>
	<tr>
		<td width="40%"><?=Loc::getMessage('ZR_STOP_LIST_STATUS');?>:</td>
		<td width="60%">
            <?  
                $status = $curUser['STATUS']=='Y'?'checked':'';
                if ($ID == -1) $status = 'checked';
            ?>  
			<input type="checkbox" name="STATUS" value="Y" <?=$status?> />
		</td>
	</tr>
    <tr>
		<td width="40%"><?=Loc::getMessage('ZR_STOP_LIST_IP');?>:</td>
		<td width="60%">
			<input type="text" name="IP" value="<?=$curUser['IP']?>" />
		</td>
	</tr>
<?
$tabControl->Buttons(
	array(
		"disabled"=>$MOD_RIGHT<"W",
		"back_url"=>"zr_stoplist_users_list.php?lang=".LANG,
	)
);
?>
<input type="hidden" name="lang" value="<?=LANG?>">

<?
$tabControl->End();
?>
</form>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
?>
