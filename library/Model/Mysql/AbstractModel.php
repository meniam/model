<?php

namespace Model\Mysql;

use Model\Collection\AbstractCollection;
use Model\Db\Mysql as DbAdapter;

use Model\Cond\AbstractCond as Cond;
use Model\Db\Select;
use Model\Db\Expr;
use Model\Entity\AbstractEntity;
use Model\Entity\EntityInterface as Entity;
use Model\Collection\AbstractCollection as Collection;
use Model\Paginator\Adapter\Mysql;
use Model\Paginator\Paginator;
use Model\Result\Result;

/**
 *
 *
 * @category   Model
 * @package    Mysql
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      14.12.12 13:21
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class AbstractModel extends \Model\AbstractModel
{
    /**
     * Индексы
     *
     * @var array
     */
    protected $indexList = array();

    /**
     * Текущее имя DB адптера из ServiceManager
     *
     * @var string
     */
    private $dbAdapterName = 'db';

    /**
     * @var DbAdapter
     */
    protected $db;

    /**
     * @param      $data
     * @param Cond $cond
     *
     * @throws Exception\ErrorException
     * @throws \Model\Exception\ErrorException
     * @return $this|Result
     */
    public function import($data, Cond $cond = null)
    {
        $tableName = $this->getRawName();

        if (is_null($cond)) {
            $cond = $this->getCond($tableName, $tableName);
        } else {
            /** @var $cond \Model\Cond\AbstractCond */
            $cond = $this->prepareCond($cond);
        }

        $result    = new Result();
        $data      = $this->prepareData($data);
        $relatedData     = array();

        if (empty($data)) {
            return $result->addError("Import {$tableName} failed; Import data is empty", "import_{$tableName}_failed");
        }

        $id = null;
        // Ищем по различным параметрам, может данные уже есть в базе

        // Ищем данные в базе по уникальным полям
        $existsCond = clone $cond;
        $id = $this->getExistedIdByUniqueIndex($data, $existsCond);

        // Идем по всем связям и ищем обязательные связи
        foreach ($this->getRelation() as $rel) {
            if (!$rel['required_link']) {
                continue;
            }

            // Каскад разрешен
            $cascadeAllowed    = (!$id || $cond->isCascadeAllowed());
            $foreignEntityName = $rel['foreign_entity'];

            $localColumnName   = $rel['local_column'];

            /** @var $foreignModel \Model\Mysql\AbstractModel */
            $foreignModel = $rel['foreign_model'];

            if (isset($data) && array_key_exists($localColumnName, $data) && !isset($data['_' . $foreignEntityName])) {
                /**
                 * Вложенная сущность сушествует
                 */
                $relatedData[$localColumnName] = $data[$localColumnName];
            } elseif ($cascadeAllowed && isset($data['_' . $foreignEntityName]))  {
                /**
                 * Здесь мы проверяем наличие вложенной сущности,
                 * и если она есть то импортируем ее и получаем
                 * добавленный идентификатор
                 */

                /** @var $foreignModelInstance \Model\Mysql\AbstractModel */
                $foreignModelInstance = $foreignModel::getInstance();

                $importChildResult = $foreignModelInstance->import($data['_' . $foreignEntityName], $cond->getChild($foreignEntityName));
                $result->addChild($foreignEntityName, $importChildResult);

                // Если есть ошибки, то выходим
                if ($importChildResult->isError() && $cond->isIgnoreErrors()) {
                    return $result;
                }

                if ($importChildResult->isValid()) {
                    $relatedData[$localColumnName] = $data[$localColumnName] = $importChildResult->getResult();
                }

                unset($data['_' . $foreignEntityName]);
            }
        }

        // $data могла поменятся ищем еще раз
        $id = $this->getExistedIdByUniqueIndex($data, $existsCond);

        // Если не нашли
        if (!$id) {
            try {
                /** @var $_result Result */
                $addResult = $this->add($data, $cond);

                // Передаем результат добавления в общий результат и берем значение
                $id = $result->setResult($addResult)->getResult();
            } catch (\PDOException $e) {
                // Если ошибка, то добавляем ошибку с кодом import_add_failed в global
                $result->addError('Import add operation failed: ' . $e->getMessage(), 'import_add_failed');
            }
        } elseif ($cond->isUpdateAllowed()) {
            try {
                // Если разрешено обновление, то обновляем данные
                $_cond = clone $cond;
                $_cond->where(array('`' . $this->getRawName() . '`.`id`' => $id));

                $updateAllowedResult = $this->update($data, $_cond);
                $result->setResult($updateAllowedResult);
                unset($_cond);
            } catch (\PDOException $e) {
                // Если ошибка, то добавляем ошибку с кодом import_update_failed в global
                $result->addError('Import update operation failed: ' . $e->getMessage(), 'import_update_failed');
            }
        }

        // relatedData - мы в первом проходе, смотрим,
        // какие связанные сущности есть
        //
        //
        if (!empty($relatedData) && $cond->isCascadeAllowed()) {
            // Если не разрешено обновление, и разрешен каскад, то обновляем только связи
            $_cond = clone $cond;
            $_cond->where(array('`' . $this->getRawName() . '`.`id`' => $id));
            $relatedDataUpdateResult  = $this->update($relatedData, $_cond);

            if (!$relatedDataUpdateResult->isValid()) {
                $result->addErrorList($relatedDataUpdateResult->getErrorList());
            }
        }
        // Установить значение результата
        $result->setResult((string) $id);

        // Если ничего не добавилось и опция игнора ошибок отключена, то выходим
        if (!$result->getResult() && !$cond->isIgnoreErrors()) {
            return $result;
        }

        $isIgnoreErrors = $cond->isIgnoreErrors();
        if (($id || $isIgnoreErrors) && $cond->isCascadeAllowed()) {
            foreach ($this->getRelation() as $key => $rel) {
                /** @var $foreignModel \Model\Mysql\AbstractModel */
                $foreignModel         = $rel['foreign_model'];

                /** @var $foreignModel \Model\Mysql\AbstractModel */
                $foreignModelInstance = $foreignModel::getInstance();
                $foreignEntityName    = $key;
                $foreignColumnName    = $rel['foreign_column'];

                if (isset($data['_' . $foreignEntityName])) {
                    $innerData = $this->prepareData($data['_' . $foreignEntityName]);

                    if (!isset($rel['link_table']) && $rel['local_column'] == 'id' && is_array($innerData) && $id) {
                        $innerData[$foreignColumnName] = $id;
                    }

                    $_result = null;
                    // Если основная сущность добавлена, а у вложенной стоит что нельзя добавлять связи,
                    // то эти связи нужно удалить
                    if ($id && !$cond->getChild($foreignEntityName)->isAppendLink()) {
                        // Удаляем связи перед обновлением
                        $unlinkMethod = $rel['unlink_method'];

                        /** @var $unlinkResult Result */
                        $unlinkResult = $this->$unlinkMethod($id);

                        if (!$unlinkResult->isValid()) {
                            $result->addChild($foreignEntityName, $unlinkResult);
                            if (!$isIgnoreErrors) {
                                return $result;
                            }
                        }
                    }

                    if ($id && $cond->getChild($foreignEntityName)->isAppendLink()) {
                        if (empty($innerData)) {
                            $result->addChild($foreignEntityName, new Result());
                        } else {
                            $importInnerDataResult = $foreignModelInstance->import($innerData, $cond->getChild($foreignEntityName));
                            $result->addChild($foreignEntityName, $importInnerDataResult);

                            // Если ошибка и пропускать их нельзя
                            if ($result->isError() && !$isIgnoreErrors) {
                                return $result;
                            }

                            // Если при импорте ошибок не было
                            // то линкуем данные с текущей сущностью
                            if (!$importInnerDataResult->isError()) {
                                $linkMethod = $rel['link_method'];
                                $this->$linkMethod(array($id), $importInnerDataResult->getResult(), $cond->getChild($foreignEntityName)->isAppendLink());
                            }
                        }
                    }
                }

                // Если связь много ко многим или один ко многим
                if (($rel['type'] = AbstractModel::MANY_TO_MANY
                    || $rel['type'] = AbstractModel::ONE_TO_MANY)
                    && isset($data['_' . $foreignEntityName . '_collection'])){

                    $innerData = $this->prepareData($data['_' . $foreignEntityName . '_collection']);

                    // Если нет таблицы связи и существую данные для обработки
                    if (!isset($rel['link_table']) && is_array($innerData) && $id && is_array(reset($innerData))) {
                        foreach ($innerData as &$item) {
                            $item[$foreignColumnName] = $id;
                        }
                    }

                    // Если основная сущность добавлена, а у вложенной стоит что нельзя добавлять связи,
                    // то эти связи нужно удалить
                    if ($id && !$cond->getChild($foreignEntityName . '_collection')->isAppendLink()) {
                        $unlinkMethod = $rel['unlink_method'];

                        /** @var $_result Result */
                        $unlinkResult = $this->$unlinkMethod($id);

                        if ($unlinkResult->isError()) {
                            $result->addChild($foreignEntityName . '_collection', $_result);
                            if (!$cond->isIgnoreErrors()) {
                                return $result;
                            }
                        }
                    }

                    if ($id && $cond->getChild($foreignEntityName . '_collection')->isAppendLink()) {
                        if (empty($data['_' . $foreignEntityName . '_collection'])) {
                            $result->addChild($foreignEntityName . '_collection', new Result());
                        } else {
                            $importCollectionResult = $foreignModelInstance->importCollection($innerData, $cond->getChild($foreignEntityName . '_collection'));
                            $result->addChild($foreignEntityName . '_collection', $importCollectionResult);

                            // Если есть ошибки и допускать их нельзя выходим
                            if ($importCollectionResult->isError() && !$isIgnoreErrors) {
                                return $result;
                            }

                            if (isset($rel['link_table']) && $importCollectionResult->isValid()) {
                                $linkMethod = $rel['link_method'];
                                $this->$linkMethod(array($id), $importCollectionResult->getResult(), $cond->isAppendLink());
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param mixed $data
     * @param Cond  $cond
     *
     * @throws Exception\ErrorException
     * @throws \Model\Exception\ErrorException
     * @return Result
     */
    public function importCollection($data, Cond $cond = null)
    {
        $result = new Result();
        $data = $this->prepareData($data);
        $resultIds = array();

        if (is_array($data)) {
            foreach ($data as $item) {
                $_result = $this->import($item, $cond);
                $result->addChild('item', $_result);

                if ($_result->isValid()) {
                    $resultIds[] = $_result->getResult();
                }
            }
        }

        $result->setResult($resultIds);
        return $result;
    }

    /**
     * Truncate table
     *
     * @return void
     */
    public function truncate()
    {
        $sql = "DELETE FROM `" . $this->getRawName() . "`";
        $this->getDb()->query($sql);
    }

    /**
     * Try to fetch existed row by unique indexes
     *
     * @param      $data
     * @param Cond $cond
     *
     * @throws \Model\Exception\ErrorException
     * @return array|int|mixed|null|string
     */
    public function getExistedIdByUniqueIndex($data, Cond $cond = null)
    {
        $cond = $this->prepareCond($cond);
        $cond->columns(array('id'))
              ->type(Cond::FETCH_ONE);

        $data = $this->prepareDataOnAdd($data);

        $uniqueKeyList = array(AbstractModel::INDEX_UNIQUE, AbstractModel::INDEX_PRIMARY);
        $availableIndexList = array();
        foreach ($this->indexList as $index) {
            if (!in_array($index['type'], $uniqueKeyList)) {
                continue;
            }

            $indexColumnList = $index['column_list'];

            foreach ($indexColumnList as $column) {
                if (!isset($data[$column])) {
                    continue(2);
                }
            }

            $availableIndexList[] = $index['name'];
        }

        if (!empty($availableIndexList)) {
            foreach ($availableIndexList as $availableIndex) {
                $currentIndex = $this->indexList[$availableIndex];

                $checkArray = array();
                foreach ($currentIndex['column_list'] as $column) {
                    $checkArray[$column] = $data[$column];
                }

                if ($id = $this->getByDataArray($checkArray, $cond)) {
                    return $id;
                }
            }
        }

        return 0;
    }

    /**
     *
     * @param  mixed $id
     * @param Cond   $cond
     *
     * @throws \Model\Exception\ErrorException
     * @return array|mixed|null|string
     */
    public function getById($id, Cond $cond = null)
    {
        $cond = $this->prepareCond($cond)
                        ->where(array('`' . $this->getRawName() . '`.`id`' => $this->getIdsFromMixed($id)));

        return $this->get($cond);
    }

    /**
     *
     * @param  mixed $id
     * @param Cond   $cond
     *
     * @throws \Model\Exception\ErrorException
     * @return array|mixed|null|string
     */
    public function getCollectionById($id, Cond $cond = null)
    {
        $cond = $this->prepareCond($cond);
        $ids = $this->getIdsFromMixed($id);

        return $this->getCollection($cond->where(array('`' . $this->getRawName() . '`.`id`' => $ids)));
    }

    /**
     * @param      $data
     * @param Cond $cond
     *
     * @throws \Model\Exception\ErrorException
     * @return array|mixed|null|string
     */
    public function getByDataArray($data, Cond $cond = null)
    {
        $cond = $this->prepareCond($cond);
        $data = $this->prepareData($data);

        $cond->from($this->getRawName());

        $entityList = array();
        $values = array();
        foreach ($data as $k => $v) {
            $entityList[] = $this->_underscoreToCamelCaseFilter(preg_replace('#_id$#si', '', $k));
            $values[] = $v;
        }
        $values[] = $cond;

        $columnAsComelCase = 'getBy' . implode('And', $entityList);

        if (method_exists($this, $columnAsComelCase)) {
            return call_user_func_array(array($this, $columnAsComelCase), $values);
        }


        foreach ($data as $k => $v) {
            $cond->where(array('`' . $this->getRawName() . '`.`' . $k . '`' => $v));
        }

        $result = $this->execute($cond);

        return $result;
    }

    /**
     * Выбираем нужную структуру из $inputData
     *
     * @param array $data
     * @param array $treeData
     * @param array $result
     * @return array
     */
    public static function filterArrayByKeyListRecursive($data, $treeData, &$result = array())
    {
        if (!is_array($data)) {
            return $result;
        }

        foreach ($treeData as $k => &$v) {
            if ("___list" === $k && is_array($v)) {
                foreach ($data as $_k => &$_v) {
                    if (is_numeric($_k) && is_array($_v)) {
                        $result[$_k] = array();
                        self::filterArrayByKeyListRecursive($_v, $v, $result[$_k]);
                    }
                }
            } elseif (is_numeric($k)
                && is_scalar($v)
                && array_key_exists($v, $data)
                && is_scalar($data[$v])
            ) {
                $result[$v] = $data[$v];
            } elseif (is_scalar($k)
                && is_array($v)
                && array_key_exists($k, $data)
                && is_array($data[$k])
            ) {
                $result[$k] = array();
                self::filterArrayByKeyListRecursive($data[$k], $v, $result[$k]);
            }
        }

        return $result;
    }

    /**
     * @param       $data
     * @param array $keyList
     * @return array
     */
    public function filterArrayByKeyList($data, array $keyList)
    {
        $data = $this->prepareData($data);

        $result = array();

        foreach ($keyList as $key) {
            $result[$key]  = isset($data[$key]) ? $data[$key] : null;
        }

        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    private function prepareData($data)
    {
        if (is_array($data)) {
            return $data;
        } elseif ($data instanceof Entity) {
            $data = $data->toArray();
        } elseif ($data instanceof Collection) {
            $data = $data->current()->toArray();
        } elseif (is_object($data) && method_exists($data, 'toArray')) {
            $data = $data->toArray();
        }

        return (array)$data;
    }

    /**
     * @param Cond $cond
     *
     * @throws \Model\Exception\ErrorException
     * @return null|mixed
     */
    public function get(Cond $cond = null)
    {
        $cond = $this->prepareCond($cond);

        $cond->type($cond->getType());

        return $this->execute($cond, $cond->getEntityClassName());
    }

    /**
     * @param Cond $cond
     *
     * @throws \Model\Exception\ErrorException
     * @return array|mixed|null|string|AbstractCollection|AbstractEntity[]
     */
    public function getCollection(Cond $cond = null)
    {
        $cond = $this->prepareCond($cond)
                     ->type(Cond::FETCH_ALL);

        return $this->execute($cond, $cond->getEntityClassName());
    }

    /**
     * Подготавливаем данные перед добавлением
     *
     * @param      $data
     * @param Cond $cond
     *
     * @throws \Model\Exception\ErrorException
     * @return array
     */
    protected function prepareDataOnAdd($data, Cond $cond = null)
    {
        $data = $this->prepareData($data);
        $cond = $this->prepareCond($cond);

        if (method_exists($this, 'beforePrepareOnAdd')) {
            $data = $this->beforePrepareOnAdd($data, $cond);
        }

        if (method_exists($this, 'beforePrepareOnAddOrUpdate')) {
            $data = $this->beforePrepareOnAddOrUpdate($data, $cond);
        }

        // Применяем умолчания
        $cond->checkCond(Cond::APPLY_DEFAULT_VALUES, true) && $data = $this->applyDefaultValues($data);

        // Если каскад разрешен, то применяем его
        $cond->checkCond(Cond::FILTER_CASCADE_ON_ADD, true) && $data = $this->applyFilterCascadeRules($data, $this->getFilterCascadeRulesOnAdd());

        // Фильтруем входные данные
        $cond->checkCond(Cond::FILTER_ON_ADD, true) && $data = $this->filterOnAdd($data);

        if (method_exists($this, 'afterPrepareOnAdd')) {
            // Вносить изменения в данные нельзя
            $this->afterPrepareOnAdd($data, $cond);
        }

        if (method_exists($this, 'afterPrepareOnAddOrUpdate')) {
            // Вносить изменения в данные нельзя
            $this->afterPrepareOnAddOrUpdate($data, $cond);
        }

        return $data;
    }

    /**
     * Добавить сущьность в базу
     *
     * @param mixed $data
     * @param Cond  $cond
     *
     * @throws \Model\Validator\Exception\InvalidArgumentException
     * @throws \Model\Exception\ErrorException
     * @return Result
     */
    public function add($data, Cond $cond = null)
    {
        // Если коллекция, то добавим только текущий
        // элемент. Недочет, но и хер с ним!
        if ($data instanceof AbstractCollection) {
            $data = $data->current();
        }

        $id     = null;
        $result = new Result();

        $cond = $this->prepareCond($cond);
        $data = $this->prepareDataOnAdd((array)$data, $cond);

        $isValid = true;
        if ($cond->checkCond(Cond::VALIDATE_ON_ADD, true)) {
            // Получаем валидатор добавления
            $validator = $this->validateOnAdd($data);

            // Проверяем данные и если есть ошибки
            // то добавляем их в результат
            if (!$isValid = $validator->isValid()) {
                $result->addErrorFromValidatorSet($validator);
            }
        }

        // Если валидация отключена (входим),
        // если включена и валидна то, тоже входим
        if ($isValid) {
            try {
                // Вставляем запись
                $id = $this->insert($this->getRawName(), $data);

                // Если не вставилась, то добавляем глобальную
                // ошибку в результат
                if (!$id) {
                    $result->addError('Add ' . $this->getRawName() . ' failed', 'add_' .  $this->getRawName()  . '_failed');
                }
            } catch (\PDOException $ex) {
                // Что-то пошло не так, выкинуто исключение
                // записываем это тоже в результат
                $result->addError($ex->getMessage(), $ex->getCode());
            }
        }

        // Устанавливаем идентификатор добавленной записи
        return $result->setResult((int)$id);
    }

    /**
     * Подготавливаем данные перед добавлением
     *
     * @param      $data
     * @param Cond $cond
     *
     * @throws \Model\Exception\ErrorException
     * @return array
     */
    protected function prepareDataOnUpdate($data, Cond $cond = null)
    {
        $data = $this->prepareData($data);
        $cond = $this->prepareCond($cond);

        if (method_exists($this, 'beforePrepareOnUpdate')) {
            $data = $this->beforePrepareOnUpdate($data, $cond);
        }

        if (method_exists($this, 'beforePrepareOnAddOrUpdate')) {
            $data = $this->beforePrepareOnAddOrUpdate($data, $cond);
        }

        // Если каскад разрешен, то применяем его
        $cond->checkCond(Cond::FILTER_CASCADE_ON_UPDATE, true) && $data = $this->applyFilterCascadeRules($data, $this->getFilterCascadeRulesOnUpdate());

        // Фильтруем входные данные
        $cond->checkCond(Cond::FILTER_ON_UPDATE, true) && $data = $this->filterOnUpdate($data);

        if (method_exists($this, 'afterPrepareOnUpdate')) {
            // Вносить изменения в данные нельзя
            $this->afterPrepareOnUpdate($data, $cond);
        }

        if (method_exists($this, 'afterPrepareOnAddOrUpdate')) {
            // Вносить изменения в данные нельзя
            $this->afterPrepareOnAddOrUpdate($data, $cond);
        }

        return $data;
    }

    /**
     * Добавить сущьность в базу
     *
     * @param mixed      $data
     * @param Cond|array|null  $cond
     *
     * @throws Exception\ErrorException
     * @throws \Model\Validator\Exception\InvalidArgumentException
     * @throws \Model\Exception\ErrorException
     * @return Result
     */
    public function update($data, $cond = null)
    {
        if ($data instanceof AbstractCollection) {
            $data = $data->current();
        }

        $data = $this->prepareData($data);
        $result = new Result();

        if (empty($data)) {
            return $result;
        }

        if (!$cond instanceof Cond) {
            if ($cond == null) {
                $cond = $this->prepareCond($cond);
            } elseif (is_array($cond)) {
                $_cond = $this->prepareCond(null);
                $cond  = $_cond->where($cond);
            } else {
                throw new Exception\ErrorException('Unknown cond type');
            }
        }

        $data = $this->prepareDataOnUpdate($data, $cond);

        // Если валидация включена
        if ($cond->checkCond(Cond::VALIDATE_ON_UPDATE, true)) {
            $validator = $this->validateOnUpdate($data);

            // Проверяем данные и если есть ошибки
            // то добавляем их в результат
            if (!$isValid = $validator->isValid()) {
                $result->addErrorFromValidatorSet($validator);
                return $result;
            }
        }

        try {
            $select = $this->prepareSelect($cond);
            $this->getDb()->update($this->getRawName(), $data, $select);
        } catch (\Exception $ex) {
            $result->setResult(false);
            $result->addError($ex->getMessage(), $ex->getCode());
        }

        return $result;
    }

    /**
     * @param      $data
     * @param      $id
     * @param Cond $cond
     *
     * @return Result
     * @throws Exception\ErrorException
     * @throws \Model\Exception\ErrorException
     */
    public function updateById($data, $id, Cond $cond = null)
    {
        $cond = $this->prepareCond($cond)
            ->where(array('`' . $this->getRawName() . '`.`id`' => $this->getIdsFromMixed($id)));

        return $this->update($data, $cond);
    }

    /**
     * Удаление данные
     *
     * @param array|Cond $cond
     *
     * @throws Exception\ErrorException
     * @throws \Model\Exception\ErrorException
     * @return Result
     */
    public function delete($cond = null)
    {
        if (!is_array($cond) && !$cond instanceof Cond && !is_null($cond)) {
            throw new Exception\ErrorException('Unknown Cond type');
        }

        if (is_array($cond)) {
            $cond = $this->getCond()->where($cond);
        }

        $result = new Result();

        try {
            $select = $this->prepareSelect($cond);

            $table = $cond->getCond('from', $this->getRawName());
            $stmt = $this->getDb()->delete($table, $select);
            $result->setResult($stmt->rowCount());
        } catch (\Exception $ex) {
            $result->addError($ex->getMessage(), $ex->getCode());
        }

        return $result;
    }

    /**
     * @param $dbName
     */
    protected function setDbAdapterName($dbName)
    {
        $this->dbAdapterName = (string)$dbName;
    }

    /**
     *
     * @param \Model\Db\Mysql $db
     * @return AbstractModel
     */
    public function setDbAdapter(DbAdapter $db)
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @return \Model\Db\Mysql
     */
    public function getDb()
    {
        if (!$this->db && $this->getServiceManager()) {
            $this->db = $this->getServiceManager()->get($this->dbAdapterName);
        } elseif (isset($GLOBALS['db'])) { // very dirty hack!!!
            $this->db = $GLOBALS['db'];
        }

        return $this->db;
    }

    /**
     * @param Cond $cond
     * @param null $entity
     *
     * @return array|mixed|null|string
     * @throws \Model\Db\Exception\ErrorException
     * @throws \Model\Exception\ErrorException
     */
    public function execute(Cond $cond = null, $entity = null)
    {
        if (!$cond) {
            return null;
        }

        $result = null;
        switch ($cond->getType()) {
            case Cond::FETCH_ONE:
                $result = $this->fetchOne($cond, $entity);
                break;
            case Cond::FETCH_ALL:
                $result = $this->fetchAll($cond, $entity);
                break;
            case Cond::FETCH_PAIRS:
                $result = $this->fetchPairs($cond);
                break;
            case Cond::FETCH_COUNT:
                $result = $this->fetchCount($cond);
                break;
            case Cond::FETCH_ROW:
                $result = $this->fetchRow($cond, $entity);
                break;
        }

        return $result;
    }

    /**
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function insert($table, array $data = array())
    {
        return $this->getDb()->insert($table, $data);
    }

    /**
     * @param string                   $table
     * @param array                    $data
     * @param \Model\Cond\AbstractCond $cond
     *
     * @throws \Model\Db\Exception\ErrorException
     * @throws \Model\Exception\ErrorException
     * @return bool
     */
    public function _update($table, array $data = array(), Cond $cond)
    {
        return $this->getDb()->update($table, $data, $cond);
    }

    public function fetchRow(Cond $cond = null)
    {
        $entity = $cond->getEntityName() ? $cond->getEntityName() : $this->getRawName();
        $select = $this->prepareSelect($cond->limit(1), $entity);

        if ($cond->checkCond(Cond::SHOW_QUERY) || $cond->checkCond(Cond::SHOW_QUERY_EXTENDED)) {
            echo '<!--' . $select . "-->\n";
        }

        $item = $this->db->fetchRow($select->__toString(), $select->getBind());

        if (!$cond->checkCond(Cond::WITHOUT_PREPARE)) {
            $item = call_user_func_array(array($this, 'prepare'), array($item, $cond));
        }

        return $item;
    }

    /**
     * @param Cond $cond
     *
     * @return array
     * @throws \Model\Db\Exception\ErrorException
     * @throws \Model\Exception\ErrorException
     */
    public function fetchPairs(Cond $cond = null)
    {
        $entity = $cond->getEntityName() ? $cond->getEntityName() : $this->getRawName();
        $select = $this->prepareSelect($cond->limit(1), $entity);
        if ($cond->checkCond(Cond::SHOW_QUERY) || $cond->checkCond(Cond::SHOW_QUERY_EXTENDED)) {
            echo '<!--' . $select . "-->\n";
        }
        $item = $this->db->fetchPairs($select->__toString(), $select->getBind());

        return $item;
    }

    /**
     * @param Cond $cond
     *
     * @return string
     * @throws \Model\Db\Exception\ErrorException
     * @throws \Model\Exception\ErrorException
     */
    public function fetchCount(Cond $cond = null)
    {
        $entity = $cond->getEntityName() ? $cond->getEntityName() : $this->getRawName();
        $select = $this->prepareSelect($cond->limit(1), $entity);
        if ($cond->checkCond(Cond::SHOW_QUERY) || $cond->checkCond(Cond::SHOW_QUERY_EXTENDED)) {
            echo '<!--' . $select . "-->\n";
        }
        $item = $this->db->fetchOne($select->getCountSelect(), $select->getBind());

        return $item;
    }

    /**
     * @param \Model\Cond\AbstractCond $cond
     *
     * @throws \Model\Db\Exception\ErrorException
     * @throws \Model\Exception\ErrorException
     * @return mixed
     */
    public function fetchOne(Cond $cond = null)
    {
        $entity = $cond->getEntityName() ? $cond->getEntityName() : $this->getRawName();
        $select = $this->prepareSelect($cond->limit(1), $entity);
        if ($cond->checkCond(Cond::SHOW_QUERY) || $cond->checkCond(Cond::SHOW_QUERY_EXTENDED)) {
            echo '<!--' . $select . "-->\n";
        }

        return $this->db->fetchOne($select->__toString(), $select->getBind());
    }

    /**
     * @param Cond $cond
     *
     * @throws \Model\Exception\ErrorException
     * @return array|mixed|string
     */
    public function fetchAll(Cond $cond = null)
    {
        $entity = $cond->getEntityName();
        $entity = $entity ? $entity : $this->getRawName();

        $prepareCallbackFunction = 'prepareCollection';
        $pager = null;

        try {
            $select = $this->prepareSelect($cond, $entity);
            if ($cond->checkCond(Cond::SHOW_QUERY) || $cond->checkCond(Cond::SHOW_QUERY_EXTENDED)) {
                echo '<!--' . $select . "-->\n";
            }
            if ($cond->checkCond('page')) {
                $pager = new Paginator(new Mysql($select));
                $pager->setCurrentPageNumber($cond->getCond('page', 1));
                $pager->setItemCountPerPage($cond->getCond('items_per_page', 25));

                $items = (array)$pager->getCurrentItems();
            } else {
                if ($cond->getCond('return_query')) {
                    return (string)$select;
                } else {
                    $items = $select->query()->fetchAll();
                }
            }
        } catch (\Exception $e) {
            $items = array();
        }

        if ($cond->checkCond(Cond::WITHOUT_PREPARE)) {
            return $items;
        } else {
            return call_user_func(array($this, $prepareCallbackFunction), $items, $cond, $pager);
        }
    }

    /**
     * @param Cond  $cond
     * @param mixed $entity
     *
     * @throws \Model\Db\Exception\ErrorException
     * @throws \Model\Exception\ErrorException
     * @internal param \Model\Cond\AbstractCond $opts
     * @return Select
     */
    private function prepareSelect(Cond $cond = null, $entity = null)
    {
        $cond = $cond ?: $this->getCond($entity);

        $select = new Select($this->getDb());

        /**********************************************************************
         * FROM
         *********************************************************************/
        $from = $cond->getCond(Cond::FROM);

        if (!$from) {
            $from = $this->getRawName();
        }

        if (!is_array($from)) {
            $from = $this->getDb()->quoteTableAs($from);
        }
        $select->from($from);

        /**********************************************************************
         * DISTINCT
         *********************************************************************/
        $distinct = $cond->getCond(Cond::DISTINCT);
        if ($distinct) {
            $select->distinct($cond->getCond(Cond::DISTINCT));
        }

        /**********************************************************************
         * COLUMNS
         *********************************************************************/
        if ($cond->checkCond(Cond::COLUMNS)) {
            $select->reset(Cond::COLUMNS)->columns($cond->getCond(Cond::COLUMNS));
        }

        /**********************************************************************
         * JOIN
         *********************************************************************/
        if ($cond->checkAnyJoin()) {
            $joinRules = $cond->getJoin();

            $relation = $this->getRelation();
            foreach ($joinRules as $join) {
                $joinFunc = Cond::$_joinTypes[$join->getJoinType()];
                if (!$join->issetRule() && isset($relation[$join->getEntity()])) {
                    $rel = $relation[$join->getEntity()];
                    $joinFunc = Cond::$_joinTypes[$join->getJoinType()];

                    if (isset($rel['link_table']) && !empty($rel['link_table'])) {
                        $select->$joinFunc($rel['link_table'], "`{$rel['local_table']}`.`{$rel['local_column']}` = `{$rel['link_table']}`.`{$rel['link_table_local_column']}`", '');
                        $select->$joinFunc($rel['foreign_table'], "`{$rel['foreign_table']}`.`{$rel['foreign_column']}` = `{$rel['link_table']}`.`{$rel['link_table_foreign_column']}`", '');
                    } else {

                        $select->$joinFunc($rel['foreign_table'], "`{$rel['local_table']}`.`{$rel['local_column']}` = `{$rel['foreign_table']}`.`{$rel['foreign_column']}`", '');
                    }
                } else {
                    $select->$joinFunc($join->getTable(), $join->getCondition(), $join->getColumns());
                }
            }
        }

        /**********************************************************************
         * WHERE
         *********************************************************************/
        $where = $cond->getCond(Cond::WHERE);
        if (is_array($where) && !empty($where)) {
            foreach ($where as $whereCond) {
                if (!is_array($whereCond)) {
                    continue;
                }

                $_cond = $whereCond['cond'];
                $_bind = $whereCond['bind'];

                if (is_array($_cond)) {
                    foreach ($_cond as $k => $value) {
                        if (is_array($value) && count($value) == 1) {
                            $value = reset($value);
                        }

                        if (is_null($value)) {
                            $_cond = $k . ' IS NULL ';
                        } elseif (is_array($value)) {
                            foreach ($value as &$v) {
                                if (is_scalar($v) && !is_int($v)) {
                                    $v = $this->getDb()->_quote($v);
                                }
                            }
                            $_v = implode(',', $value);
                            $_cond = $k . ' IN (' . $_v . ') ';
                        } elseif ($value instanceof Expr) {
                            if (is_string($k)) {
                                $_cond = $k . ' =  ' . $value;
                            } else {
                                $_cond = $value->__toString();
                            }
                        } elseif (is_int($value)) {
                            $_cond = $k . ' = ' . $value;
                        } elseif (is_scalar($value)) {
                            $_v = $this->getDb()->_quote($value);
                            $_cond = $k . ' =  ' . $_v;
                        }

                        $select->where(trim($_cond));
                    }
                } else {
                    $select->where($_cond, $_bind);
                }
            }
        }

        /**********************************************************************
         * GROUP
         *********************************************************************/
        $group = $cond->getCond(Cond::GROUP);
        if (is_array($group) && !empty($group)) {
            foreach ($group as $_group) {
                $select->group($_group);
            }
        }

        /**********************************************************************
         * ORDER
         *********************************************************************/
        $order = $cond->getCond(Cond::ORDER);
        if (is_array($order) && !empty($order)) {
            foreach ($order as $_order) {
                if ($_order instanceof Select) {
                    $_order = str_replace("`", '', $_order->renderOrder());
                }

                $select->order($_order);
            }
        }

        /**********************************************************************
         *
         *********************************************************************/
        if (!$cond->checkCond(Cond::PAGE)) {
            if ($cond->checkCond(Cond::LIMIT)) {
                if ($cond->checkCond(Cond::OFFSET)) {
                    $select->limit($cond->getCond(Cond::LIMIT), $cond->getCond(Cond::OFFSET));
                } else {
                    $select->limit($cond->getCond(Cond::LIMIT));
                }
            } else if ($cond->checkCond(Cond::OFFSET)) {
                $select->limit(0, $cond->getCond(Cond::OFFSET));
            }
        }

        return $select;
    }

    /**
     * @return DbAdapter
     */
    public function beginTransaction()
    {
        return $this->getDb()->beginTransaction();
    }

    /**
     * @return DbAdapter
     */
    public function rollback()
    {
        return $this->getDb()->rollback();
    }

    /**
     * @return DbAdapter
     */
    public function commit()
    {
        return $this->getDb()->rollback();
    }

}