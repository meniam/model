<?php

class WeightDecorator
{
    /**
     * Вес в виде "12.34 g." или просто "12"
     */
    const SYSTEM_G = 'g';

    /**
     * Вес в виде "12.34 kg." или просто "12"
     */
    const SYSTEM_KG = 'kg';

    /**
     * Вес в виде "12 kg. 34.56 g." или просто "12 34"
     */
    const SYSTEM_KG_G = 'kg_g';

    /**
     * Вес в виде "12.34 oz." или просто "12"
     */
    const SYSTEM_OZ = 'oz';

    /**
     * Вес в виде "12.34 lb." или просто "12"
     */
    const SYSTEM_LB = 'lb';

    /**
     * Вес в виде "12 lb. 34.56 oz." или просто "12 34"
     */
    const SYSTEM_LB_OZ = 'lb_oz';

    /**
     * Попытаться определить систему счисления и формат
     */
    const SYSTEM_TRY_PARSE = 'try_parse';

    const PRECISION_G = 0;

    const PRECISION_KG = 3;

    const PRECISION_OZ = 1;

    const PRECISION_LB = 5;

    protected static $_allowedSystems = array(
        self::SYSTEM_KG_G,
        self::SYSTEM_KG,
        self::SYSTEM_G,
        self::SYSTEM_LB_OZ,
        self::SYSTEM_LB,
        self::SYSTEM_OZ
    );

    protected $_weight = null;

    protected $_system = self::SYSTEM_G;

    protected $_preciseMode;

    /**
     * Создать декоратор для веса
     * @param string|int|float|WeightDecorator $weight Вес в определенной системе
     * @param string $system Тип системы счисления веса
     *
     * @return WeightDecorator
     */
    public function __construct($weight, $system = null)
    {
        if ($system == self::SYSTEM_TRY_PARSE) {
            $weight = self::tryParse($weight);
        }

        if ($weight instanceof WeightDecorator) {
            if (!$weight->exists()) {
                return;
            }
            $this->_weight      = $weight->getWeight();
            $this->_system      = $weight->getSystem();
            $this->_preciseMode = $weight->getPreciseMode();
            return;
        }

        if (!in_array($system, self::$_allowedSystems)) {
            return;
        }

        $this->setPreciseMode(false);

        $weight = trim(strval($weight));

        switch ($system) {
            case self::SYSTEM_KG_G:
                if (preg_match('/^(\d+)(\s*kg\.?)?\s+(\d+(\.\d+)?)(\s*g\.?)?$/i', $weight, $parts) && $parts[3] < 1000.0) {
                    $this->_system = $system;
                    $this->_weight = floatval($parts[3] + $parts[1] * 1000);
                }
                break;
            case self::SYSTEM_KG:
                if (preg_match('/^(\d+(\.\d+)?)(\s*kg\.?)?$/i', $weight, $parts)) {
                    $this->_system = $system;
                    $this->_weight = floatval($parts[1] * 1000);
                }
                break;
            case self::SYSTEM_G:
                if (preg_match('/^(\d+(\.\d+)?)(\s*g\.?)?$/i', $weight, $parts)) {
                    $this->_system = $system;
                    $this->_weight = floatval($parts[1]);
                }
                break;
            case self::SYSTEM_LB_OZ:
                if (preg_match('/^(\d+)(\s*lb\.?)?\s+(\d+(\.\d+)?)(\s*oz\.?)?$/i', $weight, $parts) && $parts[3] < 16.0) {
                    $this->_system = $system;
                    $this->_weight = floatval($parts[3] + $parts[1] * 16);
                }
                break;
            case self::SYSTEM_LB:
                if (preg_match('/^(\d+(\.\d+)?)(\s*lb\.?)?$/i', $weight, $parts)) {
                    $this->_system = $system;
                    $this->_weight = floatval($parts[1] * 16);
                }
                break;
            case self::SYSTEM_OZ:
                if (preg_match('/^(\d+(\.\d+)?)(\s*oz\.?)?$/i', $weight, $parts)) {
                    $this->_system = $system;
                    $this->_weight = floatval($parts[1]);
                }
                break;
        }
    }

    /**
     * Попытаться создать декоратор. При неопределенности вернет пустой декоратор
     * @param string|int|float|WeightDecorator $weight Вес
     *
     * @return WeightDecorator
     */
    public static function tryParse($weight)
    {
        if ($weight instanceof WeightDecorator) {
            return $weight;
        }

        $resultDecorator = null;
        $decorators      = array(
            self::SYSTEM_KG_G,
            self::SYSTEM_LB_OZ
        );
        foreach ($decorators as &$decorator_type) {
            $decorator = new WeightDecorator($weight, $decorator_type);
            if (!$decorator->exists()) {
                continue;
            }
            // неопределенность
            if ($resultDecorator) {
                return new WeightDecorator(null, null);
            }
            $resultDecorator = $decorator;
        }

        if ($resultDecorator) {
            return $resultDecorator;
        }

        $decorators = array(
            self::SYSTEM_G,
            self::SYSTEM_OZ,
            self::SYSTEM_KG,
            self::SYSTEM_LB
        );
        foreach ($decorators as &$decorator_type) {
            $decorator = new WeightDecorator($weight, $decorator_type);
            if (!$decorator->exists()) {
                continue;
            }
            // неопределенность
            if ($resultDecorator) {
                return new WeightDecorator(null, null);
            }
            $resultDecorator = $decorator;
        }
        return $resultDecorator ? : new WeightDecorator(null, null);
    }

    /**
     * Создан ли декоратор
     * @return bool
     */
    public function exists()
    {
        return $this->_weight !== null;
    }

    /**
     * Получить вес в g / oz
     * @return float
     */
    protected function getWeight()
    {
        return $this->_weight;
    }

    /**
     * Текущая система счисления
     * @return string
     */
    public function getSystem()
    {
        if (!$this->exists()) {
            return null;
        }
        return $this->_system;
    }

    /**
     * Текущая система счисления
     */
    public function setSystem($system)
    {
        switch ($system) {
            case self::SYSTEM_G:
            case self::SYSTEM_KG:
            case self::SYSTEM_KG_G:
                $this->_weight = $this->getTotalGrams();
                break;
            case self::SYSTEM_OZ:
            case self::SYSTEM_LB:
            case self::SYSTEM_LB_OZ:
                $this->_weight = $this->getTotalOz();
                break;
            default:
                return;
        }
        $this->_system = $system;
    }

    /**
     * Включен ли точный режим
     * @return bool
     */
    public function getPreciseMode()
    {
        return $this->_preciseMode;
    }

    /**
     * Включить/выключить точный режим
     * Точный режим нужен при многократных переводах веса
     * из одной системы весов в другую, но округление мешает сводить суммы весов
     * @param bool $on
     */
    public function setPreciseMode($on = true)
    {
        $this->_preciseMode = (bool)$on;
    }

    protected function _roundWeight($weight, $precision)
    {
        if ($this->getPreciseMode()) {
            return abs(max(floatval(round($weight, $precision)), 0));
        }
        $precision = max($precision, 0);
        if (!$precision) {
            return intval($weight);
        }
        $weight = round($weight, $precision + 1);
        $weight = explode('.', strval(floatval($weight)));
        if (isset($weight[1]) && strlen($weight[1]) > $precision) {
            $weight[1] = substr($weight[1], 0, $precision);
        }
        return abs(max(floatval(implode('.', $weight)), 0));
    }

    /**
     * Полный вес в граммах
     * @return float
     */
    public function getTotalGrams()
    {
        if (!$this->exists()) {
            return 0;
        }

        switch ($this->_system) {
            case self::SYSTEM_G:
            case self::SYSTEM_KG:
            case self::SYSTEM_KG_G:
                return $this->_roundWeight($this->_weight, self::PRECISION_G);
                break;
            case self::SYSTEM_OZ:
            case self::SYSTEM_LB:
            case self::SYSTEM_LB_OZ:
                return $this->_roundWeight($this->_weight * 28.3495231, self::PRECISION_G);
                break;
        }

        return 0;
    }

    /**
     * Граммовая составляющая веса
     * @return float
     */
    public function getGrams()
    {
        if (!$this->exists()) {
            return 0;
        }

        return $this->_roundWeight($this->getTotalGrams() - $this->getKilos() * 1000, self::PRECISION_G);
    }

    /**
     * Полный вес в килограммах
     * @return float
     */
    public function getTotalKilos()
    {
        if (!$this->exists()) {
            return 0;
        }

        return $this->_roundWeight($this->getTotalGrams() / 1000, self::PRECISION_KG);
    }

    /**
     * Килограммная составляющая веса
     * @return float
     */
    public function getKilos()
    {
        if (!$this->exists()) {
            return 0;
        }

        return intval($this->_roundWeight($this->getTotalGrams(), self::PRECISION_G) / 1000);
    }

    /**
     * Полный вес в унциях
     * @return float
     */
    public function getTotalOz()
    {
        if (!$this->exists()) {
            return 0;
        }

        switch ($this->_system) {
            case self::SYSTEM_OZ:
            case self::SYSTEM_LB:
            case self::SYSTEM_LB_OZ:
                return $this->_weight;
                break;
            case self::SYSTEM_G:
            case self::SYSTEM_KG:
            case self::SYSTEM_KG_G:
                return $this->_roundWeight($this->_weight / 28.3495231, self::PRECISION_OZ);
                break;
        }

        return 0;
    }

    /**
     * Унционная составляющая веса
     * @return float
     */
    public function getOz()
    {
        if (!$this->exists()) {
            return 0;
        }

        return $this->_roundWeight($this->getTotalOz() - $this->getLb() * 16, self::PRECISION_OZ);
    }

    /**
     * Полный вес в фунтах
     * @return float
     */
    public function getTotalLb()
    {
        if (!$this->exists()) {
            return 0;
        }

        return $this->_roundWeight($this->getTotalOz() / 16, self::PRECISION_LB);
    }

    /**
     * Фунтовая составляющая веса
     * @return float
     */
    public function getLb()
    {
        if (!$this->exists()) {
            return 0;
        }

        return intval($this->_roundWeight($this->getTotalOz(), self::PRECISION_OZ) / 16);
    }

    /**
     * Вес в виде "12 lb 34.56 oz"
     * @return string
     */
    public function toLbOzString()
    {
        return $this->toString(self::SYSTEM_LB_OZ);
    }

    /**
     * Вес в виде "12.345678 lb"
     * @return string
     */
    public function toLbString()
    {
        return $this->toString(self::SYSTEM_LB);
    }

    /**
     * Вес в виде "1234.56 oz"
     * @return string
     */
    public function toOzString()
    {
        return $this->toString(self::SYSTEM_OZ);
    }

    /**
     * Вес в виде "12 kg 345 g"
     * @return string
     */
    public function toKgGramsString()
    {
        return $this->toString(self::SYSTEM_KG_G);
    }

    /**
     * Вес в виде "12.345 kg"
     * @return string
     */
    public function toKilosString()
    {
        return $this->toString(self::SYSTEM_KG);
    }

    /**
     * Вес в виде "12345 g"
     * @return string
     */
    public function toGramsString()
    {
        return $this->toString(self::SYSTEM_G);
    }

    /**
     * Сконвертировать и получить значение в определенной истеме измерения
     * @param string $system Система измерения WeightDecorator::SYSTEM_* (выбранная)
     *
     * @return float|int
     */
    public function convertTo($system)
    {
        switch ($system) {
            case self::SYSTEM_G:
                return $this->getTotalGrams();
            case self::SYSTEM_KG:
                return $this->getTotalKilos();
            case self::SYSTEM_OZ:
                return $this->getTotalOz();
            case self::SYSTEM_LB:
                return $this->getTotalLb();
        }
        return 0;
    }

    /**
     * Вес в определенной системе измерения
     * @param string $system Система измерения WeightDecorator::SYSTEM_* (выбранная) или null (текущая)
     *
     * @return string
     */
    public function toString($system = null)
    {
        if (!$this->exists()) {
            return '';
        }

        if (!$system) {
            $system = $this->_system;
        }

        switch ($system) {
            case self::SYSTEM_G:
                return $this->getTotalGrams() . ' g';
            case self::SYSTEM_KG:
                return $this->getTotalKilos() . ' kg';
            case self::SYSTEM_KG_G:
                $kg     = $this->getKilos();
                $gr     = $this->getGrams();
                $result = '';

                if ($kg) {
                    $result = $kg . ' kg';
                }

                if ($gr) {
                    if ($gr) {
                        $result .= ' ';
                    }

                    $result .= $gr . ' g';
                }
                return $result;
            case self::SYSTEM_OZ:
                return $this->getTotalOz() . ' oz';
            case self::SYSTEM_LB:
                return $this->getTotalLb() . ' lb';
            case self::SYSTEM_LB_OZ:
                $lb     = $this->getLb();
                $oz     = $this->getOz();
                $result = '';

                if ($lb) {
                    $result = $lb . ' lb';
                }

                if ($oz) {
                    if ($lb) {
                        $result .= ' ';
                    }

                    $result .= $oz . ' oz';
                }
                return $result;
        }
        return '';
    }

    /**
     * Вес в текущей системе измерения
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}