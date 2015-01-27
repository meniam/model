<?php

namespace Model\Entity\Decorator;

use Model\DateTime\DateTime;
use Model\Entity\EntityInterface;

class DateTimeDecorator extends AbstractDecorator
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
	 * @var DateTime
	 */
	protected $_date = null;

    public function __construct($input = null, EntityInterface $entity = null)
    {
		$this->_date = new DateTime($input);
	}

    /**
     * @param string $format
     *
     * @return string
     */
    public function format($format = 'Y-m-d H:i:s')
	{
		return $this->_date->format($format);
	}
	
	public function getTimestamp()
	{
		return $this->_date->getTimestamp();
	}
   
   /**
    * @return DateTime
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
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->format('Y-m-d H:i:s');
	}
}
