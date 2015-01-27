<?php

namespace Model\Filter;

/**
 * Получить стем от фразы
 *
 * @see http://ru.wikipedia.org/wiki/%D0%A1%D1%82%D0%B5%D0%BC%D0%BC%D0%B5%D1%80_%D0%9F%D0%BE%D1%80%D1%82%D0%B5%D1%80%D0%B0
 */
class UniqStem extends Stem
{
	public function filter($value)
	{
        $value = parent::filter($value);

        $value = preg_replace('#\s+#', ' ', $value);

		// Сортируем слова по алфавиту
		$wordsArray = array_unique(explode(' ', $value));
		asort($wordsArray);

		return implode(' ', $wordsArray);
	}
}
