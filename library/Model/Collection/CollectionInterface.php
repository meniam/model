<?php

namespace Model\Collection;

use Model\Entity\EntityInterface as Entity;

/**
 * Интерфейс набора
 *
 * @category   Model
 * @package    Collection
 * @author     Eugene Myazin <meniam@gmail.com>
 * @since      26.12.12 10:23
 * @copyright  2008-2012 ООО "Америка"
 * @version    SVN: $Id$
 */
interface CollectionInterface
{
    public function toArray();
    public function getIdsAsArray();
    public function contains($callback);
    public function containsEntity(\Model\Entity\EntityInterface $entity);
    public function diff(\Model\Collection\CollectionInterface $collection);
    public function setPager($pager);

    /**
     * @return Entity
     */
    public function current();
}