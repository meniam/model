<?php

namespace Model;

class Translator
{
    /**
     * @var Zend/Translate
     */
    protected $_translator;

    /**
     * Global default translation adapter
     * @var Zend_Translate
     */
    protected static $_translatorDefault;

    /**
     * is the translator disabled?
     * @var bool
     */
    protected $_translatorDisabled = false;

    /**
     * Set translator object
     *
     * @param  Zend_Translate|Zend_Translate_Adapter|null $translator
     * @throws Model_Exception
     * @return Zend_Form
     */
    public function setTranslator($translator = null)
    {
        if (null === $translator) {
            $this->_translator = null;
        } elseif ($translator instanceof Zend_Translate_Adapter) {
            $this->_translator = $translator;
        } elseif ($translator instanceof Zend_Translate) {
            $this->_translator = $translator->getAdapter();
        } else {
            throw new Model_Exception('Invalid translator specified');
        }

        return $this;
    }

    /**
     * Set global default translator object
     *
     * @param  Zend_Translate|Zend_Translate_Adapter|null $translator
     * @return void
     */
    public static function setDefaultTranslator($translator = null)
    {
        if (null === $translator) {
            self::$_translatorDefault = null;
        } elseif ($translator instanceof Zend_Translate_Adapter) {
            self::$_translatorDefault = $translator;
        } elseif ($translator instanceof Zend_Translate) {
            self::$_translatorDefault = $translator->getAdapter();
        } else {
            throw new Model_Exception('Invalid translator specified');
        }
    }

    /**
     * Retrieve translator object
     *
     * @return Zend_Translate_Adapter
     */
    public function getTranslator()
    {
        if ($this->translatorIsDisabled()) {
            return null;
        }

        if (null === $this->_translator) {
            return self::getDefaultTranslator();
        }

        return $this->_translator;
    }

    /**
     * Does this form have its own specific translator?
     *
     * @return bool
     */
    public function hasTranslator()
    {
        return (bool)$this->_translator;
    }

    /**
     * Get global default translator object
     *
     * @return Zend_Translate_Adapter
     */
    public static function getDefaultTranslator()
    {
        if (null === self::$_translatorDefault) {
            if (Zend_Registry::isRegistered('Zend_Translate')) {
                $translator = Zend_Registry::get('Zend_Translate');
                if ($translator instanceof Zend_Translate_Adapter) {
                    return $translator;
                } elseif ($translator instanceof Zend_Translate) {
                    return $translator->getAdapter();
                }
            } else {
                throw new Exception("Registry key 'Zend_Translate' is not defined.");
            }
        }
        return self::$_translatorDefault;
    }

    /**
     * Is there a default translation object set?
     *
     * @return boolean
     */
    public static function hasDefaultTranslator()
    {
        return (bool)self::$_translatorDefault;
    }

    /**
     * Indicate whether or not translation should be disabled
     *
     * @param  bool $flag
     * @return Zend_Form
     */
    public function setDisableTranslator($flag)
    {
        $this->_translatorDisabled = (bool) $flag;
        return $this;
    }

    /**
     * Is translation disabled?
     *
     * @return bool
     */
    public function translatorIsDisabled()
    {
        return $this->_translatorDisabled;
    }
    
    /**
     * Translates the given string
     * returns the translation
     *
     * @see Zend_Locale
     * @param  string|array       $messageId Translation string, or Array for plural translations
     * @param  string|Zend_Locale $locale    (optional) Locale/Language to use, identical with
     *                                       locale identifier, @see Zend_Locale for more information
     * @return string
     */
    public static function translate($messageId, $locale = null)
    {
        return self::getDefaultTranslator()->translate($messageId, $locale);
    }

}