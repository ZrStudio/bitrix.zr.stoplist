<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;

$module_id = 'zr.stoplist';
$prefix = 'ZR_STOPLIST_';

include($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/'.$module_id.'/default_option.php');

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/options.php');
Loc::loadMessages(__FILE__);

// проверка прав на настройки модуля
if ($APPLICATION->GetGroupRight($module_id) < 'S')
{
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

Loader::includeModule($module_id);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$aTabs = [];
$rsSites = CSite::GetList($by = 'sort', $order = 'asc', ['ACTIVE' => 'Y']);
while ($arSite = $rsSites->Fetch())
{
    $arOptions = [];

    // Main setting
    $arOptions = array_merge($arOptions,
    [
        Loc::getMessage($prefix .'HEADER_BASE_SETTINGS'),
        [
            'module_active_'. $arSite['LID'],
            Loc::getMessage($prefix .'ACTIVE'),
            $zr_stoplist_default_option['module_active_s1'],
            ['checkbox']
        ],
        [
            'count_check_activity_'. $arSite['LID'],
            Loc::getMessage($prefix .'COUNT_CHECK_ACTIVITY'),
            $zr_stoplist_default_option['count_check_activity_s1'],
            ['text', 5]
        ],
        [
            'max_allow_time_b/t_actions_'. $arSite['LID'],
            Loc::getMessage($prefix .'MAX_ALLOW_TIME_BETWEEN_ACTIONS'),
            $zr_stoplist_default_option['max_allow_time_b/t_actions_s1'],
            ['text', 5]
        ],
        [
            'allow_error_time_b/t_actions_'. $arSite['LID'],
            Loc::getMessage($prefix .'ALLOW_ERROR_TIME_BETWEEN_ACTIONS'),
            $zr_stoplist_default_option['allow_error_time_b/t_actions_s1'],
            ['text', 5]
        ],
    ]);

    // Text
    $arOptions = array_merge($arOptions,
    [
        Loc::getMessage($prefix .'HEADER_TEXT_SETTINGS'),
        [
            '403_page_title_'. $arSite['LID'],
            Loc::getMessage($prefix .'TEXT_403_TITLE'),
            Loc::getMessage($prefix .'TEXT_403_TITLE_TEXT'),
            ['textarea', 3, 100]
        ],
        [
            '403_page_message_'. $arSite['LID'],
            Loc::getMessage($prefix .'TEXT_403_MESSAGE'),
            Loc::getMessage($prefix .'TEXT_403_MESSAGE_TEXT'),
            ['textarea', 5, 100]
        ],
    ]);

    // Text
    $arOptions = array_merge($arOptions,
    [
        Loc::getMessage($prefix .'HEADER_WHITE_LIST_SETTINGS'),
        [
            'white_list_'. $arSite['LID'],
            Loc::getMessage($prefix .'WHITE_LIST'),
            '',
            ['textarea', 10, 50]
        ],
    ]);

    $aTabs[] =
    [
        'DIV' => 'settings_'. $arSite['LID'],
        'TAB' => $arSite['NAME'].' ('.$arSite['LID'].')',
        'OPTIONS' => $arOptions
    ];
}


// сохранение настроек
if ($request->isPost() && $request['Update'] && check_bitrix_sessid())
{
    foreach ($aTabs as $aTab)
    {
        foreach ($aTab['OPTIONS'] as $arOption)
        {
            if (!is_array($arOption)) continue;
            if ($arOption['note']) continue;
            __AdmSettingsSaveOption($module_id, $arOption);
        }
    }
}

// вывод формы
$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<?$tabControl->Begin();?>
<form method="POST" action="<?=$APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($request['mid'])?>&lang=<?=$request['lang']?>" name="zr_stoplist_settings">
    <?=bitrix_sessid_post()?>
    <?foreach ($aTabs as $aTab)
    {
        if ($aTab['OPTIONS'])
        {
            $tabControl->BeginNextTab();
            __AdmSettingsDrawList($module_id, $aTab['OPTIONS']);
        }
    }?>
    <? $tabControl->Buttons(); ?>

    <input type="submit" name="Update" value="<?=Loc::getMessage('MAIN_SAVE')?>">
    <input type="reset" name="reset" value="<?=Loc::getMessage('MAIN_RESET')?>">
</form>
<?$tabControl->End();?>