<?php

namespace Model\Code\Generator;


class FileGenerator extends \Zend\Code\Generator\FileGenerator
{
    /**
     * @param string     $name
     * @param string|null $as
     *
     * @return $this
     */
    public function addUse($name, $as = null)
    {
        $existed = $this->getUses($as);
        $name = '\\'. ltrim($name, '\\');

        if (!in_array(array($name, $as), $existed)) {
            $this->setUse($name, $as);
        }

        return $this;
    }

}
