<?php

namespace Model\Cluster\Schema\Table\Link;
use Model\Cluster\Schema\Table as Table;
use Model\Cluster\Schema\Table\Column as Column;
use Model\Exception\ErrorException;
use Model\Cluster\Schema\Table\Link\OneToOne;
use Model\Cluster\Schema\Table\Link\OneToMany;
use Model\Cluster\Schema\Table\Link\ManyToOne;
use Model\Cluster\Schema\Table\Link\ManyToMany;


/**
 * Абстрактный класс связи
 *
 * @category   Schema
 * @package    Table\Link
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
abstract class AbstractLink extends \ArrayIterator
{
    const LINK_TYPE_MANY_TO_MANY     = 'Model\Cluster\Schema\Table\Link\ManyToMany';
    const LINK_TYPE_ONE_TO_ONE       = 'Model\Cluster\Schema\Table\Link\OneToOne';
    const LINK_TYPE_ONE_TO_MANY      = 'Model\Cluster\Schema\Table\Link\OneToMany';
    const LINK_TYPE_MANY_TO_ONE      = 'Model\Cluster\Schema\Table\Link\ManyToOne';

    const RULE_NO_ACTION  = 'NO ACTION';
    const RULE_SET_NULL   = 'SET NULL';
    const RULE_CASCADE    = 'CASCADE';
    const RULE_RESTRICT   = 'RESTRICT';

    /**
     * Имя (генерится автоматически)
     *
     * @var string
     */
    private $name;

    public static function factory(Column $localColumn, Column $foreignColumn, $ruleDelete = AbstractLink::RULE_NO_ACTION, $ruleUpdate = AbstractLink::RULE_CASCADE, Column $linkTableLocalColumn = null, Column $linkTableForeignColumn = null)
    {
        $localTable   = $localColumn->getTable();
        $foreignTable = $foreignColumn->getTable();
        $schema = $localColumn->getTable()->getSchema();

        $link = null;

        // Если локальный связь прямая и локальный с внешним полем уникальты - то OneToOne
        if ($localColumn->isUnique()
            && $foreignColumn->isUnique()
            && !$foreignTable->isLinkTable() && !$localTable->isLinkTable())
        {
            $link = new OneToOne($localColumn, $foreignColumn, $ruleDelete, $ruleUpdate);
        } elseif ($localColumn->isUnique()
            && !$foreignColumn->isUnique()
            && !$foreignTable->isLinkTable() && !$localTable->isLinkTable())
        {  // OneToMany Direct
            $link = new OneToMany($localColumn, $foreignColumn, $ruleDelete, $ruleUpdate);
        } elseif (!$localColumn->isUnique()
            && $foreignColumn->isUnique()
            && !$foreignTable->isLinkTable() && !$localTable->isLinkTable())
        { // ManyToOne Direct
            $link = new ManyToOne($localColumn, $foreignColumn, $ruleDelete, $ruleUpdate);
        } elseif ($localTable->isLinkTable() || $foreignTable->isLinkTable()) {
            $linkTable  = $localTable->isLinkTable() ? $localTable : $foreignTable;

            if ($localTable->isLinkTable()) {
                $localTable = $foreignTable;
                $localColumn = $foreignColumn;
            }

            /**
             * Тут мы выясняем сколько внешних ключей у таблицы связки
             * Если их больше двух или меньше двух, то значит это хреновая связь
             *
             * Потом мы выясняем внешнюю таблицку и создаем связь
             */
            $linkTableForeignKeys = $schema->getForeignKeyArray($linkTable);

            $linkTableColumnList    = $linkTable->getColumn();
            $linkTableLocalColumn   = null;
            $linkTableForeignColumn = null;
            $foreignColumn          = null;

            /** @var $linkTableColumn Column */
            foreach ($linkTableColumnList as $linkTableColumn) {
                // НЕ ID поле
                if (substr($linkTableColumn->getName(), -3) != '_id') {
                    continue;
                }

                $tableName = substr($linkTableColumn->getName(), 0, -3);
                $matchedTable = $schema->getTable($tableName);

                if ($matchedTable) {
                    if ($matchedTable->getName() == $localTable->getName()) {
                        $linkTableLocalColumn = $linkTableColumn;
                    } else {
                        $foreignColumn = $matchedTable->getColumn('id');
                        $linkTableForeignColumn = $linkTableColumn;
                    }
                }
            }

            if (!empty($linkTableForeignKeys)) {
                foreach ($linkTableForeignKeys as $linkTableForeignKey) {
                    if ($linkTableForeignKey['table_name'] == $localTable->getName() || $linkTableForeignKey['referenced_table_name'] == $localTable->getName()) {
                        $linkTableLocalColumnName = ($linkTableForeignKey['table_name'] == $localTable->getName()) ? $linkTableForeignKey['referenced_column_name'] : $linkTableForeignKey['column_name'];
                        $linkTableLocalColumn = $schema->getTable($linkTable->getName())->getColumn($linkTableLocalColumnName);
                        continue;
                    } else {
                        // Пытаемся найти внешнее поле foreignColumn
                        if ($linkTableForeignKey['table_name'] == $linkTable->getName()) {
                            $foreignTableName = $linkTableForeignKey['referenced_table_name'];
                            $foreignColumnName = $linkTableForeignKey['referenced_column_name'];
                            $linkTableForeignColumnName = $linkTableForeignKey['column_name'];
                        } else {
                            $foreignTableName = $linkTableForeignKey['table_name'];
                            $foreignColumnName = $linkTableForeignKey['column_name'];
                            $linkTableForeignColumnName = $linkTableForeignKey['referenced_column_name'];
                        }

                        $linkTableForeignColumn = $schema->getTable($linkTable->getName())->getColumn($linkTableForeignColumnName);
                        $foreignColumn = $schema->getTable($foreignTableName)->getColumn($foreignColumnName);
                    }
                }
            }

            if ($foreignColumn && $linkTableLocalColumn && $linkTableForeignColumn) {
                /** @var $indexList \Model\Cluster\Schema\Table\Index\AbstractIndex[] */
//                $indexList = $linkTableLocalColumn->getTable()->getIndex();

                $linkType = '\Model\Cluster\Schema\Table\Link\ManyToMany';
                if ($linkTableLocalColumn->isUnique() && $linkTableForeignColumn->isUnique()) {
                    $linkType = '\Model\Cluster\Schema\Table\Link\OneToOne';
                } elseif ($linkTableLocalColumn->isUnique()) {
                    $linkType = '\Model\Cluster\Schema\Table\Link\OneToMany';
                } elseif ($linkTableForeignColumn->isUnique()) {
                    $linkType = '\Model\Cluster\Schema\Table\Link\ManyToOne';
                }

                $link = new $linkType($localColumn, $foreignColumn, $ruleDelete, $ruleUpdate, $linkTableLocalColumn, $linkTableForeignColumn);
            }
        }

        return $link;
    }

    /**
     * @param \Model\Cluster\Schema\Table\Column $localColumn
     * @param \Model\Cluster\Schema\Table\Column $foreignColumn
     * @param string                             $ruleDelete
     * @param string                             $ruleUpdate
     * @param \Model\Cluster\Schema\Table\Column $linkTableLocalColumn
     * @param \Model\Cluster\Schema\Table\Column $linkTableForeignColumn
     */
    public function __construct(Column $localColumn, Column $foreignColumn, $ruleDelete = AbstractLink::RULE_NO_ACTION, $ruleUpdate = AbstractLink::RULE_CASCADE, Column $linkTableLocalColumn = null, Column $linkTableForeignColumn = null)
    {
        $data['rule_update'] = $ruleUpdate;
        $data['rule_delete'] = $ruleDelete;
        
        $data['local_column'] = $localColumn;
        $data['foreign_column'] = $foreignColumn;

        $localTable = $localColumn->getTable();
        $data['local_table'] = $localTable;

        $foreignTable = $foreignColumn->getTable();
        $data['foreign_table'] = $foreignTable;

        if ($linkTableLocalColumn && $linkTableForeignColumn) {
            $data['link_table_local_column'] = $linkTableLocalColumn;
            $data['link_table_foreign_column'] = $linkTableForeignColumn;
            $data['link_table'] = $linkTableLocalColumn->getTable();

            if ($linkTableLocalColumn->getName() == 'id') {
                $data['local_entity_alias'] = $localTable->getName();
            } else {
                $data['local_entity_alias'] =  str_replace('_id', '', $linkTableLocalColumn->getName());
            }

            if ($linkTableForeignColumn->getName() == 'id') {
                $data['foreign_entity_alias'] = $foreignTable->getName();
            } else {
                $data['foreign_entity_alias'] = str_replace('_id', '', $linkTableForeignColumn->getName());
            }
        } else {
            if ($localColumn->getName() == 'id' && $foreignColumn->getName() == 'id') {
                $data['local_entity_alias'] = $localTable->getName();
                $data['foreign_entity_alias'] = $foreignTable->getName();
            } elseif ($localColumn->getName() == 'id') {
                $data['foreign_entity_alias'] = $foreignTable->getName();
                $data['local_entity_alias'] =  str_replace('_id', '', $foreignColumn->getName());
            } else {
                $data['foreign_entity_alias'] = str_replace('_id', '', $localColumn->getName());
                $data['local_entity_alias'] =  $localTable->getName();
            }
        }

        parent::__construct($data);
        $this->setupLinkType();
        $this->setName();
    }

    /**
     * Создать экземпляр из XMl
     *
     * @param                             $xml
     * @param \Model\Cluster\Schema\Table $table
     * @throws \Model\Exception\ErrorException
     * @return AbstractLink
     */
    public static function fromXml($xml, Table $table)
    {
        if (is_array($xml)) {
            $data = $xml;
        } else {
            $xml = simplexml_load_string($xml);
            $data = json_decode(json_encode((array) $xml), 1);
        }
        $data = Column::prepareXmlArray($data);


        switch (strtolower($data['type'])) {
            case 'onetoone':
                $type = self::LINK_TYPE_ONE_TO_ONE;
                    break;
            case 'onetomany':
                $type = self::LINK_TYPE_ONE_TO_MANY;
                    break;
            case 'manytoone':
                $type = self::LINK_TYPE_MANY_TO_ONE;
                    break;
            case 'manytomany':
                $type = self::LINK_TYPE_MANY_TO_MANY;
                    break;
        }
        $type  = '\\' . $type;

        $schema = $table->getSchema();

        $localColumn = $schema->getTable($data['local_table'])->getColumn($data['local_column']);
        $foreignColumn = $schema->getTable($data['foreign_table'])->getColumn($data['foreign_column']);

        if (!$localColumn) {
            throw new ErrorException('Local column not found');
        }

        if (!$foreignColumn) {
            throw new ErrorException('Foreign column not found');
        }

        if (isset($data['rule_update'])) {
            if (empty($data['rule_update'])) {
                $data['rule_update'] = null;
            }
        }

        if (isset($data['rule_delete'])) {
            if (empty($data['rule_delete'])) {
                $data['rule_delete'] = null;
            }
        }

        if (isset($data['link_table'])) {
            $linkTableLocalColumn = $schema->getTable($data['link_table'])->getColumn($data['link_table_local_column']);
            $linkTableForeignColumn = $schema->getTable($data['link_table'])->getColumn($data['link_table_foreign_column']);

            if (!$linkTableLocalColumn) {
                throw new ErrorException('Link table Local column not found');
            }

            if (!$linkTableForeignColumn) {
                throw new ErrorException('Link table Foreign column not found');
            }

            $link = new $type($localColumn, $foreignColumn, $data['rule_delete'], $data['rule_update'], $linkTableLocalColumn, $linkTableForeignColumn);
        } else {
            $link = new $type($localColumn, $foreignColumn, $data['rule_delete'], $data['rule_update']);
        }

        return $link;
    }

    /**
     * Установить имя
     */
    final private function setName()
    {
        $linkType = $this->getLinkType();
        $linkTypeArray = explode('\\', $linkType);
        $linkType = end($linkTypeArray);

        $this->name = $linkType . '___' . implode('', array_map('ucfirst', explode('_', $this->getLocalEntityAlias()))) . '___' . implode('', array_map('ucfirst', explode('_', $this->getForeignEntityAlias())));
    }

    /**
     * Получить имя связи
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Получить имя локальной Enitity
     *
     * @return string
     */
    public function getLocalEntity()
    {
        if ($this->getLinkTableLocalColumn()) {
            $_entity = \preg_replace('#_id$#', '', $this->getLinkTableLocalColumn()->getName());
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
    public function getLocalEntityAlias()
    {
        return ($this->offsetExists('local_entity_alias')) ? $this->offsetGet('local_entity_alias') : null;
    }

    /**
     * @return string
     */
    public function getForeignEntity()
    {
        if ($this->getLinkTableForeignColumn()) {
            $_entity = \preg_replace('#_id$#', '', $this->getLinkTableForeignColumn()->getName());
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
     * @return string
     */
    public function getForeignEntityAlias()
    {
        return ($this->offsetExists('foreign_entity_alias')) ? $this->offsetGet('foreign_entity_alias') : null;
    }


    /**
     * Определить тип связи
     *
     * @return AbstractLink
     */
    protected function setupLinkType()
    {
        $this['link_type'] = get_class($this);
        return $this;
    }
    
    /**
     *
     * @return string
     */
    public function getLinkType()
    {
        return ($this->offsetExists('link_type')) ? $this->offsetGet('link_type') : null;
    }

    /**
     * @return \Model\Cluster\Schema\Table
     */
    public function getLinkTable()
    {
        return ($this->offsetExists('link_table')) ? $this->offsetGet('link_table') : null;
    }

    /**
     *
     * @return Table
     */
    public function getLocalTable()
    {
        return ($this->offsetExists('local_table')) ? $this->offsetGet('local_table') : null;
    }

    /**
     *
     * @return Column
     */
    public function getLocalColumn()
    {
        return ($this->offsetExists('local_column')) ? $this->offsetGet('local_column') : null;
    }

    /**
     *
     * @return Table
     */
    public function getForeignTable()
    {
        return ($this->offsetExists('foreign_table')) ? $this->offsetGet('foreign_table') : null;
    }

    /**
     * Правило изменения
     *
     * @return mixed
     */
    public function getRuleUpdate()
    {
        return ($this->offsetExists('rule_update')) ? $this->offsetGet('rule_update') : null;
    }

    /**
     * Правило удаления
     *
     * @return mixed
     */
    public function getRuleDelete()
    {
        return ($this->offsetExists('rule_delete')) ? $this->offsetGet('rule_delete') : null;
    }
    
    /**
     *
     * @return Column
     */
    public function getForeignColumn()
    {
        return ($this->offsetExists('foreign_column')) ? $this->offsetGet('foreign_column') : null;
    }

    /**
     * Получить локальную колонку в таблице связки
     *
     * @return \Model\Cluster\Schema\Table\Column|null
     */
    public function getLinkTableLocalColumn()
    {
        return ($this->offsetExists('link_table_local_column')) ? $this->offsetGet('link_table_local_column') : null;
    }

    /**
     * Получить внешнюю колонку в таблице связки
     *
     * @return \Model\Cluster\Schema\Table\Column|null
     */
    public function getLinkTableForeignColumn()
    {
        return ($this->offsetExists('link_table_foreign_column')) ? $this->offsetGet('link_table_foreign_column') : null;
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
                        'local_entity_alias' => $this->getLocalEntityAlias(),
                        'local_table' => $this->getLocalTable()->getName(),
                        'local_column' => $this->getLocalColumn()->getName(),
                        'foreign_entity_alias' => $this->getForeignEntityAlias(),
                        'foreign_table' => $this->getForeignTable()->getName(),
                        'foreign_column' => $this->getForeignColumn()->getName(),
                        'rule_update' => $this->getRuleUpdate(),
                        'rule_delete' => $this->getRuleDelete());

        if ($this->getLinkTableLocalColumn() && $this->getLinkTableForeignColumn()) {
            $result['link_table']                = $this->getLinkTable()->getName();
            $result['link_table_local_column']   = $this->getLinkTableLocalColumn()->getName();
            $result['link_table_foreign_column'] = $this->getLinkTableForeignColumn()->getName();
        }

        $result['is_direct'] = $this->isDirect();

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
            $localColumn = preg_replace('#(^|_)id$#', '', $this->getLinkTableLocalColumn()->getName());

            if (strpos($this->getLinkTable()->getName(), $localColumn) === 0) {
                return true;
            } elseif ($this->getLocalEntity() != $this->getLocalTable()->getName() && ($this->getForeignEntity() == $this->getForeignTable()->getName())) {
                return true;
            } else {
                return false;
            }
        } else {
            switch ($this->getLinkType()) {
                case self::LINK_TYPE_ONE_TO_ONE:
                    if ($this->getLocalColumn()->getName() == 'id') {
                        if ($this->getLocalColumn()->isAutoincrement() && !$this->getForeignColumn()->isAutoincrement()) {
                            return false;
                        } elseif (!$this->getLocalColumn()->isAutoincrement() && $this->getForeignColumn()->isAutoincrement()) {
                            return true;
                        }
                    }

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

        return true;
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
        $newType = 'Model\\Cluster\\Schema\\Table\Link\\' . $class;
        $_newType = '\\' . $newType;



        return new $_newType($this->getForeignColumn(),
                       $this->getLocalColumn(),
                       null,
                       null,
                       $this->getLinkTableForeignColumn(),
                       $this->getLinkTableLocalColumn());
    }

    /**
     * Выгрузить в XML
     *
     * @param bool $withHeader
     * @param int  $tabStep
     * @return string
     */
    public function toXml($withHeader = true, $tabStep = 6)
    {
        $tab = '    ';
        $shift = str_repeat($tab, $tabStep);

        $xml = $withHeader ? \Model\Cluster::XML_HEADER : '';

        $xml .= $shift . '<link name="' . $this->getName() . '">' . "\n";

        $linkParams = $this->toArray();

        $typeParts = explode('\\', $linkParams['type']);
        $type = end($typeParts);

        $linkParams['type'] = $type;

        foreach ($linkParams as $k => $v) {
            $xml .= $shift . $tab . '<' . $k . '>' . $v . '</' . $k . '>' . "\n";
        }

        $xml .= $shift . '</link>' . "\n";
        return $xml;
    }

}
