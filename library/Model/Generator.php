<?php

namespace Model;

use Model\Generator\Part\AbstractPart;
use Model\Db\Mysql as DbAdapter;

use Model\Cluster\Schema\Table;
use Model\Generator\Part\Cond;
use Model\Generator\Part\FrontCond;

/**
 * Основной класс генератора
 *
 * @category   Model
 * @package    Model_Generator
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
class Generator
{
    /**
     * Название базы данных
     *
     * @var string
     */
    protected $_dbName;

    /**
     * @var Cluster
     */
    protected $cluster;

    /**
     * @var DbAdapter
     */
    protected $_db;

    /**
     * @var
     */
    protected $_outDir;

    /**
     * @var
     */
    protected $_deployDir;

    /**
     * @var
     */
    private $sm;

	public function __construct($sm, $cluster, $outDir)
	{
        $this->sm = $sm;
        $this->cluster = $cluster;
		$this->_outDir = rtrim($outDir, '/');

		if (!is_dir($outDir) || !is_writeable($outDir)) {
			throw new \Model\Exception\ErrorException('Directory is not writable: ' . $outDir);
		}

        @mkdir($outDir . '/abstract');
        @mkdir($outDir . '/entities');
        @mkdir($outDir . '/entities/abstract');
        @mkdir($outDir . '/collections');
        @mkdir($outDir . '/collections/abstract');

        @mkdir($outDir . '/cond');
        @mkdir($outDir . '/cond/abstract');

		if (!is_dir($outDir . '/abstract') || !is_readable($outDir . '/abstract')) {
			throw new \Model\Exception\ErrorException('Directory model abstract "' . $outDir . '/abstract" is not writable');
		}

		if (!is_dir($outDir . '/entities') || !is_readable($outDir . '/entities')) {
			throw new \Model\Exception\ErrorException('Directory entities "' . $outDir . '/entities" is not writable');
		}

		if (!is_dir($outDir . '/entities/abstract') || !is_readable($outDir . '/entities/abstract')) {
			throw new \Model\Exception\ErrorException('Directory entity abstract "' . $outDir . '/entities/abstract" is not writable');
		}

		if (!is_dir($outDir . '/collections') || !is_readable($outDir . '/collections')) {
			throw new \Model\Exception\ErrorException('Directory collections is not writable');
		}

		if (!is_dir($outDir . '/collections/abstract') || !is_readable($outDir . '/collections/abstract')) {
			throw new \Model\Exception\ErrorException('Directory collections abstract "' . $outDir . '/collections/abstract" is not writable');
		}
	}


    public function buildPartEntity($tableName)
    {
        $table = $this->getCluster()->getTable($tableName);
        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        new \Model\Generator\Part\Entity($table, $this->getCluster(), $this->_outDir . '/entities/abstract/Abstract' . $tableNameAsCamelCase . 'Entity.php');
    }

    public function buildPartFrontEntity($tableName)
    {
        $table = $this->getCluster()->getTable($tableName);
        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        new \Model\Generator\Part\FrontEntity($table, $this->getCluster(), $this->_outDir . '/entities/' . $tableNameAsCamelCase . 'Entity.php');
    }

    /**
     *
     * @param $tableName
     * @return bool
     */
    public function buildPartCollection($tableName)
    {
        $table = $this->getCluster()->getTable($tableName);
        if (!$table) {
            return false;
        }

        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        new \Model\Generator\Part\Collection($table, $this->getCluster(), $this->_outDir . '/collections/abstract/Abstract' . $tableNameAsCamelCase . 'Collection.php');

        return true;
    }

    /**
     * @param $tableName
     * @return bool
     */
    public function buildPartFrontCollection($tableName)
    {
        $table = $this->getCluster()->getTable($tableName);
        if (!$table) {
            return false;
        }

        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        new \Model\Generator\Part\FrontCollection($table, $this->getCluster(), $this->_outDir . '/collections/' . $tableNameAsCamelCase . 'Collection.php');

        return true;
    }

    /**
     * @param $tableName
     * @return bool
     */
    public function buildPartModel($tableName)
    {
        $table = $this->getCluster()->getTable($tableName);

        if (!$table) {
            return false;
        }

        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        new \Model\Generator\Part\Model($table, $this->getCluster(), $this->_outDir . '/abstract/Abstract' . $tableNameAsCamelCase . 'Model.php');

        return true;
    }

    /**
     * @param $tableName
     * @return bool
     */
    public function buildPartFrontModel($tableName)
    {
        $table = $this->getCluster()->getTable($tableName);

        if (!$table) {
            return false;
        }

        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        new \Model\Generator\Part\FrontModel($table, $this->getCluster(), $this->_outDir . '/' . $tableNameAsCamelCase . 'Model.php');

        return true;
    }

    /**
     * @param      $tableName
     * @param null $aliasName
     * @return bool
     */
    public function buildPartCond($tableName, $aliasName = null)
    {
        $table = $this->getCluster()->getTable($tableName);
        if (!$table) {
            return false;
        }

        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        if ($aliasName) {
            $file = $this->_outDir . '/cond/abstract/Abstract' . implode('', array_map('ucfirst', explode('_', $aliasName))) . 'Cond.php';
        } else {
            $file = $this->_outDir . '/cond/abstract/Abstract' . $tableNameAsCamelCase . 'Cond.php';
        }

        $cond = new Cond($table, $this->getCluster(), $file);
        $cond->generate(array('alias' => $aliasName));

        return true;
    }

    public function buildPartFrontCond($tableName, $aliasName = null)
    {
        $table = $this->getCluster()->getTable($tableName);
        $tableNameAsCamelCase = $table->getNameAsCamelCase();

        if ($aliasName) {
            $file = $this->_outDir . '/cond/' . implode('', array_map('ucfirst', explode('_', $aliasName))) . 'Cond.php';
        } else {
            $file = $this->_outDir . '/cond/' . $tableNameAsCamelCase . 'Cond.php';
        }

        $cond = new FrontCond($table, $this->getCluster(), $file);
        $cond->generate(array('alias' => $aliasName));

        return true;
    }


    public function run()
    {
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Cond\JoinConst(), \Model\Generator\Part\AbstractPart::PART_COND);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Cond\WithConst(), \Model\Generator\Part\AbstractPart::PART_COND);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Cond\SetupEntity(), \Model\Generator\Part\AbstractPart::PART_COND);


        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Entity\Dockblock(),       \Model\Generator\Part\AbstractPart::PART_ENTITY);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Entity\DecoratorMethod(), \Model\Generator\Part\AbstractPart::PART_ENTITY);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Entity\DataTypes(),       \Model\Generator\Part\AbstractPart::PART_ENTITY);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Entity\Getter(),          \Model\Generator\Part\AbstractPart::PART_ENTITY);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Entity\GetterEnum(),          \Model\Generator\Part\AbstractPart::PART_ENTITY);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Entity\RelatedGetter(),   \Model\Generator\Part\AbstractPart::PART_ENTITY);

        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Collection\DefaultEntityType(), \Model\Generator\Part\AbstractPart::PART_COLLECTION);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Collection\Dockblock(), \Model\Generator\Part\AbstractPart::PART_COLLECTION);

        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Model\Dockblock(), \Model\Generator\Part\AbstractPart::PART_MODEL);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Model\InitDefaults(), \Model\Generator\Part\AbstractPart::PART_MODEL);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Model\Relationdefine(), \Model\Generator\Part\AbstractPart::PART_MODEL);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Model\IndexList(), \Model\Generator\Part\AbstractPart::PART_MODEL);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Model\Construct(), \Model\Generator\Part\AbstractPart::PART_MODEL);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Model\InitFilterRules(), \Model\Generator\Part\AbstractPart::PART_MODEL);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Model\InitValidatorRules(), \Model\Generator\Part\AbstractPart::PART_MODEL);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Model\Getter(), \Model\Generator\Part\AbstractPart::PART_MODEL);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Model\GetInstance(), \Model\Generator\Part\AbstractPart::PART_MODEL);
        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\Model\Link(), \Model\Generator\Part\AbstractPart::PART_MODEL);

        AbstractPart::addPlugin(new \Model\Generator\Part\Plugin\FrontModel\Stubs(), \Model\Generator\Part\AbstractPart::PART_FRONT_MODEL);


        /**
         * Generate Alias cond
         */
        $classmap = '';
        foreach ($this->getCluster()->getTablelist() as $table) {
            $aliasList = $table->getAliasLinkList();

            if (!empty($aliasList)) {
                foreach ($aliasList as $aliasName => $tableName) {
                    $this->buildPartFrontCond($tableName, $aliasName);

                    $aliasAsCamelCase = implode('', array_map('ucfirst', explode('_', $aliasName)));

                    $classmap .= "\t\t'Model\\\\Cond\\\\{$aliasAsCamelCase}Cond' => __DIR__ . '/cond/{$aliasAsCamelCase}Cond.php',\n";
                }
            }
        }

        /** @var  $table Table */
        foreach ($this->getCluster()->getTableList() as $table) {
            $this->buildPartModel($table->getName());
            $this->buildPartFrontModel($table->getName());
            $this->buildPartCond($table->getName());
            $this->buildPartFrontCond($table->getName());

            $this->buildPartEntity($table->getName());
            $this->buildPartFrontEntity($table->getName());

            $this->buildPartCollection($table->getName());
            $this->buildPartFrontCollection($table->getName());

            $tableNameAsCamelCase = $table->getNameAsCamelCase();
            $classmap .= "\t\t'Model\\\\Abstract{$tableNameAsCamelCase}Model' => __DIR__ . '/abstract/Abstract{$tableNameAsCamelCase}Model.php',\n";
            $classmap .= "\t\t'Model\\\\{$tableNameAsCamelCase}Model' => __DIR__ . '/{$tableNameAsCamelCase}Model.php',\n";
            $classmap .= "\t\t'Model\\\\Entity\\\\{$tableNameAsCamelCase}Entity' => __DIR__ . '/entities/{$tableNameAsCamelCase}Entity.php',\n";
            $classmap .= "\t\t'Model\\\\Entity\\\\Abstract{$tableNameAsCamelCase}Entity' => __DIR__ . '/entities/abstract/Abstract{$tableNameAsCamelCase}Entity.php',\n";
            $classmap .= "\t\t'Model\\\\Collection\\\\Abstract{$tableNameAsCamelCase}Collection' => __DIR__ . '/collections/abstract/Abstract{$tableNameAsCamelCase}Collection.php',\n";
            $classmap .= "\t\t'Model\\\\Collection\\\\{$tableNameAsCamelCase}Collection' => __DIR__ . '/collections/{$tableNameAsCamelCase}Collection.php',\n";
            $classmap .= "\t\t'Model\\\\Cond\\\\Abstract{$tableNameAsCamelCase}Cond' => __DIR__ . '/cond/abstract/Abstract{$tableNameAsCamelCase}Cond.php',\n";
            $classmap .= "\t\t'Model\\\\Cond\\\\{$tableNameAsCamelCase}Cond' => __DIR__ . '/cond/{$tableNameAsCamelCase}Cond.php',\n";
        }



        file_put_contents($this->_outDir . '/_autoload_classmap.php', "<?php\nreturn array(" . $classmap . ");\n");
    }

    public function deploy($outDir)
    {
        $this->_deployDir = rtrim($outDir, '/');

        if (!is_dir($outDir) || !is_writeable($outDir)) {
            throw new \Model\Exception\ErrorException('Directory is not writable: ' . $outDir);
        }

        @mkdir($outDir . '/abstract');
        @mkdir($outDir . '/entities');
        @mkdir($outDir . '/entities/abstract');
        @mkdir($outDir . '/collections');
        @mkdir($outDir . '/collections/abstract');

        @mkdir($outDir . '/cond');
        @mkdir($outDir . '/cond/abstract');

        foreach (glob($this->_outDir . '/abstract/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/abstract/' . basename($filename);
            //echo $deployFile;
            copy($filename, $deployFile);
        }

        unlink($this->_deployDir . '/_autoload_classmap.php');
        foreach (glob($this->_outDir . '/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/' . basename($filename);
            //echo $deployFile;

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }

        foreach (glob($this->_outDir . '/cond/abstract/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/cond/abstract/' . basename($filename);
            //echo $deployFile;
            copy($filename, $deployFile);
        }

        foreach (glob($this->_outDir . '/cond/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/cond/' . basename($filename);
            //echo $deployFile;

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }

        foreach (glob($this->_outDir . '/collections/abstract/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/collections/abstract/' . basename($filename);
            //echo $deployFile;
            copy($filename, $deployFile);
        }

        foreach (glob($this->_outDir . '/collections/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/collections/' . basename($filename);
            //echo $deployFile;

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }

        foreach (glob($this->_outDir . '/entities/abstract/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/entities/abstract/' . basename($filename);
            //echo $deployFile;
            copy($filename, $deployFile);
        }

        foreach (glob($this->_outDir . '/entities/*.php') as $filename) {
            $deployFile = $this->_deployDir . '/entities/' . basename($filename);
            //echo $deployFile;

            if (!is_file($deployFile)) {
                copy($filename, $deployFile);
            }
        }
    }

    /**
     * @return Cluster
     */
    public function getCluster()
    {
        return $this->cluster;
    }

    /**
     * @return string
     */
    public function toXml()
    {
        $xml = $this->getCluster()->toXml();
        $xml = simplexml_load_string($xml);
        return $xml->asXml();
    }
}