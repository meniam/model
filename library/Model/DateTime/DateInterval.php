<?php

namespace Model\DateTime;

/**
* Полный прокси для DateInterval
* 
* Причину ненаследования от DateInterval смотри в исходниках PHP
*/
class DateInterval
{
    protected $_interval = null;
    protected $_days = null;
    
    public function __construct($interval)
    {
        if ($interval instanceof DateInterval) {
            $this->_interval = $interval;
            return;
        }
        if ($interval instanceof DateInterval) {
            $this->_interval = $interval->getInterval();
            return;
        }
        
        $this->_interval = new DateInterval($interval);
    }

    /**
     * @param int $s
     * @param int $i
     * @param int $h
     * @param int $d
     * @param int $m
     * @param int $y
     * @return DateInterval
     */
    public static function create($s, $i = 0, $h = 0, $d = 0, $m = 0, $y = 0)
    {
        return new self("P" . self::_createIntervalString($s, $i, $h, $d, $m, $y));
    }

    protected static function _createIntervalString($s, $i, $h, $d, $m, $y, $delim = 'T')
    {
        $str = "";
        if ($s || $i || $h) {
            if ($s) {
                $i += intval($s/60);
                $s = $s % 60;
                $str = $s . "S" . $str;
            }
            if ($i) {
                $h += intval($i/60);
                $i = $i % 60;
                $str = $i . "M" . $str;
            }
            if ($h) {
                $d += intval($h/24);
                $h = $h % 24;
                $str = $h . "H" . $str;
            }
            $str = $delim . $str;
        }
        if ($d || $m || $y) {
            if ($d) {
                $str = $d . "D" . $str;
            }
            if ($m) {
                $y += intval($m/12);
                $m = $m % 12;
                $str = $m . "M" . $str;
            }
            if ($y) {
                $str = $y . "Y" . $str;
            }
        }
        if (!$str) {
            $str = $delim . "0S";
        }
        return $str;
    }
    
    /**
    * Получить оригинальный объект интервала
    * 
    * @return DateInterval
    */
    public function getInterval()
    {
        return $this->_interval;
    }

    /**
     * @param $property
     *
     * @return mixed
     * @throws \Exception
     */
    public function get($property)
    {
        return $this->__get($property);
    }

    /**
     * @param string $property
     *
     * @throws \Exception
     * @return mixed
     */
    public function __get($property)
    {
        $originalProperties = array('y', 'm', 'd', 'h', 'i', 's', 'invert');
        if (in_array($property, $originalProperties)) {
            return $this->getInterval()->$property;
        } elseif ($property == 'days') {
            return $this->getTotalDays();
        }
        
        throw new \Exception('Unknown property "'.$property.'"');
    }
    
    /**
    * Получить общее кол-во дней интервала
    * 
    * @param int $precision
    * @return int|float
    */
    public function getTotalDays($precision = 0)
    {
        if ($this->_days === null) {
            if (!$this->getInterval()->get('days') || $this->getInterval()->get('days') == -99999) {
                if ($this->get('d') || $this->get('m') || $this->get('y')) {
                    $days = $this->y * 365 + $this->m * 30 + $this->d;
                } else {
                    $days = 0;
                }
            } else {
                $days = $this->getInterval()->get('days');
            }
            $this->_days = intval($days);
        }
        $days = $this->_days;
        if ($precision > 0) {
            $days += $this->h / 24 + $this->i / (24*60) + $this->s / (24*60*60);
            $days = round($days, $precision);
        }
        return $days;
    }
    
    /**
    * Получить общее кол-во часов интервала
    * 
    * @param int $precision
    * @return int|float
    */
    public function getTotalHours($precision = 0)
    {
        $hours = $this->getTotalDays(0) * 24 + intval($this->h);
        if ($precision > 0) {
            $hours += $this->i / (60) + $this->s / (60*60);
            $hours = round($hours, $precision);
        }
        return $hours;
    }
	
    /**
    * Получить общее кол-во минут интервала
    * 
    * @param int $precision
    * @return int|float
    */
    public function getTotalMinutes($precision = 0)
    {
        $minutes = $this->getTotalHours(0) * 60 + intval($this->i);
        if ($precision > 0) {
            $minutes += $this->s / (60);
            $minutes = round($minutes, $precision);
        }
        return $minutes;
    }
	
    /**
    * Получить общее кол-во секунд интервала
    * 
    * @return int
    */
    public function getTotalSeconds()
    {
		return $this->getTotalMinutes(0) * 60 + intval($this->s);        
    }
    
    /**
    * @param string $time
    * @return DateInterval
    */
    public static function createFromDateString($time)
    {
        return new self(DateInterval::createFromDateString($time));
    }
    
    /**
    * @param string $format
    * @return string
    */
    public function format($format)
    {
        $format = str_replace('%a', $this->getTotalDays(0), $format);
        return $this->getInterval()->format($format);
    }
    
    public function __toString()
    {
        return self::_createIntervalString($this->s, $this->i, $this->h, $this->d, $this->m, $this->y, ' ');
    }
}