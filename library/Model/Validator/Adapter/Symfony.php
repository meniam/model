<?php

namespace Model\Validator\Adapter;

use Model\Validator\Exception\ErrorException;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Validator Symfony Adapter
 *
 * @category   Model
 * @package    Validator
 * @author     Mikhail Rybalka <ruspanzer@gmail.com>
 */
class Symfony extends AbstractAdapter
{
    /**
     * @param $class
     * @param array $args
     * @param array $namespaces
     *
     * @return mixed
     * @throws ErrorException
     */
    public function getValidatorInstance($class, array $args = array(), array $namespaces = array())
    {
        $namespaces = array_merge($namespaces, array('', '\\Symfony\\Component\\Validator\\Constraints\\'));

        foreach ($namespaces as $namespace) {
            if ($namespace) {
                $className = rtrim($namespace, '\\') . '\\' . ucfirst($class);
            } else {
                $className  = $class;
            }

            $_class = new \ReflectionClass($className);
            if ($_class->isSubclassOf('\\Symfony\\Component\\Validator\\Constraint')) {
                $object = $_class->newInstanceArgs(array($args));
                return $object;
            }
        }

        throw new ErrorException("Validator class not found from basename '{$class}'");
    }

    /**
     * @param array $validatorList
     * @param array $data
     * @return mixed
     */
    public function validate($validatorList, array $data)
    {
        $validatorObject = Validation::createValidator();

        $result = array();
        foreach ($data as $field => $value) {
            $result[$field] = array();
            if (isset($validatorList[$field])) {
                $result[$field] = $validatorObject->validate($value, $validatorList[$field]);
            }
        }

        return $result;
    }

    /**
     * @param $validatorResult
     * @return bool
     */
    public function isValid($validatorResult)
    {
        /** @var ConstraintViolationListInterface $result */
        foreach ($validatorResult as $result) {
            if ($result->count()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getNotEmptyValidator()
    {
        return new NotBlank();
    }

    /**
     * Get decorated messages array
     *
     * @param array $validationResult
     * @return array
     */
    public function getValidatorMessages($validationResult)
    {
        $result = array();
        /**
         * @var  $field
         * @var \Symfony\Component\Validator\ConstraintViolationList $fieldResult
         */
        foreach ($validationResult as $field => $fieldResult) {
            $messages = array();
            foreach ($fieldResult as $violation) {
                $messages[] = $violation->getMessage();
            }
            if (!empty($messages)) {
                $result[$field] = $messages;
            }
        }

        return $result;
    }
}