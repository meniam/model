<?php

namespace Model\Collection;

use Model\Collection\Exception\ErrorException;
use Model\Entity\AbstractEntity;
use Model\Entity\EntityInterface;
use \Model\Paginator\Paginator;

/**
 * Абстрактный класс набора
 *
 * @category   Model
 * @package    Collection
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      26.12.12 10:23
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class AbstractCollection extends \ArrayIterator
{
    /**
     * Pager
     *
     * @var Paginator
     */
    protected $pager;

    /**
     * Какая Entity для этой коллекции считается по-умолчанию
     *
     * @var null
     */
    protected $defaultEntityType = null;

    /**
     * Запретить инициализацию в конструкторе
     *
     * @var bool
     */
    protected $_disableConstructInit = false;

    /**
     * @param null $data
     * @param null $entityType
     *
     * @throws Exception\ErrorException
     */
    public function __construct($data = null, $entityType = null)
    {
        if (!$this->_disableConstructInit) {
            $this->setupDefaultEntityType();

            if (!empty($data)) {
                $array = $this->loadFromArray($data, $entityType);
            } else {
                $array = array();
            }

            parent::__construct($array);
        } else {
            parent::__construct($data);
        }
    }

    /**
     * Инициализация
     *
     * @param null $data
     */
    protected function init($data = null)
    { }

    /**
     * Какую Entity будем использовать по-умолчанию
     */
    protected function setupDefaultEntityType()
    { }

    /**
     * Получить объект пейджера
     *
     * @return Paginator
     */
    public function getPager()
    {
        if ($this->pager !== null) {
            return $this->pager;
        } else {
            return new Paginator();
        }
    }

    /**
     * Установить объект пейджера
     *
     * @param Paginator|null $pager
     * @throws \Model\Paginator\Exception\ErrorException
     * @return \Model\Collection\AbstractCollection
     */
    public function setPager(Paginator $pager = null)
    {
        $this->pager = $pager;
        return $this;
    }

    /**
     * Загрузить данные с массива
     *
     * @param      $data
     * @param null $entityType
     * @throws Exception\ErrorException
     * @return array|\Model\Collection\AbstractCollection
     */
    public function loadFromArray($data, $entityType = null)
    {
        $array = array();

        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item) && $entityType) {
                    $array[] = new $entityType($item);
                } else if ($item instanceof EntityInterface) {
                    $array[] = $item;
                } else if (is_array($item) && $this->defaultEntityType) {
                    $array[] = new $this->defaultEntityType($item);
                }  else if (is_array($item) && !$this->defaultEntityType) {
                    throw new ErrorException('Unknown entity type please set dataType');
                    //$array[] = new Model_Entity($item);
                } else {
                    throw new ErrorException('Cant create entity object');
                }
            }
        } elseif ($data instanceof AbstractCollection) {
            $array = $data;
        } elseif ($data instanceof EntityInterface) {
            $array = array($data);
        } elseif (!empty($data)) {
            throw new ErrorException('Cant - init collection');
        }

        return $array;
    }

    /**
     * Обрезка коллекции
     *
     * @param null $offset
     * @param null $length
     * @return mixed
     */
    public function slice($offset = null, $length = null)
    {
        $class = get_class($this);
        return new $class(array_slice($this->getArrayCopy(), $offset, $length));
    }

    /**
     * Пустой?
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() == 0;
    }

    /**
     * Данные в массиве есть?
     *
     * @return bool
     */
    public function exists()
    {
        return !$this->isEmpty();
    }

    /**
     * Выгрузить в массив
     *
     * @param bool $extended
     *
     * @internal param bool $type
     * @return array const \Model\Collection\AbstractCollection::ARRAY_
     */
    public function toArray($extended = false)
    {
        $items = array();
        $result = array();

        /** @var AbstractEntity[]|AbstractCollection $this */
        foreach ($this as &$current) {
            $items[] = $current->toArray();
        }

        if ($extended) {
            $result['count'] = $this->count();
            $result['collection_type'] = get_class($this);
            $result['entity_type'] = $this->defaultEntityType;
            $result['items'] = $items;

            if ($this->getPager()->count()) {
                $result['pager'] = array('page' => $this->getPager()->getCurrentPageNumber(),
                                         'page_count' => $this->getPager()->count(),
                                         'item_count' => $this->getPager()->getTotalItemCount(),
                                         'current_item_count' => $this->getPager()->getCurrentItemCount(),
                                         'item_count_per_page' => $this->getPager()->getItemCountPerPage());
            } else {
                $result['pager'] = array();
            }
        } else {
            $result = $items;
        }

        return $result;
    }

    /**
     * @param $field
     * @return array
     */
    public function getFieldsAsArray($field)
    {
        $result = array();

        $method = 'get' . implode('', array_map('ucfirst', explode('_', $field)));

        $this->rewind();
        while($this->valid()) {
            $result[] = $this->current()->$method();
            $this->next();
        }

        return $result;
    }

    /**
     * @param        $field
     * @param string $delimiter
     * @return array
     */
    public function getFieldsAsString($field, $delimiter = ',')
    {
        return implode($delimiter, $this->getFieldsAsArray($field));
    }

    /**
     * Получить идентификаторы в массиве
     * @return array
     */
    public function getIdsAsArray()
    {
        return $this->getFieldsAsArray('id');
    }

    /**
     * Получить идентификаторы строкой с разделителями comma
     *
     * @param string $delimiter
     * @return array
     */
    public function getIdsAsSrting($delimiter = ',')
    {
        return $this->getFieldsAsString('id', $delimiter);
    }

    /**
     * Проверить достигнут ли конца итератор
     *
     * @return bool
     */
    public function isLast()
    {
        if ($this->count()) {
            return $this->key() == $this->count() - 1;
        } else {
            return true;
        }
    }

    /**
     * Получить последний элемент
     *
     * @return \Model\Entity\AbstractEntity
     */
    public function last()
    {
        $lastIndex = $this->count() - 1;
        if ($this->offsetExists((int)$lastIndex)) {
            return $this->offsetGet($lastIndex);
        } else {
            return new $this->defaultEntityType();
        }
    }

    /**
     * Является ли первым элементом
     *
     * @return boolean
     */
    public function isFirst()
    {
        return $this->key() == 0;
    }

    /**
     * Получить первый элемент
     *
     * @return \Model\Entity\AbstractEntity
     */
    public function first()
    {
        if ($this->offsetExists(0)) {
            return $this->offsetGet(0);
        } else {
            return new $this->defaultEntityType();
        }
    }

    /**
     * Строит из обычного списка вложенный массив по parent_id технологии
     *
     * @param mixed $items
     * @return array
     */
    protected function _prepareNestedTreeArray($items)
    {
        if ($items instanceof AbstractCollection) {
            $items = $items->toArray(true);
        }

        if (empty($items)) {
            return array();
        }

        $registry = array();
        $result   = array();

        foreach ($items as &$node) {
            $node['level'] = 1;
            $registry[$node['id']] = &$node;
        }
        foreach ($registry as &$node) {
            $parentId = $node['parent_id'];
            if (isset($registry[$parentId])) {
                $node['level'] = $registry[$parentId]['level'] + 1;
                $registry[$parentId]['_child_list'][] = &$node;
            }
            if ($node['level'] == 1) {
                $result[] = &$node;
            }
        }

        return $result;
    }

    /**
     * Строит из обычного списка плоский массив по parent_id технологии
     * (не стал придумывать как посторить плоское дерево проще)
     *
     * @param mixed $items
     * @return array
     */
    protected function _preparePlainTreeArray($items)
    {
        $result = array();
        $itemsTree = $this->_prepareNestedTreeArray($items);

        foreach ($itemsTree as $item) {
            if (isset($item['_child_list']) && is_array($item['_child_list'])) {
                $tmp = $item['_child_list'];
                unset($item['_child_list']);
                $result[] = $item;

                $this->_plain($tmp, $result);
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param $itemsTree
     * @param $result
     */
    protected function _plain($itemsTree, &$result)
    {
        foreach ($itemsTree as $item) {
            if (array_key_exists('_child_list' ,$item) && is_array($item['_child_list'])) {
                $tmp = $item['_child_list'];
                unset($item['_child_list']);
                $result[] = $item;

                $this->_plain($tmp, $result);
            } else {
                $result[] = $item;
            }
        }
    }

    /**
     * Найти в коллекции элемент, удовлетворяющий условию
     *
     * @param \closure $callback Функция, принимающая элемент коллекции
     * @return \Model\Entity\AbstractEntity
     */
    public function find($callback)
    {
        foreach ($this as $item) {
            if (is_callable($callback) && $callback($item)) {
                return $item;
            }
        }
        return new $this->defaultEntityType;
    }

    /**
     * Найти в коллекции элементы, удовлетворяющий условию
     *
     * @param \closure $callback Функция, принимающая элемент коллекции
     * @return \Model\Collection\AbstractCollection
     */
    public function findAll($callback)
    {
        $result = array();
        foreach ($this as $item) {
            if (is_callable($callback) && $callback($item)) {
                $result[] = $item;
            }
        }
        $class = get_class($this);

        return new $class($result);
    }

    /**
     * Проверить, есть ли в коллекции элемент, удовлетворяющий условию
     *
     * @param \closure $callback Функция, принимающая элемент коллекции
     * @return bool
     */
    public function contains($callback)
    {
        foreach ($this as $item) {
            if (is_callable($callback) && $callback($item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверить, есть ли в коллекции элемент с совпадающим уникальным ключем/ключами
     *
     * @param EntityInterface $entity
     * @return bool
     */
    public function containsEntity(EntityInterface $entity)
    {
        /** @var AbstractEntity[]|AbstractCollection $this */
        foreach ($this as $item) {
            if ($entity->equals($item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Проверить, есть ли в коллекции элемент с совпадающим id
     *
     * @param mixed $id
     * @return bool
     */
    public function containsId($id)
    {
        /** @var AbstractEntity[] $this*/
        foreach ($this as $item) {
            if ($item->getId() == $id) {
                return true;
            }
        }

        return false;
    }

    /**
     *
     * @param int    $id
     * @param string $entityType
     * @internal \Model\Entity\AbstractEntity $entity
     * @return \Model\Entity\AbstractEntity
     */
    public function getEntityById($id, $entityType)
    {
        $id = intval($id);
        /** @var $item \Model\Entity\AbstractEntity */
        foreach ($this as $item) {
            if ($item->getId() == $id) {
                return $item;
            }
        }

        return new $entityType();
    }

    /**
     * Вычитание коллекций из текущей коллекции
     * Поведение аналогично array_diff()
     *
     * @param AbstractCollection $collection
     * @return AbstractCollection
     */
    public function diff(AbstractCollection $collection)
    {
        $className = get_class($this);
        $collectionNew = new $className();
        foreach ($this as $entity) {
            if (!$collection->containsEntity($entity)) {
                $collectionNew[] = $entity;
            }
        }
        return $collectionNew;
    }

    /**
     * Добавить коллекцию к текущей коллекции и вернуть новую
     *
     * @param AbstractCollection $collection Добавляемая коллекция
     * @param bool $checkContains Проверять, что элемент уже в коллекции
     * @return AbstractCollection
     */
    public function merge(AbstractCollection $collection, $checkContains = false)
    {
        $className = get_class($this);

        /** @var $collectionNew AbstractCollection */
        $collectionNew = new $className();
        foreach ($this as $entity) {
            if (!$checkContains || !$collectionNew->containsEntity($entity)) {
                $collectionNew[] = $entity;
            }
        }
        foreach ($collection as $entity) {
            if (!$checkContains || !$collectionNew->containsEntity($entity)) {
                $collectionNew[] = $entity;
            }
        }
        return $collectionNew;
    }

    /**
     * Отсортировать по столбцам
     *
     * @param int $cols Количество столбцов
     * @return \Model\Collection\AbstractCollection
     */
    public function sortAsCol($cols = 3)
    {
        $result     = array();

        $totalCount = $this->count();
        $rest       = $totalCount % $cols;
        $colCount   = ceil($totalCount / $cols);

        if ($rest != 0) {
            for ($i = 0; $i < $rest; $i++) {
                $tmp[$i] = $this->slice($i * $colCount, $colCount);
            }

            for ($i = $rest; $i < $cols; $i++) {
                $tmp[$i] = $this->slice($i * $colCount - ($i-$rest), $colCount-1);
            }
        } else {
            for ($i = 0; $i < $cols; $i++) {
                $tmp[$i] = $this->slice($i * $colCount, $colCount);
            }
        }

        for ($i = 0; $i < $colCount; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                if (isset($tmp[$j][$i])) {
                    $result[] = $tmp[$j][$i];
                }
            }
        }
        return new static($result);
    }

}