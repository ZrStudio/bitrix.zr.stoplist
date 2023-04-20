<?
namespace ZrStudio\StopList;

use Bitrix\Main\Application,
    ZrStudio\StopList\UserActionsTable,
    ZrStudio\StopList\UserIpRuleTable;

class User
{
    /**
     * Get user Ip
     *
     * @return string is user ip
     */
    protected static function getUserIp()
    {
        return Application::getInstance()->getContext()->getRequest()->getRemoteAddress();
    }

    protected static function checkIsBlocedUserByIp(string $userIp)
    {
        $user = UserIpRuleTable::getUserByIp($userIp)->fetchObject();
        if (!$user) return false;
        return $user->getStatus() == 'N';
    }

    /**
     * Check laters activity user by user ip address
     *
     * @param string $userIp user ip address
     * @return void
     */
    private static function _exploreLatestActivity(string $userIp, string $sid = SITE_ID)
    {
        $numberCheckActions = \Bitrix\Main\Config\Option::get('zr.stoplist', 'count_check_activity_'.$sid);
        $maxAllowedSecBtActions = \Bitrix\Main\Config\Option::get('zr.stoplist', 'max_allow_time_b/t_actions_'.$sid);
        $allowedErrorSecBtActions = \Bitrix\Main\Config\Option::get('zr.stoplist', 'allow_error_time_b/t_actions_'.$sid);

        $lastActions = UserActionsTable::getLastUserActionsByIp($userIp, $numberCheckActions)->fetchAll();

        $hasTriggerActions = false;
        for ($i = 0; $i < count($lastActions) - 1; $i+=2)
        {
            $dateFirst = $lastActions[$i]['TIMESTAMP_X'];
            $dateSecond = $lastActions[$i + 1]['TIMESTAMP_X'];

            $datesDiff = $dateFirst->getTimestamp() - $dateSecond->getTimestamp();
            if ($datesDiff <= $maxAllowedSecBtActions && ($datesDiff + $allowedErrorSecBtActions) <= $maxAllowedSecBtActions)
            {
                $hasTriggerActions = true;
                break;
            }
        }

        if ($hasTriggerActions)
        {
            self::_banUser('current');
        }
    }

    /**
     * Check user ip address in white list
     *
     * @param string $userIp user ip address
     * @param string $sid site id. default 's1'
     * @return bool is found in white list or not
     */
    private static function _checkInWhiteList(string $userIp, string $sid = SITE_ID)
    {
        $whiteList = \Bitrix\Main\Config\Option::get('zr.stoplist', 'white_list_'.$sid, '');
        $arWhiteList = explode("|",str_replace(array("\r\n", "\r", "\n"),"|",$whiteList."\n"));

        if (in_array($userIp, $arWhiteList)) return true;
        return false;
    }

    /**
     * Check user access in site
     *
     * @param string $userIp user ip address (optional)
     * @return boolean allowed access or not
     */
    protected static function checkUserAccess(string $userIp = '', string $sid = SITE_ID): bool
    {
        // TODO: delete this a crutch
        if ($sid == 'ru') $sid = 's1';

        if (!$userIp)
        {
            $userIp = self::getUserIp();
        }

        $haveInWhiteList = self::_checkInWhiteList($userIp, $sid);
        if ($haveInWhiteList)
        {
            return true;
        }

        $isAllowed = self::checkIsBlocedUserByIp($userIp);
        if ($isAllowed)
        {
            self::_exploreLatestActivity($userIp, $sid);
        }
        return $isAllowed;
    }

    /**
     * Ban current user
     *
     * @param string $userIp ip address
     * @return void
     */
    protected static function _banUser(string $userIp = '')
    {
        if ($userIp == 'current')
        {
            $userIp = self::getUserIp();
        }
        
        $isSuccessBan = UserIpRuleTable::banUserByIp($userIp);
        if ($isSuccessBan)
        {
            self::showPage403(); // die site
        }
    }

    /**
     * Unban current user
     *
     * @param string $userIp ip address
     * @return void
     */
    protected static function _unBanUser(string $userIp = '')
    {
        if ($userIp == 'current')
        {
            $userIp = self::getUserIp();
        }
        UserIpRuleTable::unBanUserByIp($userIp);
    }

    /**
     * Show 403 page and die site
     * !!! SHOW ONLY BANNED USERS
     *
     * @return void
     */
    protected static function showPage403()
    {
        include($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/zr_stoplist_403.php");
    }

    protected function getUserIpRule(string $userIp)
    {
        $result = UserIpRuleTable::getUserByIp($userIp)->fetch();
        return $result;
    }

    /**
     * Get list activity user
     *
     * @param string $userIp user IP address
     * @return \Bitrix\Main\ORM\Query\Result user data
     */
    protected function _getListActivities(string $userIp)
    {
        return UserActionsTable::getList(array(
            'order' => array('TIMESTAMP_X' => 'DESC'),
            'select' => array('*'),
            'filter' => array('=USER.IP' => $userIp)
        ));
    }

    /**
     * Get user ip rule data by IP address
     *
     * @param string $userIp user IP address
     * @return array|bool array with user ip rule data or false
     */
    protected function getUserIpRuleData(string $userIp)
    {
        $userIpRule = $this->getUserIpRule($userIp);
        if ($userIpRule)
        {
            return $userIpRule;
        }
        else
        {
            $resAdd = UserIpRuleTable::add([
                'IP' => $userIp
            ]);

            if ($resAdd->isSuccess())
            {
                return $this->getUserIpRule($userIp);
            }
            else
            {
                return false;
            }
        }
    }

    /**
     * Create action for User Ip Rule
     *
     * @param integer $userId user ip rule id
     * @param string $action name action 
     * @return bool is seccess create action or not
     */
    protected function createAction(int $userId, string $action)
    {
        $resAddAction = UserActionsTable::add([
            'USER_ID' => $userId,
            'ACTION' => $action
        ]);
        return $resAddAction->isSuccess();
    }
}