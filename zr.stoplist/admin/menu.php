<?
IncludeModuleLangFile(__FILE__);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/zr.stoplist/prolog.php");

include($_SERVER["DOCUMENT_ROOT"]."/bitrix/css/zr.stoplist/menu.css");

$aMenu = array(
	"parent_menu" => "global_menu_services",
	"section" => "zr.stoplist",
	"sort" => 210,
	"text" => 'ZR.STOPLIST',
	"title" => 'ZR.STOPLIST',
	"icon" => "icon-menu-zr-stoplist",
	"page_icon" => "icon-menu-zr-stoplist",
	"items_id" => "zr_stoplist",
	"items" => array(),
);

/** @global CUser $USER */
if($USER->isAdmin())
{
	$aMenu["items"][] = array(
		"text" => 'Список пользователей',
		"url" => "zr_stoplist_users_list.php?lang=".LANGUAGE_ID,
		"more_url" => Array("zr_stoplist_users_list-edit.php"),
		"title" => 'Список пользователей',
	);
}

/** @global CUser $USER */
if($USER->isAdmin())
{
	$aMenu["items"][] = array(
		"text" => 'Список активности пользователей',
		"url" => "zr_stoplist_user_activity_list.php?lang=".LANGUAGE_ID,
		"more_url" => Array("zr_stoplist_user_activity_list-edit.php"),
		"title" => 'Список активностипользователей',
	);
}

if((isset($aMenu["items"]) && count($aMenu["items"]) > 0) || (isset($aMenu[0]["items"]) && count($aMenu[0]["items"]) > 0))
	return $aMenu;
else
	return false;
?>