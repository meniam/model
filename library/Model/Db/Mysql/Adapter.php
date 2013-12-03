<?php

namespace Model\Db\Mysql;

use Model\Cond as Cond;
use Model\AbstractModel;

class Adapter extends AbstractModel
{
    /**
     * @var \Model\Db\Mysql\Driver
     */
    private static $db;

    /**
     * Добавление сущности
     *
     * @param $data
     */
    public function add($data)
    {
        $data = $this->factoryEntity($data)->toArray();

        // Фильтруем данные
        $data = $this->filterOnAdd($data);

        // Валидируем данные
        $validator = $this->validateOnAdd($data);

        $result = new Result;

        if ($validator->isValid()) {
            try {
                $id = $this->insert($this->getName(), $data);

                if (!$id) {
                    $result->addError('Add ' . $this->getName() . ' failed', 'add_product_failed');
                }
            } catch (Exception $e) {
                $result->addError($e->getMessage(), $e->getCode());
            }
        }

        return $result->setResult($id);
    }

    /**
     * @param $data
     * @return \Model\Entity
     */
    public function factoryEntity($data)
    {
        $entityClass = 'Model\\' . $this->getName() . 'Entity';

        return new $entityClass($data);
    }

    /**
     * Установить Db Adapter
     *
     * @param \Model\Db\Mysql\Driver $db
     */
    public static function setDb(\Model\Db\Mysql\Driver $db)
    {
        self::$db = $db;
    }

    /**
     *
     * @return \Model\Db\Mysql\Driver
     */
    public function getDb()
    {
        return self::$db;
    }
}