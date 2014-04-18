<?php

class UsernameDecorator extends Model_Entity_Decorator_Abstract
{
    protected $_surname;
    protected $_name;

    protected $_useLocale = true;

    /**
     * Использовать ли локаль?
     *
     * @param boolean $value
     * @return boolean Старое значение
     */
    public function setUseLocale($value = null)
    {
        $oldValue = $this->_useLocale;

        if (!is_null($value)) {
            $this->_useLocale = (bool)$value;

        }

        return $oldValue;
    }

    public function getName()
    {
        $result = trim($this->_name);
        return $this->_name ? $this->_name : 'Анонимный';
    }

    public function getSurname()
    {
        return $this->_surname;
    }

    /**
     * Юзать локаль ?
     * 
     * @return boolean
     */
    public function isUseLocale()
    {
        return (bool)$this->_useLocale;
    }

    /**
     * Конструктор
     *
     * @param string $name
     * @internal param $price цена
     * @internal param $currency валюта
     * @return \UsernameDecorator
     */
	public function __construct($input)
	{
        $this->_name = $input;
	}

    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function setSurname($surname)
    {
        $this->_surname = $surname;
        return $this;
    }

    public function existsSurname()
    {
        return !empty($this->_surname);
    }

    public function getShortNameAndSurname()
    {
        $result = $this->getName();

        if ($this->existsSurname()) {
            $result .= ' ' . mb_substr($this->getSurname(), 0, 1, 'UTF-8') . '.';
        }

        return $result;
    }

	public function getFullName()
	{
		return $this->_translit($this->getRawFullName());
	}

	public function getRawFullName()
	{
		return $this->getSurname() . ' ' . $this->getName();
	}

    public function getNameAndSurname()
    {
        return $this->_translit($this->getRawNameAndSurname());
    }

    public function getRawNameAndSurname()
    {
        return $this->getName() . ' ' . $this->getSurname();
    }

    /**
     * Транслитерировать имя если нужно
     *
     * @param string $name
     * @return string
     */
    protected function _translit($name)
    {
        if ($this->isUseLocale() && $this->getTranslator()->getLocale() != 'ru') {
            $name = App_Translit::text($name);
        }

        return $name;
    }

    /**
     * Получить локаль
     *
     * @return Zend_Locale
     */
    protected function getLocale()
    {
        return Model_Locale::getDefaultLocale();
    }

    /**
     * Получить локаль
     *
     * @return Zend_Translate
     */
    protected function getTranslator()
    {
        return Model_Translator::getDefaultTranslator();
    }

	/**
	 * Получить в виде строки
	 * @return string
	 */
	public function __toString()
	{
		return (string)$this->getSurname() . ' ' . (string)$this->getName();
	}
}
