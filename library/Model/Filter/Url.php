<?php

namespace Model\Filter;

class Url extends AbstractFilter
{
    protected $_options;

    const STRIP_SCHEME   = 1;
    const STRIP_PORT     = 2;
    const STRIP_USER     = 4;
    const STRIP_PASS     = 8;
    const STRIP_AUTH     = 12;
    const STRIP_HOST     = 32;
    const STRIP_PATH     = 64;
    const STRIP_QUERY    = 128;
    const STRIP_FRAGMENT = 256;

    protected static $_stripConstAssoc;

    protected static $_stripUrlParamAssoc = array(
                                                self::STRIP_SCHEME   => 'scheme',
                                                self::STRIP_HOST     => 'host',
                                                self::STRIP_PORT     => 'port',
                                                self::STRIP_USER     => 'user',
                                                self::STRIP_PASS     => 'pass',
                                                self::STRIP_AUTH     => '-unknown-',
                                                self::STRIP_PATH     => 'path',
                                                self::STRIP_QUERY    => 'query',
                                                self::STRIP_FRAGMENT => 'fragment');

    /**
     * @param null $options
     */
    public function __construct($options = null)
    {
        $this->_options = (array)$options;
    }

    public function filter($value)
    {
        $format = isset($this->_options['format']) ? $this->_options['format'] : 1;

        if (!$format) {
            return $value;
        }

        $bit = $format;

        $urlParams = parse_url($value);

        $stripConsts = self::getStripConstAsArray();

        while ($bit > 0) {
            foreach ($stripConsts as $stripConst) {
                if ($bit >= $stripConst) {
                    if ($stripConst == self::STRIP_AUTH) {
                        unset($urlParams[self::$_stripUrlParamAssoc[self::STRIP_USER]]);
                        unset($urlParams[self::$_stripUrlParamAssoc[self::STRIP_PASS]]);
                    } else {
                        unset($urlParams[self::$_stripUrlParamAssoc[$stripConst]]);
                    }
                    $bit ^= $stripConst;
                    break;
                }
            }
        }

        return $this->buildUrl($urlParams);
    }

    public static function getStripConstAsArray()
    {
        if (!self::$_stripConstAssoc) {
            $reflect = new \ReflectionClass('\Model\Filter\Url');
            $constantList = $reflect->getConstants();

            foreach ($constantList as $const => $value) {
                if (strpos($const, 'STRIP_') !== false) {
                    self::$_stripConstAssoc[$const] = $value;
                }
            }

            arsort(self::$_stripConstAssoc);
        }

        return self::$_stripConstAssoc;
    }

    public function buildUrl($urlParts)
    {
        if (empty($urlParts)) {
            return '';
        }

        $result = '';
        if (isset($urlParts['scheme'])) {
            $result .= $urlParts['scheme'] . '://';
        }

        if (isset($urlParts['host'])) {
            $result .= $urlParts['host'];
        }

        if (isset($urlParts['user'])) {
            $result .= $urlParts['user'];
        }

        if (isset($urlParts['pass']) && isset($urlParts['user'])) {
            $result .= ':' . $urlParts['pass'] . '@';
        }


        if (isset($urlParts['port'])) {
            $result .=  ':' . $urlParts['port'];
        }

        if (isset($urlParts['path'])) {
            if (isset($this->_options['strip_end_slash']) && $this->_options['strip_end_slash']) {
                $result .= rtrim($urlParts['path'], '/ ');
            } else {
                $result .=  $urlParts['path'];
            }
        }

        if (isset($urlParts['query'])) {
            if (isset($this->_options['sort_query_string']) && $this->_options['sort_query_string']) {
                $result .= '?' . $this->sortQueryString($urlParts['query']);
            } else {
                $result .= '?' . $urlParts['query'];
            }
        }

        if (isset($urlParts['fragment'])) {
            $result .= '#' . $urlParts['fragment'];
        }

        return $result;
    }

    protected function sortQueryString($queryString)
    {
        $params = array();
        parse_str($queryString, $params);
        ksort($params);

        array_walk_recursive($params, function($item) {
            return urlencode($item);
        });

        return http_build_str($params, '', '&');
    }
}