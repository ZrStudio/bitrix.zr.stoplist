<?
namespace ZrStudio\StopList;

use Bitrix\Main,
	Bitrix\Main\Entity,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\ORM\Fields\Relations\Reference,
    Bitrix\Main\ORM\Query\Join,
    ZrStudio\StopList\UserIpRuleTable;

Loc::loadMessages(__FILE__);

class UserActionsTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'zr_stoplist_user_actions';
    }

    public static function getMap()
    {
        return array(
            'ID' => new IntegerField('ID', array(
                'autocomplete' => true,
                'primary' => true,
                'title' => Loc::getMessage('ZR_STOPLIST_USER_ACTIONS_ID'),
            )),
            'USER_ID' => new IntegerField('USER_ID'),
            (new Reference(
                'USER',
                \ZrStudio\StopList\UserIpRuleTable::class,
                Join::on('this.USER_ID', 'ref.ID')
            ))->configureJoinType('left'),
            'ACTION' => new StringField('ACTION', array(
				'required' => false,
				'title' => Loc::getMessage('ZR_STOPLIST_USER_ACTIONS_ACTION'),
			)),
            'TIMESTAMP_X' => new DatetimeField('TIMESTAMP_X', array(
                'default_value' => function() { return new Main\Type\DateTime(); },
				'title' => Loc::getMessage('ZR_STOPLIST_USER_ACTIONS_DATETIME'),
			)),
        );
    }

    /**
     * Get user actions list by user ip
     *
     * @param string $userIp user ip
     * @param array $arSelect array with select fileds
     * @return Main\ORM\Query\Result
     */
    public static function getUserActionsByIp(string $userIp, array $arSelect = ['*']): Main\ORM\Query\Result
    {
        $res = self::getList(array(
            'select' => $arSelect,
            'filter' => array('USER_RULE.IP' => $userIp),
            'runtime' => array(
                new \Bitrix\Main\Entity\ReferenceField(
                    'USER_RULE',
                    UserIpRuleTable::getEntity(),
                    ['=this.USER_ID' => 'ref.ID'],
                    ['join_type' => 'INNER']
                )
            ),
            'cache' => array(
                'ttl' => '3600',
                'join' => 'Y'
            )
        ));
        return $res;
    }

    /**
     * Get last (N) user actions list by user ip
     *
     * @param string $userIp user ip
     * @param int $countActions count user actions your need get
     * @param array $arSelect array with select fileds
     * @return Main\ORM\Query\Result
     */
    public static function getLastUserActionsByIp(string $userIp, int $countActions = 5, array $arSelect = ['*']): Main\ORM\Query\Result
    {
        $res = self::getList(array(
            'order' => ['TIMESTAMP_X' => 'DESC'],
            'select' => $arSelect,
            'filter' => array('USER_RULE.IP' => $userIp),
            'runtime' => array(
                new \Bitrix\Main\Entity\ReferenceField(
                    'USER_RULE',
                    UserIpRuleTable::getEntity(),
                    ['=this.USER_ID' => 'ref.ID'],
                    ['join_type' => 'INNER']
                )
            ),
            'limit' => intval($countActions),
            'cache' => array(
                'ttl' => '3600',
                'join' => 'Y'
            )
        ));
        return $res;
    }

    /**
     * Clear old actions
     *
     * @return void
     */
    public static function clearOldActions()
    {
        $res = self::getList(array(
            'order' => ['TIMESTAMP_X' => 'ASC'],
            'select' => ['ID', 'TIMESTAMP_X', 'USER_STATUS' => 'USER_RULE.STATUS'],
            'runtime' => array(
                new \Bitrix\Main\Entity\ReferenceField(
                    'USER_RULE',
                    UserIpRuleTable::getEntity(),
                    ['=this.USER_ID' => 'ref.ID'],
                    ['join_type' => 'INNER']
                )
            ),
            'filter' => array('<=TIMESTAMP_X' => ConvertTimeStamp(time() - 86400,"FULL"), '=USER_STATUS' => 'Y'),
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