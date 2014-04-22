<?php

namespace Model\Entity\Decorator;

use Model\Entity\EntityInterface;

class StringDecorator implements DecoratorInterface
{
	protected $_str = '';

    /**
     * Конструктор
     *
     * @param                               $input
     * @param \Model\Entity\EntityInterface $entity
     *
     * @internal param \Model\Entity\Decorator\ $currency
     * @return \Model\Entity\Decorator\StringDecorator
     */
    public function __construct($input = null, EntityInterface $entity = null)
    {
		$this->_str = $input;
	}

	public function strToUpper()
	{
		return mb_strtoupper($this->_str, 'UTF-8');
	}

	public function strToLower()
	{
        return mb_strtolower($this->_str, 'UTF-8');
	}
	
	public function substr($start, $length = null)
	{
        return mb_substr($this->_str, $start, $length, 'UTF-8');
	}
	
    public function length()
    {
        return mb_strlen($this->_str, 'UTF-8');
    }

	public function ucFirst()
	{
		return mb_strtoupper(mb_substr($this->_str, 0, 1, 'UTF-8'), 'UTF-8') . mb_strtolower(mb_substr($this->_str, 1) , 'UTF-8');
	}

    /**
     * Сделать CamelCase
     * @return string
     */
    public function camelCase()
    {
        return implode('',array_map('ucfirst', explode('_', $this->_str)));
    }
    
    public function replace($search, $replace = null)
    {
        if ($replace == null && is_array($search)) {
            $replace = array_values($search);
            $search = array_keys($search);
        }
        
        return str_replace($search, $replace, $this->_str);
    }

    /**
     *
     * @param                                 $length     Длинна
     * @param string $etc        Что добавить в случае обрезки
     * @param bool   $breadWords Разрешено ли резать слова
     * @param bool   $middle     Делает строку вида http://www.superbols...domain.ru
     *
     *
     * @return string
     */
	public function truncate($length, $etc = '&#133;', $breadWords = false, $middle = false)
	{
		return App_Str::truncate($this->_str, $length, $etc, $breadWords, $middle);
	}

	/**
	 * Удалить пунктуацию в конце строки
	 *
	 * @return string
	 */
	public function stripEndPunctuation()
	{
		return App_Str::stripEndPuntuation($this->_str);
	}

    public function urlTranslit()
    {
        return App_Translit::url($this->_str);
    }
    
	/**
	 * Получить в виде строки
	 * @return string
	 */
	public function __toString()
	{
		return (string)$this->_str;
	}
}
