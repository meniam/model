<?php

namespace Model\Entity\Decorator;

use Zend\Uri\Uri;

/**
 * Правильная работа c URL
 *
 * @author Eugene Myazin (meniam@gmail.com)
 */
class UrlDecorator implements DecoratorInterface
{
	protected $_uri        = null;
	protected $_uriParsed  = array();

	protected $_checkValue = null;
	
	/**
	 * То что после ? в URL знак ? не включен в результат
	 */
	const URL_PART_QUERY = 'query';

	/**
	 */
	const URL_PART_DOMAIN = 'host';

    /**
	 */
	const URL_PART_PATH = 'path';
	
	public function __construct($uri = null)
	{
		$this->_uri = $uri;
	}

	/**
	 *
	 * @return boolean
	 */
	public function check()
	{
		if ($this->_checkValue === null) {
			$this->_checkValue = (new Uri($this->_uri))->isValid();
		}
		
		return $this->_checkValue;
	}
	/**
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->_uri;
	}

    /**
     *
     * @param bool $withWww
     *
     * @return string
     */
	public function getDomain($withWww = true)
	{
		$domain = $this->getUrlPart(UrlDecorator::URL_PART_DOMAIN);
		if ($domain && !$withWww) {
			$domain = preg_replace('#^www\.#', '', $domain);
		}
		
		return $domain;
	}

    /**
	 * @return string
	 */
	public function getQuery()
	{
		return $this->getUrlPart(UrlDecorator::URL_PART_QUERY);
	}

    /**
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->getUrlPart(UrlDecorator::URL_PART_PATH);
	}
	
	/**
	 * Получить часть от URL
	 * 
	 * @param string $part UrlDecorator::URL_PART_*
	 * @return mixed
	 */
	public function getUrlPart($part)
	{
		$parsedUrl = $this->parseUrl($this->_uri);
		
		$result = '';
		if (isset($parsedUrl[$part])) {
			$result = $parsedUrl[$part];
		}
		
		return $result;
	}
	
	/**
	 *
	 * @param string $url
	 * @return array
	 */
	protected function parseUrl($url)
	{
		if (empty ($this->_uriParsed)) {
			$this->_uriParsed = parse_url($url);
		}
		
		return $this->_uriParsed;
	}

    public function getFullUrl()
    {
        if (!preg_match('#^(http)#si', $this->_uri)) {
            return 'http://' . $this->_uri;
        }

        return $this->_uri;
    }
}
