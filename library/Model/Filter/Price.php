<?php

namespace Model\Filter;

class Price extends AbstractFilter
{
	public function filter($value)
	{
        if (is_int($value) || is_float($value)) {
            return (float)$value;
        }

		// Подчищаем
        $value = preg_replace('#(^[^\d\-]+)|([^\d\.\,\-]+)|([^\d]+$)#u', '', $value);

		$commaCount = substr_count($value, ',');
		$dotCount   = substr_count($value, '.');

        // Если нашли и точку и запятую
		if ($commaCount && $dotCount) {
			// Определяем знак который нужно удалять
			// Для этого определяем последний разделитель
			$lastDelimiter = strrpos($value, ',') < strrpos($value, '.') ? '.' : ',';

			$removeChar = $lastDelimiter == ',' ? '.' : ',';

			// Удаляем
			$value = str_replace($removeChar, '', $value);

            // Приводим к правильному чуслу с точкой
            $value = str_replace(array(',', '.'), '.', $value);

            // Удаляем все знаки кроме последнего
            $splittedValue = preg_split('#[\.\,]+#', strval($value));
            $lastValuePart = end($splittedValue);
            if (strlen($lastValuePart) < 3) { // Если конечная часть меньше трех например 10,23
                $values = explode('.', $value);
                $add = array_pop($values);
                $value = implode('', $values) . '.' . $add;
            }
            $result = $value;
		} elseif ($commaCount > 1 || $dotCount > 1) { // Если количество запятых/точек больше одной
            $splittedValue = preg_split('#[\.\,]+#', strval($value));
            $lastValuePart = end($splittedValue);
            if (strlen($lastValuePart) < 3) { // Если конечная часть меньше трех например 10,23
                $values = explode('.', $value);
                $add = array_pop($values);
                $result = implode('', $values) . '.' . $add;
            } else {
                $result = str_replace(array(',', '.'), '', $value);
            }
		} elseif ($commaCount || $dotCount) { // Если запятая одна нужно определять что это за запятая
            $splittedValue = preg_split('#[\.\,]+#', strval($value));
			$lastValuePart = end($splittedValue);
			if (strlen($lastValuePart) < 3) { // Если конечная часть меньше трех например 10,23
                $result = str_replace(array(',', '.'), '.', $value);
			} else {
				$result = str_replace(array(',', '.'), '', $value);
			}
        } else {
			$result = $value;
		}

		return (float)$result;
	}
}
