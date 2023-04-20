<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?><?
CHTTP::SetStatus("403 Forbidden");

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

$title = \Bitrix\Main\Config\Option::get('zr.stoplist', '403_page_title_s1');
$message = \Bitrix\Main\Config\Option::get('zr.stoplist', '403_page_message_s1');
?>
<html>
	<head>
		<title>
            <? if ($title):?>
                <?=$title?>
            <? else:?>
                <?=Loc::getMessage('ZR_STOPLIST_TITLE_TEXT')?>
            <? endif;?>
        </title>
	</head>
	<body>
        <? if ($message):?>
            <?=$message?>
        <? else:?>
            <?=Loc::getMessage('ZR_STOPLIST_MESSAGE_TEXT')?>
        <? endif;?>
	</body>
</html>
<?//die();?>
