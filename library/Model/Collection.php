<?php

namespace Model;

/**
 * Абстрактный класс списка
 *
 * @category   Model
 * @package    Model_List
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Collection extends \ArrayIterator implements ListInterface
{
    /**
     * Получить только данные из _data
     * без связанных сущностей и прочих плюшек
     */
    const ARRAY_PLAIN = false;

    /**
     * Поулчить только данные из _data
     * с релевантными данными
     */
    const ARRAY_DEEP  = true;

    /**
     * Получить расширенный вид без related
     */
    const ARRAY_EXTENDED = 2;

    /**
     * Получить - все что можно выдернуть
     */
    const ARRAY_EXTENDED_DEEP = 3;

    /**
     * Pager
     *
     * @var Paginator\Adapter\AdapterInterface
     */
    protected $_pager;

    /**
     * @var null|int|string
     */
    protected $_defaultEntityType = null;

    /**
     * @var null|int|string
     */
    protected $_disableConstructInit = false;

    public function __construct($data = null, $entityType = null)
    {
        if (!$this->_disableConstructInit) {
            $this->_setupDefaultEntityType();

            $array = !empty($data) ? $this->loadFromArray($data, $entityType) : array();

            parent::__construct($array);
        } else {
            parent::__construct($data);
        }

        $this->_init($data, $entityType);
    }

    protected function _init($data = null)
    { }

    /**
    * Override this method to setup default entity type
    */
    protected function _setupDefaultEntityType()
    {
        $this->_defaultEntityType = 'Model\Entity';
    }


    /**
     * Получить объект пейджера
     *
     * @return Zend_Paginator
     */
    public function getPager()
    {
        if ($this->_pager !== null) {
            return $this->_pager;
        } else {
            return Zend_Paginator::factory(0);
        }
    }

    /**
     * Установить объект пейджера
     *
     * @param Zend_Paginator $pager
     * @throws Exception\ErrorException
     * @return Model_Collection_Abstract
     */
    public function setPager($pager)
    {
        if ($this->_pager !== null) {
            throw new \Model\Exception\ErrorException('Cant rewrite pager in collection');
        }

        $this->_pager = $pager;
        return $this;
    }

    public function loadFromArray($data, $entityType = null)
    {
        $array = array();

        if (is_array($data)) {
            foreach ($data as $item) {
                if (is_array($item) && $entityType) {
                    $array[] = new $entityType($item);
                } else if ($item instanceof Model_Entity_Interface) {
                    $array[] = $item;
                } else if (is_array($item) && $this->_defaultEntityType) {
                    $array[] = new $this->_defaultEntityType($item);
                }  else if (is_array($item) && !$this->_defaultEntityType) {
                    $array[] = new Entity($item);
                } else {
                    throw new \Model\Exception\ErrorException('Cant create entity object');
                }
            }
        } elseif ($data instanceof Model_Collection_Abstract) {
            $array = $data;
        } elseif ($data instanceof Model_Entity_Interface) {
            $array = array($data);
        } elseif (!empty($data)) {
            throw new \Model\Exception\ErrorException('Cant - init collection');
        }

        return $array;
    }
    //

    public function slice($offset = null, $length = null)
    {
        $class = get_class($this);
        return new $class(array_slice($this->getArrayCopy(), $offset, $length));
    }

    public function isEmpty()
    {
        return $this->count() == 0;
    }

    public function exists()
    {
        return !$this->isEmpty();
    }

    public function toArray($type = self::ARRAY_PLAIN)
    {
        $items = array();
        $result = array();

        foreach ($this as $current) {
            if ($type === self::ARRAY_EXTENDED
                || $type === self::ARRAY_EXTENDED_DEEP
            ) {
               if ($type === self::ARRAY_EXTENDED_DEEP) {
                   $items[] = $current->toArray(self::ARRAY_DEEP);
               } else {
                   $items[] = $current->toArray(self::ARRAY_PLAIN);
               }
            } else {
               $items[] = $current->toArray($type);
            }
        }

        if ($type === self::ARRAY_EXTENDED
            || $type === self::ARRAY_EXTENDED_DEEP
        ) {
            $result['count'] = $this->count();
            $result['collection_type'] = get_class($this);
            $result['entity_type'] = $this->_defaultEntityType;
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
     * Получить идентификаторы в массиве
     * @return array
     */
    public function getIdsAsArray()
    {
        $result = array();

        $this->rewind();
        while($this->valid()) {
           $result[] = $this->current()->getId();
           $this->next();
        }

        return $result;
    }

    /**
     * Получить идентификаторы строкой с разделителями comma
     * @return array
     */
    public function getIdsAsSrting()
    {
        return implode(',', $this->getIdsAsArray());
    }

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
     * @return Entity
     */
    public function last()
    {
    	$lastIndex = $this->count() - 1;
        if ($this->offsetExists($lastIndex)) {
        	return $this->offsetGet($lastIndex);
    	} else {
            return new $this->_defaultEntityType();
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
     * @return Model_Entity_Abstract
     */
    public function first()
    {
        if ($this->offsetExists(0)) {
        	return $this->offsetGet(0);
        } else {
    		return new $this->_defaultEntityType();
    	}
    }

    /**
    * Найти в коллекции элемент, удовлетворяющий условию
    *
    * @param midex $callback Функция, принимающая элемент коллекции
    * @return mixed|\Model\Entity
    */
    public function find($callback)
    {
        foreach ($this as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return new $this->_defaultEntityType;
    }

    /**
    * Найти в коллекции элементы, удовлетворяющий условию
    *
    * @param mixed $callback Функция, принимающая элемент коллекции
    * @return  mixed|\Model\Entity
    */
    public function findAll($callback)
    {
        $result = array();
        foreach ($this as $item) {
            if ($callback($item)) {
                $result[] = $item;
            }
        }

        $class = get_class($this);
        return new $class($result);
    }

    /**
    * Проверить, есть ли в коллекции элемент, удовлетворяющий условию
    *
    * @param function $callback Функция, принимающая элемент коллекции
    * @return bool
    */
    public function contains($callback)
    {
        foreach ($this as $item) {
            if ($callback($item)) {
                return true;
            }
        }
        return false;
    }

    /**
    * Проверить, есть ли в коллекции элемент с совпадающим уникальным ключем/ключами
    *
    * @param Collection $entity
    * @return bool
    */
    public function containsEntity(Collection $entity)
    {
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
        foreach ($this as $item) {
            if ($item->getId() == $id) {
                return true;
            }
        }
        return false;
    }

	/**
	 *
	 * @param int $id
	 */
	public function getEntityById($id, $entityType = 'Model\Entity')
	{
		$id = intval($id);

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
    * @param Collection $collection
    * @return Collection
    */
    public function diff(Collection $collection)
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
     * @param Collection $collection Добавляемая коллекция
     * @param bool $checkContains Проверять, что элемент уже в коллекции
     * @return Collection
     */
    public function merge(Collection $collection, $checkContains = false)
    {
        $className = get_class($this);
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
}