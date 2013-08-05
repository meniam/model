<?php

namespace Model\Db\Mysql;

/**
 * Абстрактный класс для всех SQL классов
 *
 * @category   CategoryName
 * @package    PackageName
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
abstract class AbstractSql
{
    abstract public function getSql();

    /**
     * Quote value
     *
     * @param mixed $valueList
     * @return string
     */
    public function quote($valueList)
    {
        $valueList = str_replace('\'', '\\' . '\'', $valueList);

        if (is_array($valueList)) {
            $valueList = implode('\', \'', $valueList);
        }
        return '\'' . $valueList . '\'';
    }

    /**
     * Quote identifier
     *
     * @param string|string[]  $identifier
     * @return string
     */
    public function quoteIdentifier($identifier)
    {
        $identifier = str_replace('`', '\\`', $identifier);
        if (is_array($identifier)) {
            $identifier = implode('`.`', $identifier);
        }
        return '`' . $identifier . '`';
    }

    /**
     * Quote identifier in fragment
     *
     * @param  string $identifier
     * @param  array $safeWords
     * @return string
     */
    public function quoteIdentifierInFragment($identifier, array $safeWords = array())
    {
        $parts = preg_split('#([\.\s\W])#', $identifier, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $i => $part) {
            if ($safeWords && in_array($part, $safeWords)) {
                continue;
            }
            switch ($part) {
                case ' ':
                case '.':
                case '*':
                case 'AS':
                case 'As':
                case 'aS':
                case 'as':
                    break;
                default:
                    $parts[$i] = '`' . str_replace('`', '\\' . '`', $part) . '`';
            }
        }
        return implode('', $parts);
    }
}