<?
namespace ZrStudio\StopList;

use Exception,
    ZrStudio\StopList\User;

class UserCur extends User
{

    /** @var string $ip user ip */
    private string $ip;

    /** @var string $userIpRule user data from table ip user rule */
    private array $userIpRule;

    private static ?UserCur $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): UserCur
    {
        if (self::$instance === null)
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
        $this->ip = $this->getUserIp();
        $this->userIpRule = $this->getUserIpRuleData($this->ip);
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    /**
     * Check is active module
     *
     * @param string $sid site id
     * @return bool
     */
    private static function _isModuleActive(string $sid = SITE_ID)
    {
        return \Bitrix\Main\Config\Option::get('zr.stoplist', 'module_active_'.$sid) == 'Y';
    }

    /**
     * Get last user activities in count option 'count_check_activity_*sid*'
     *
     * @return \Bitrix\Main\ORM\Query\Result
     */
    public function getListActivities()
    {
        return $this->_getListActivities($this->ip);
    }

    /**
     * Add user action for current user
     *
     * @param string $action action name
     * @return bool is success or not
     */
    public function addUserAction(string $action)
    {
        return $this->createAction($this->userIpRule['ID'], $action);
    }

    /**
     * Check cur user check for suspicious activity.
     * If found raise 403 page and die site
     *
     * @param string $sid site id (SITE_ID)
     * @return bool
     */
    public static function checkAccess(string $sid = SITE_ID)
    {
        $sid = $sid == 'ru' ? 's1' : $sid;
        if (!self::_isModuleActive($sid)) return true;
        self::getInstance();

        $isAllowed = self::checkUserAccess();
        if ($isAllowed) 
        {
            return true;
        }
        self::showPage403();
        return true;
    }

    /**
     * Blocked cur user
     *
     * @return void
     */
    public static function banCurUser()
    {
        self::_banUser('current');
    }

    /**
     *  Unblocked cur user
     * 
     *  @return void
     */
    public static function unBanCurUser()
    {
        self::_unBanUser('current');
    }
}