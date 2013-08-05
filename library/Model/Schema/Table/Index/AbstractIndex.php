<?php

namespace Model\Schema\Table\Index;

use Model\Schema as Schema;
use Model\Schema\Table as Table;
use Model\Schema\Table\Column as Column;

use Model\Schema\Table\Index\Exception\ErrorException as ErrorException;

/**
 * Abstract table index
 *
 * @category   Schema
 * @package    Table
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
abstract class AbstractIndex extends \ArrayIterator
{
    const TYPE_PRIMARY  = 'Model\Schema\Table\Index\Primary';
    const TYPE_UNIQUE   = 'Model\Schema\Table\Index\Unique';
    const TYPE_KEY      = 'Model\Schema\Table\Index\Key';

    /**
     *
     * @var string
     */
    protected $_type;

    /**
     *
     * @var string
     */
    protected $_name;

    /**
     *
     * @param array $name
     * @param array $columns
     * @throws Model\Schema\Table\Index\Exception\ErrorException
     */
    public function __construct($name, array $columns)
    {
        $this->_name    = (string)$name;
        $this->_type    = get_class($this);

        if (empty($columns)) {
            throw new ErrorException('Index without columns? Hm.');
        }

        foreach ($columns as $column) {
            if (!$column instanceof Column) {
                throw new ErrorException('Column is not an instance of Model\Schema\Table\Column');
            }
        }
        
        parent::__construct($columns);
    }

    /**
     * Получить имя индекса
     *
     * @return string
     */
    public function getName()
    {
        /** @var $this AbstractIndex */
        return (string)$this->_name;
    }

    /**
     * Уникальный ли индекс
     *
     * @return bool
     */
    public function isUnique()
    {
        return ($this->getType() == self::TYPE_PRIMARY || $this->getType() == self::TYPE_UNIQUE);
    }

    /**
     * Получить тип ключа
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Является ли ключ составным
     *
     * @return bool
     */
    public function isMultiple()
    {
        return $this->count() > 1;
    }
    
    public function toArray($deep = true)
    {
        $result = array('name' => $this->getName(), 
                        'type' => $this->getType());
        
        if ($deep) {
            $result['columns'] = array();
            foreach ($this as $column) {
                $result['columns'][] = $column->toArray($deep);
            }
        }
        
        return $result;
    }
}