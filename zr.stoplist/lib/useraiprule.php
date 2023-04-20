<?
namespace ZrStudio\StopList;

use Bitrix\Main\Entity,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\BooleanField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class UserIpRuleTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'zr_stoplist_user_ip_rules';
    }

    public static function getMap()
    {
        return array(
            'ID' => new IntegerField('ID', array(
                'autocomplete' => true,
                'primary' => true,
                'title' => Loc::getMessage('ZR_STOPLIST_USER_IP_RULE_ID'),
            )),
            'STATUS' => new BooleanField('STATUS', array(
				'values' => array('N', 'Y'),
				'default_value' => 'Y',
                'title' => Loc::getMessage('ZR_STOPLIST_USER_IP_RULE_ACTIVITY'),
			)),
            'IP' => new StringField('IP', array(
				'required' => true,
                'unique' => true,
				'title' => Loc::getMessage('ZR_STOPLIST_USER_ACTIONS_IP'),
			)),
        );
    }

    /**
     * Get user Result bu ip address
     *
     * @param string $userIp user ip address
     * @return \Bitrix\Main\ORM\Query\Result
     */
    public static function getUserByIp(string $userIp)
    {
        return self::getList(array(
            'select' => array('*'),
            'filter' => array('=IP' => $userIp),
            'limit' => 1,
        ));
    }

    /**
     * Ban user by ip address
     *
     * @param string $userIp ip address
     * @return bool is success ban or not
     */
    public static function banUserByIp(string $userIp)
    {
        $user = self::getUserByIp($userIp)->fetchObject();
        if (!$user) return false;

        $res = self::update($user->getId(), ['STATUS' => 'N']);
        return $res->isSuccess();
    }

    /**
     * Unban user by ip address
     *
     * @param string $userIp ip address
     * @return bool is success unban or not
     */
    public static function unBanUserByIp(string $userIp)
    {
        $user = self::getUserByIp($userIp)->fetchObject();
        if (!$user) return false;

        $res = self::update($user->getId(), ['STATUS' => 'Y']);
        return $res->isSuccess();
    }

    /**
     * Clear old user where not a banned
     *
     * @return void
     */
    public static function clearUsers()
    {
        $res = self::getList(array(
            'order' => ['ID' => 'ASC'],
            'select' => ['ID'],
            'filter' => array('=STATUS' => 'Y'),
            'cache' => array(
                'ttl' => '3600',
                'join' => 'Y'
            )
        ));

        while ($item = $res->fetchObject())
        {
            $item->delete();
        }
    }
}