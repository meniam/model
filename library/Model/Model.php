<?php

namespace Model;

/**
 * Основной класс моделей
 *
 * @category   Model
 * @package    Model
 * @author     Eugene Myazin <meniam@gmail.com>
 * @version    SVN: $Id$
 */
class Model
{
    /**
     * @var array
     */
    private static $configuration;

    /**
     * Sets the configuration for Propel and all dependencies.
     *
     * @param      mixed The Configuration (array or PropelConfiguration)
     */
    public static function setConfiguration($configuration)
    {
        if (is_array($configuration)) {
            if (isset($configuration['model']) && is_array($configuration['model'])) {
                $configuration = $configuration['model'];
            }
            $configuration = new PropelConfiguration($configuration);
        }
        self::$configuration = $configuration;
    }


}