<?php declare(strict_types=1);


namespace Fuyukai\Userspace\Session;


class SessionHandler
{
    private const DEFAULT_GROUP = 'GLOBAL';
    private const EXPIRE_KEY = '__expire';
    private const LIFETIME_MINUTES = 30;
    
    /**
     * SessionHandler constructor.
     */
    public function __construct()
    {
        ini_set('session.use_strict_mode', '1');
        // set max session timeout * 4 time
        ini_set('session.gc_maxlifetime', (string)((self::LIFETIME_MINUTES * 60) * 4));
        if(session_id() == '') {
            session_start();
        }
    }
    
    public function init()
    {
    
    }
    
    /**
     * @param string $key
     * @param string $group
     * @return string
     */
    public function getValue(string $key, string $group = ''): string
    {
        if (!$group) {
            $group = self::DEFAULT_GROUP;
        }
        
        if (isset($_SESSION[$group][$key])) {
            return (string)$_SESSION[$group][$key];
        }
    
        return '';
    }
    
    /**
     * @param string $value
     * @param string $key
     * @param string $group
     */
    public function setValue(string $value, string $key, string $group = '')
    {
        if (!$group) {
            $group = self::DEFAULT_GROUP;
        }
        
        $_SESSION[$group][$key] = (string)$value;
    }
    
    /**
     * Destroy current Session and generate a new "Clean" one
     */
    public function wipe(): void
    {
        session_unset();
        session_destroy();
        session_commit();
        unset($_SESSION);
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
        session_regenerate_id(true);
    }
    
    /**
     *
     */
    public function updateExpireTimeout(): void
    {
        $this->setValue((string)(time() + (self::LIFETIME_MINUTES * 60)), self::EXPIRE_KEY);
    }
    
    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        $expireTs = (int)$this->getValue(self::EXPIRE_KEY);
        return ($expireTs < time());
    }
}