<?php


namespace Model\Validator\Adapter;

use Model\Validator\Exception\ErrorException;
use Zend\Validator\NotEmpty;
use Zend\Validator\ValidatorInterface;

/**
 * Validator Zend Adapter
 *
 * @category   Model
 * @package    Validator
 * @author     Mikhail Rybalka <ruspanzer@gmail.com>
 */
class Zend extends AbstractAdapter
{
    public function getValidatorInstance($class, array $args = array(), array $namespaces = array())
    {
        $namespaces = array_merge($namespaces, array('', '\\Zend\\Validator'));

        foreach ($namespaces as $namespace) {
            if ($namespace) {
                $className = rtrim($namespace, '\\') . '\\' . ucfirst($class);
            } else {
                $className  = $class;
            }

            $_class = new \ReflectionClass($className);
            if ($_class->implementsInterface('\Zend\Validator\ValidatorInterface')) {
                if ($_class->hasMethod('__construct')) {
                    $object = $_class->newInstanceArgs(array($args));
                } else {
                    $object = $_class->newInstance();
                }

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
        $result = array();
        foreach ($data as $field => $value) {
            $result[$field] = array();
            if (isset($validatorList[$field]) && is_array($validatorList[$field])) {
                /** @var ValidatorInterface $validator */
                foreach ($validatorList[$field] as $validator) {
                    $validator->isValid($value);
                    $result[$field][] = $validator;
                }
            }
        }

        return $result;
    }

    /**
     * @param $validatorResult
     *
     * @return bool
     */
    public function isValid($validatorResult)
    {
        foreach ($validatorResult as $validatorList) {
            /** @var ValidatorInterface $validator */
            foreach ($validatorList as $validator) {
                if (count($validator->getMessages())) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function getNotEmptyValidator()
    {
        return new NotEmpty();
    }

    /**
     * @param array $validatorList
     * @return array
     */
    public function getValidatorMessages($validationResult)
    {
        $result = array();
        foreach ($validationResult as $field => $validatorArray) {
            $messages = array();
            /** @var \Zend\Validator\ValidatorInterface $validator */
            foreach ($validatorArray as $validator) {
                $messages += $validator->getMessages();
            }
            if (!empty($messages)) {
                $result[$field] = $messages;
            }
        }

        return $result;
    }
}