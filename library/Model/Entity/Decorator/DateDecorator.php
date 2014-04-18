<?php

class DateDecorator extends Model_Entity_Decorator_Abstract
{
	const FORMAT_FULL = 'd F Y, H:i';

    /**
     * То что пришло в конструктор
     * @var mixed
     */
    protected $_dateSourceValue = null;

    protected static $_language = 'ru';

    protected static $_availableLanguages = array('en', 'ru');

    protected static $_trans = array(
        'en' => array('Yesterday', 'Today', 'Tomorrow'),
        'ru' => array('Вчера', 'Сегодня', 'Завтра'),
    );

    protected static $_days = array(
        'en' => array(
            array('mon', 'monday'),
            array('tue', 'tuesday'),
            array('wed', 'wednesday'),
            array('thu', 'thursday'),
            array('fri', 'friday'),
            array('sat', 'saturday'),
            array('sun', 'sunday')
        ),
        'ru' => array(
            array('пн', 'понедельник'),
            array('вт', 'вторник'),
            array('ср', 'среда'),
            array('чт', 'четверг'),
            array('пт', 'пятница'),
            array('сб', 'суббота'),
            array('вс', 'воскресенье')
        )
    );

    protected static $_month = array(
        'en' => array(
            1 => array('jan', 'January', 'January'),
            2 => array('feb', 'February', 'February'),
            3 => array('mar', 'March', 'March'),
            4 => array('apr', 'April', 'April'),
            5 => array('may', 'May', 'May'),
            6 => array('jun', 'June', 'June'),
            7 => array('jul', 'July', 'July'),
            8 => array('aug', 'Augest', 'Augest'),
            9 => array('sep', 'September', 'September'),
            10 => array('oct', 'October', 'October'),
            11 => array('nov', 'November', 'November'),
            12 => array('dec', 'December', 'December')
        ),
        'ru' => array(
            1 => array('янв', 'январь', 'января'),
            2 => array('фев', 'февраль', 'февраля'),
            3 => array('мар', 'март', 'марта'),
            4 => array('апр', 'апрель', 'апреля'),
            5 => array('май', 'май', 'мая'),
            6 => array('июн', 'июнь', 'июня'),
            7 => array('июл', 'июль', 'июля'),
            8 => array('авг', 'август', 'августа'),
            9 => array('сен', 'сентябрь', 'сентября'),
            10 => array('окт', 'октябрь', 'октября'),
            11 => array('ноя', 'ноябрь', 'ноября'),
            12 => array('дек', 'декабрь', 'декабря')
        )
    );

	/**
	 * Объект времени
	 *
	 * @var App_DateTime
	 */
	protected $_date = null;

	public function __construct($date = null)
	{
		$this->_date = new App_DateTime($date);
		try {
		    self::$_language = $this->_getTranslator()->getLocale();
            if (!in_array(self::$_language, self::$_availableLanguages)) {
                self::$_language = 'en';
            }
        } catch (Model_Entity_Exception $ex) {}
	}

	public function format($format = 'Y-m-d H:i:s')
	{
		return $this->_date->format($format);
	}
	
	public function getTimestamp()
	{
		return $this->_date->getTimestamp();
	}
   
   /**
    * @return App_DateTime
    */
	public function getDate()
	{
		return $this->_date;
	}

	public function getFullDate($isShowTime = true)
	{
		$day = $this->format('j');
		$month =  self::$_month[self::$_language][$this->format('n')][2];
		$year = $this->format('Y');
		return $day . ' ' . $month . ' ' . $year . ($isShowTime ? ', ' . $this->format('H:i') : '');
	}

	public function getCoolDate($isShowTime = true)
	{
		if ($this->_date->isToday()) {
			return self::$_trans[self::$_language][1] . ($isShowTime ? ', ' . $this->format('H:i') : '');
		} else if ($this->_date->isYesterday()) {
			return self::$_trans[self::$_language][0] . ($isShowTime ? ', ' . $this->format('H:i') : '');
		} else if ($this->_date->isTomorrow()) {
			return self::$_trans[self::$_language][2] . ($isShowTime ? ', ' . $this->format('H:i') : '');
		} else {
			return $this->getFullDate($isShowTime);
		}
	}
	/**
	 * Получить название месяца
	 * @param bool $case Брать ли винительный падеж
	 * @return string 
	 */
	public function getMonthName($case = false)
	{
		if ($case) {
			return self::$_month[self::$_language][$this->format('n')][2];
		} else {
			return self::$_month[self::$_language][$this->format('n')][1];
		}
	}

    /**
     * Получить название месяца по позиции месяца
     *
     * @param $monthPos
     * @param bool $case
     * @return string
     */
    public static function getMonthNameByMonthPos($monthPos, $case = false)
    {
        if (isset(self::$_month[self::$_language][$monthPos])) {
            if ($case) {
                return self::$_month[self::$_language][$monthPos][2];
            } else {
                return self::$_month[self::$_language][$monthPos][1];
            }
        }

        return '';
    }

	/**
	 * Получить интервал времени, прошедшего от заданной даты и времени до текущей  
	 */
	public function getIntervalDate()
	{
		$interval =  $this->getDate()->diff(new App_DateTime(), true);
		
		$minutes = $interval->getTotalMinutes();
		$hours = $interval->getTotalHours();
		$days = $interval->getTotalDays();
		
		if ($days > 60) {
			return Model_Translator::translate('ago');
		}
		
		if (($hours % 24 > 12) && ($days !== 0)) {
			$days++;
		}
		if (($minutes % 60 > 30) && ($hours !== 0)) {
			$hours++;
		}
		
		if ($days > 0) {
			return $days . Model_Translator::translate('short_days');
		}
		
		if ($hours > 12) {
			return '~1' . Model_Translator::translate('short_days');
		} elseif ($hours > 0) {
			return $hours . Model_Translator::translate('short_hours');
		}
		
		if ($minutes > 45) {
			return '~1' . Model_Translator::translate('short_hours');
		} elseif ($minutes > 1) {
			return $minutes . Model_Translator::translate('short_minutes');
		} else {
			return Model_Translator::translate('now');
		}
	}

	
	/**
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->format('Y-m-d H:i:s');
	}
}
