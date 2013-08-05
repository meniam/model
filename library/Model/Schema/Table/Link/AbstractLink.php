<?php

namespace Model\Schema\Table\Link;
use Model\Schema\Table as Table;
use Model\Schema\Table\Column as Column;

/**
 * Абстрактный класс связи
 *
 * @category   Schema
 * @package    Table\Link
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
abstract class AbstractLink
{
    /*
    const LINK_TYPE_FOREIGN_KEY = 'foreign_key';
    const LINK_TYPE_VIRTUAL     = 'virtual';
    */
    
    const LINK_TYPE_MANY_TO_MANY     = 'Model\Schema\Table\Link\ManyToMany';
    const LINK_TYPE_ONE_TO_ONE       = 'Model\Schema\Table\Link\OneToOne';
    const LINK_TYPE_ONE_TO_MANY      = 'Model\Schema\Table\Link\OneToMany';
    const LINK_TYPE_MANY_TO_ONE      = 'Model\Schema\Table\Link\ManyToOne';

    const RULE_NO_ACTION  = 'NO ACTION';
    const RULE_SET_NULL   = 'SET NULL';
    const RULE_CASCADE    = 'CASCADE';
    const RULE_RESTRICT   = 'RESTRICT';

    /**
     *
     * @var string
     */
    protected $_linkType;

    /**
     * Что делать при UPDATE
     *
     * @var string
     */
    protected $_ruleUpdate;

    /**
     * Что делать при DELETE
     *
     * @var string
     */
    protected $_ruleDelete;
    
    /**
     * Локальное поле
     *
     * @var Column
     */
    protected $_localColumn;
    
    /**
     * Внешнее поле
     *
     * @var Column
     */
    protected $_foreignColumn;
    
    /**
     * Локальная таблица
     *
     * @var Table
     */
    protected $_localTable;

    /**
     * Внешняя таблица
     *
     * @var Table
     */
    protected $_foreignTable;

    /**
     *
     * @var Column
     */
    protected $_linkTableLocalColumn;

    /**
     *
     * @var Column
     */
    protected $_linkTableForeignColumn;

    /**
     *
     * @var Table
     */
    protected $_linkTable;
       
    public function __construct($name, Column $localColumn, Column $foreignColumn, $ruleDelete = AbstractLink::RULE_NO_ACTION, $ruleUpdate = AbstractLink::RULE_CASCADE, Column $linkTableLocalColumn = null, Column $linkTableForeignColumn = null)
    {
        $this->_name          = (string)($name);
        
        $this->_ruleUpdate    = $ruleUpdate;
        $this->_ruleDelete    = $ruleDelete;
        
        $this->_localColumn   = $localColumn;
        $this->_foreignColumn = $foreignColumn;
        
        $this->_localTable    = $localColumn->getTable();
        $this->_foreignTable  = $foreignColumn->getTable();

        if ($linkTableLocalColumn && $linkTableForeignColumn) {
            $this->_linkTableLocalColumn   = $linkTableLocalColumn;
            $this->_linkTableForeignColumn = $linkTableForeignColumn;
            $this->_linkTable              = $linkTableLocalColumn->getTable();
        }

        $this->setupLinkType();
    }

    /**
     * Получить имя связи
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Получить имя локальной Enitity
     *
     * @return string
     */
    public function getLocalEntity()
    {
        if ($this->_linkTableLocalColumn) {
            $_entity = \preg_replace('#_id$#', '', $this->_linkTableLocalColumn->getName());
        } elseif ($this->getForeignColumn()->getName() != 'id') {
            $_entity = \preg_replace('#_id$#', '', $this->getForeignColumn()->getName());
        } else {
            $_entity = $this->getLocalColumn()->getTable()->getName();
        }

        return strtolower($_entity);
    }

    /**
     * Получить имя локальной Entity в CamelCase виде
     *
     * @return string
     */
    public function getLocalEntityAsCamelCase()
    {
        return implode('', array_map('ucfirst', explode('_', $this->getLocalEntity())));
    }

    /**
     * Получить имя локальной Entity в виде переменной АКА lowerFirstWord
     *
     * @return string
     */
    public function getLocalEntityAsVar()
    {
        $localEntityAsCamelCase = $this->getLocalEntityAsCamelCase();
        return strtolower($localEntityAsCamelCase[0]) . substr($localEntityAsCamelCase, 1);
    }

    /**
     * @return string
     */
    public function getForeignEntity()
    {
        if ($this->_linkTableForeignColumn) {
            $_entity = \preg_replace('#_id$#', '', $this->_linkTableForeignColumn->getName());
        } elseif ($this->getLocalColumn()->getName() != 'id') {
            $_entity = \preg_replace('#_id$#', '', $this->getLocalColumn()->getName());
        } else {
            $_entity = $this->getForeignColumn()->getTable()->getName();
        }

        return strtolower($_entity);
    }

    /**
     * Получить имя внешней Entity в CamelCase виде
     *
     * @return string
     */
    public function getForeignEntityAsCamelCase()
    {
        return implode('', array_map('ucfirst', explode('_', $this->getForeignEntity())));
    }

    /**
     * Получить имя внешней Entity в виде переменной АКА lowerFirstWord
     *
     * @return string
     */
    public function getForeignEntityAsVar()
    {
        $localEntityAsCamelCase = $this->getForeignEntityAsCamelCase();
        return strtolower($localEntityAsCamelCase[0]) . substr($localEntityAsCamelCase, 1);
    }



    /**
     * Определить тип связи
     *
     * @return AbstractLink
     */
    protected function setupLinkType()
    {
        $this->_linkType = get_class($this);
        return $this;
    }
    
    /**
     *
     * @return string
     */
    public function getLinkType()
    {
        return $this->_linkType;
    }

    /**
     * @return \Model\Schema\Table
     */
    public function getLinkTable()
    {
        return $this->_linkTable;
    }

    /**
     *
     * @return Table
     */
    public function getLocalTable()
    {
        return $this->_localTable;
    }
    
    
    /**
     *
     * @return Column
     */
    public function getLocalColumn()
    {
        return $this->_localColumn;
    }

    /**
     *
     * @return Table
     */
    public function getForeignTable()
    {
        return $this->_foreignTable;
    }
    
    
    public function getRuleUpdate()
    {
        return $this->_ruleUpdate;
    }

    public function getRuleDelete()
    {
        return $this->_ruleDelete;
    }
    
    /**
     *
     * @return Column
     */
    public function getForeignColumn()
    {
        return $this->_foreignColumn;
    }

    /**
     * Получить локальную колонку в таблице связки
     *
     * @return \Model\Schema\Table\Column|null
     */
    public function getLinkTableLocalColumn()
    {
       return $this->_linkTableLocalColumn;
    }

    /**
     * Получить внешнюю колонку в таблице связки
     *
     * @return \Model\Schema\Table\Column|null
     */
    public function getLinkTableForeignColumn()
    {
       return $this->_linkTableForeignColumn;
    }

    /**
     * Отобразить в качестве массива
     *
     * @return array
     */
    public function toArray()
    {
        $result = array('name' => $this->getName(),
                        'type' => $this->getLinkType(),
                        'local_table' => $this->getLocalTable()->getName(),
                        'local_column' => $this->getLocalColumn()->getName(),
                        'foreign_table' => $this->getForeignTable()->getName(),
                        'foreign_column' => $this->getForeignColumn()->getName(),
                        'rule_update' => $this->getRuleUpdate(),
                        'rule_delete' => $this->getRuleDelete());

        if ($this->_linkTableLocalColumn && $this->_linkTableForeignColumn) {
            $result['link_table']                = $this->_linkTable->getName();
            $result['link_table_local_column']   = $this->_linkTableLocalColumn->getName();
            $result['link_table_foreign_column'] = $this->_linkTableForeignColumn->getName();
        }

        return $result;
    }

    /**
     * Получить уникальный идентификатор связи
     *
     * @return string
     */
    public function getUniqId()
    {
        $array = $this->toArray();

        unset($array['rule_update'], $array['rule_delete']);

        return implode('___', $array);
    }


    /**
     * Прямая или обратная связь?
     *
     * Примеры,
     *
     * Связь product -> product_info (OneToOne - product.id = product_info.product_id)
     *    для product это обратная связь, для product_info прямая
     *
     * Связь product -> product_stat (OneToMany - product.id = product_stat.product_id)
     *    для product это обратная связь, для product_info прямая
     *
     * Связь product -> tag через product_tag_link (ManyToMany)
     *    для product это прямая свзяь, для tag обратная
     */
    public function isDirect()
    {
        if ($this->getLinkTable()) {
            $localColumn = preg_replacE('#(^|_)id$#', '', $this->getLinkTableLocalColumn());

            if (strpos($this->getLinkTable()->getName(), $localColumn) === 0) {
                return true;
            } else {
                return false;
            }
        } else {
            switch ($this->getLinkType()) {
                case self::LINK_TYPE_ONE_TO_ONE:
                    return ($this->getLocalColumn()->getName() != 'id');
                    break;
                case self::LINK_TYPE_ONE_TO_MANY:
                    return false;
                    break;
                case self::LINK_TYPE_MANY_TO_ONE:
                    return true;
                    break;
            }
        }
    }

    /**
     * Получить обратную связь
     *
     * @return AbstractLink
     */
    public function inverse()
    {
        $linkType = $this->getLinkType();
        $tmp = explode('\\',  $linkType);
        $params = explode('To', end($tmp));

        $class = $params[1] . 'To' . $params[0];
        $newType = 'Model\\Schema\\Table\Link\\' . $class;
        $_newType = '\\' . $newType;

        $nameParams = explode('___', $this->getName());
        $newName = $class . '___' . $nameParams[2] . '___' . $nameParams[1];


        return new $_newType($newName,
                       $this->getForeignColumn(),
                       $this->getLocalColumn(),
                       null,
                       null,
                       $this->getLinkTableForeignColumn(),
                       $this->getLinkTableLocalColumn());
    }
}
