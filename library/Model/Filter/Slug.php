<?php

namespace Model\Filter;

use Cocur\Slugify\Slugify;

class Slug extends Name
{
	/**
	 * @var Slugify
	 */
	private $slugify;

	public function filter($value)
	{
        $value = parent::filter($value);
        $value = str_replace('&', 'and', $value);

		$value = $this->getSlugify()->slugify($value);
        return trim(preg_replace('#[\-\_\s]+#u', '-', $value), ' -');
	}

	protected function getSlugify()
	{
		if (!$this->slugify) {
			$this->slugify = new Slugify();
		}

		return $this->slugify;
	}
}
