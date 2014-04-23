<?php

namespace Model\DateTime;

use \DateInterval;

class DateTime extends \DateTime
{
    const DATE_ONLY = 'Y-m-d';
    const TIME_ONLY = 'H:i:s';
    const DATE_FULL = 'Y-m-d H:i:s';

    protected static $_today;
    protected static $_yesterday;
    protected static $_tomorrow;

    /**
     * @param string $time
     * @param null   $timezone
     */
    public function __construct($time = 'now', $timezone = null)
    {
        if ($time instanceof \MongoDate) {
            parent::__construct(date('Y-m-d H:i:s', $time->sec), null);
            return;
        }

        if ($time instanceof \DateTime) {
            parent::__construct($time->format(self::DATE_FULL), $time->getTimezone());
            return;
        }

        if (is_integer($time)) {
            parent::__construct(date('Y-m-d H:i:s', $time), null);
            return;
        }
        
        if (empty($time)) {
            $time = 'now';
        }

        if ($timezone) {
            if (!$timezone instanceof \DateTimeZone) {
                $timezone = new \DateTimeZone($timezone);
            }
            parent::__construct($time, $timezone);
        } else {
            parent::__construct($time);
        }
    }
    
    /**
    * @return bool
    */
    public function isYesterday()
    {
        return self::getYesterday()->format('Y-m-d') == $this->format('Y-m-d');
    }
    
    /**
    * @return bool
    */
    public function isToday()
    {
        return self::getToday()->format('Y-m-d') == $this->format('Y-m-d');
    }
    
    /**
    * @return bool
    */
    public function isTomorrow()
    {
        return self::getTomorrow()->format('Y-m-d') == $this->format('Y-m-d');
    }

    /**
     * Проверяет будний ли день
     * @param array $dayOffList Список дней (в цифром представлении, начиная с воскреснья - воскресенье - 0 и т.д. пятница - 5, суббота - 6)
     * которые, считать выходными (по умолчанию суббота и воскресенье)
     * @return int
     */
    public function isWeekday($dayOffList = array(0, 6))
    {
        return !$this->isDayOff($dayOffList);

    }

    /**
     * Проверяет выходной  ли день
     * @param array $dayOffList Список дней (в цифром представлении, начиная с воскреснья - воскресенье - 0 и т.д. пятница - 5, суббота - 6)
     *                         которые, считать выходными (по умолчанию суббота и воскресенье)
     * @return int
     */
    public function isDayOff($dayOffList = array(0, 6))
    {
        return in_array($this->format('w'), $dayOffList);
    }

    /**
    * @return DateTime
    */
    public static function getYesterday()
    {
        if (!self::$_yesterday) {
            self::$_yesterday = new self('yesterday');
        }

        return self::$_yesterday;
    }

    /**
    * @return DateTime
    */
    public static function getToday()
    {
        if (!self::$_today) {
            self::$_today = new self('today');
        }

        return self::$_today;
    }

    /**
     * @param \DateTimeZone|string $timezone
     * @return DateTime
     */
    public static function getNow($timezone = null)
    {
        return new self('now', $timezone);
    }

    /**
    * @return DateTime
    */
    public static function getTomorrow()
    {
        if (!self::$_tomorrow) {
            self::$_tomorrow = new self('tomorrow');
        }

        return self::$_tomorrow;
    }
    
    /**
    * Прибавить $hours часов
    * 
    * @param integer $hours
    * @return DateTime
    */
    public function addHour($hours)
    {
        $hours = intval($hours);
        if ($hours < 0) {
            $func = 'sub';
        } else {
            $func = 'add';
        }
        $this->$func(new DateInterval('PT' . abs($hours) . 'H'));
        return $this;
    }

    /**
    * Прибавить $minutes минут
    *
    * @param integer $minutes
    * @return DateTime
    */
    public function addMinute($minutes)
    {
        $minutes = intval($minutes);
        if ($minutes < 0) {
            $func = 'sub';
        } else {
            $func = 'add';
        }
        $this->$func(new DateInterval('PT' . abs($minutes) . 'M'));
        return $this;
    }
    
    /**
    * Прибавить $days дней
    * 
    * @param integer $days
    * @return DateTime
    */
    public function addDay($days)
    {
        $days = intval($days);
        if ($days < 0) {
            $func = 'sub';
        } else {
            $func = 'add';
        }
        $this->$func(new DateInterval('P' . abs($days) . 'D'));
        return $this;
    }
    
    /**
    * Прибавить $months месяцев
    * 
    * @param integer $months
    * @return DateTime
    */
    public function addMonth($months)
    {
        $months = intval($months);
        if ($months < 0) {
            $func = 'sub';
        } else {
            $func = 'add';
        }
        $this->$func(new DateInterval('P' . abs($months) . 'M'));
        return $this;
    }

    /**
     * Прибавить $years лет
     *
     * @param $years
     *
     * @internal param int $months
     * @return DateTime
     */
    public function addYear($years)
    {
        $years = intval($years);
        if ($years < 0) {
            $func = 'sub';
        } else {
            $func = 'add';
        }
        $this->$func(new DateInterval('P' . abs($years) . 'Y'));
        return $this;
    }
    
    /***************************************************************
    * OVERRIDES
    ***************************************************************/

    /**
    * @param DateTime $datetime2
    * @param bool $absolute
    * @return DateInterval
    */
    public function diff($datetime2, $absolute = false)
    {
        return new DateInterval(parent::diff($datetime2, $absolute));
    }
    
    /**
     * @param \DateTimeZone|string $timezone
     * @return DateInterval
     */
    public function diffWithNow($timezone = null)
    {
        return self::getNow($timezone)->diff($this);
    }
    
    /**
    * @param \DateTimeZone|string $timezone
    * @return DateTime
    */
    public function setTimezone($timezone)
    {
        if (!$timezone instanceof \DateTimeZone) {
            $timezone = new \DateTimeZone($timezone);
        }
        parent::setTimezone($timezone);
        return $this;
    }
    
    /**
     * @return DateTime 
     */
    public function setDefaultTimezone()
    {
        return $this->setTimezone(date_default_timezone_get());
    }
    
    public function toString($format = self::DATE_FULL)
    {
        return $this->format($format);
    }
    
    public function __toString()
    {
        return $this->toString();
    }
}