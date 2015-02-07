<?php


namespace Model\Validator\Adapter;

use Model\Validator\Exception\ErrorException;
use Model\Validator\ValidatorSet;

/**
 * Валидация данных
 *
 * @category   Model
 * @package    Validator
 * @author     Eugene Myazin <meniam@gmail.com>
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
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
     * @param \Zend\Validator\ValidatorInterface $validator
     * @param $value
     *
     * @return bool
     */
    public function isValid($validator, $value)
    {
        return $validator->isValid($value);
    }

    /**
     * @return mixed
     */
    public function getNotEmptyValidator()
    {
        return new \Zend\Validator\NotEmpty();
    }

    /**
     * @param array $validatorList
     * @return array
     */
    public function getValidatorMessages($validatorList)
    {
        $result = array();
        foreach ($validatorList as $field => $validatorArray) {
            /** @var \Zend\Validator\ValidatorInterface $validator */
            foreach ($validatorArray as $validator) {
                $messages = $validator->getMessages();
                if (!empty($messages)) {
                    $result[$field] = $result[$field] + $messages;
                }
            }
        }

        return $result;
    }
}