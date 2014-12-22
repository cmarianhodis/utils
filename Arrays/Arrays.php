<?php
/*
 * Copyright (c) 2011-2014 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 */
namespace BackBee\Utils\Arrays;

/**
 * @author      c.rouillon <charles.rouillon@lp-digital.fr>
 */
class Arrays
{
    const LINE_RETURN = "\n";

    public static function toCsv($values, $separator = ';')
    {
        $return = '';
        foreach ($values as $value) {
            $return .= implode($separator, $value).self::LINE_RETURN;
        }

        return $return;
    }

    public static function toBasicXml($array)
    {
        $return = '';
        foreach ($array as $key => $value) {
            $return .= '<'.$key.'>';
            if (is_array($value)) {
                $return .= static::toBasicXml($value);
            } else {
                $return .= str_replace('&', '&amp;', $value);
            }
            $return .= '</'.$key.'>';
        }

        return $return;
    }

    /**
     * @param  array  $array
     * @return string
     */
    public static function toXml(array $array)
    {
        return str_replace('&', '&amp;', self::convertChild($array));
    }

    /**
     * @param  array $array1
     * @param  array $array2
     * @return array
     */
    public static function array_diff_assoc_recursive(array $array1, array $array2)
    {
        $diff = [];
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key]) || !is_array($array2[$key])) {
                    $diff[$key] = $value;
                } else {
                    $newDiff = self::array_diff_assoc_recursive($value, $array2[$key]);
                    if (!empty($newDiff)) {
                        $diff[$key] = $newDiff;
                    }
                }
            } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * @param  array $array1
     * @param  array $array2
     * @return array
     */
    public static function array_merge_assoc_recursive($array1, $array2)
    {
        foreach ($array2 as $key => $value) {
            if (!array_key_exists($key, $array1)) {
                $array1[$key] = $value;
            } elseif (is_array($value)) {
                $array1[$key] = self::array_merge_assoc_recursive($array1[$key], $array2[$key]);
            } else {
                $array1[$key] = $value;
            }
        }

        return $array1;
    }

    /**
     * @param  [type] $array1
     * @param  [type] $array2
     * @return [type]
     */
    public static function array_remove_assoc_recursive(&$array1, $array2)
    {
        foreach ($array2 as $key => $value) {
            if (!array_key_exists($key, $array1)) {
                return $array1;
            } elseif (is_array($value)) {
                self::array_remove_assoc_recursive($array1[$key], $value);
                if (0 === count($array1[$key])) {
                    unset($array1[$key]);
                }
            } else {
                unset($array1[$key]);
            }
        }
    }

    private static function convertChild(array $array)
    {
        $return = '';
        foreach ($array as $tag => $children) {
            switch ($tag) {
                case 'child':
                    $return .= self::convertChild($children);
                    break;
                case 'children':
                    $return .= self::convertChildren($children);
                    break;
                case 'params':
                    continue;
                default:
                    $return .= self::getTag($tag, $children);
                    $return .= self::getContent($children);
                    $return .= '</'.$tag.'>';
            }
        }

        return $return;
    }

    private static function getTag($key, $values)
    {
        $return = '<'.$key;
        if (is_array($values) && array_key_exists('params', $values)) {
            $return .= self::convertParams($values['params']);
        }

        return $return.'>';
    }

    private static function getContent($values)
    {
        if (is_array($values)) {
            return self::convertChild($values);
        } else {
            return $values;
        }
    }

    private static function convertParams(array $array)
    {
        $return = '';
        foreach ($array as $key => $value) {
            $return .= ' '.$key.'="'.$value.'"';
        }

        return $return;
    }

    private static function convertChildren(array $array)
    {
        $return = '';
        foreach ($array as $tag => $values) {
            foreach ($values as $value) {
                $return .= self::getTag($tag, $value);
                $return .= self::getContent($value);
                $return .= '</'.$tag.'>';
            }
        }

        return $return;
    }

    /**
     * Tests if key:subkey exists in array
     * @param  array                                       $array
     * @param  string                                      $key
     * @param  string                                      $separator
     * @return boolean
     * @throws \BackBee\Exception\InvalidArgumentException
     */
    public static function has(array $array, $key, $separator = ':', &$traverse = array())
    {
        if (!is_string($key)) {
            throw new \Exception('$key parameter as to be a string');
        }
        if (!is_string($separator)) {
            throw new \Exception('$separator parameter as to be a string');
        }
        $traverse = $array;
        $keys = explode($separator, $key);
        foreach ($keys as $key) {
            if (false === is_array($traverse) || false === array_key_exists($key, $traverse)) {
                return false;
            }
            $traverse = $traverse[$key];
        }

        return true;
    }
    /**
     * Gets the value of key:subkey in array, NULL othewise
     * @param  array                                       $array
     * @param  string                                      $key
     * @param  mixed                                       $default
     * @param  string                                      $separator
     * @return mixed
     * @throws \BackBee\Exception\InvalidArgumentException
     */
    public static function get(array $array, $key, $default = null, $separator = ':')
    {
        $traverse = array();
        if (true === self::has($array, $key, $separator, $traverse)) {
            return $traverse;
        }

        return $default;
    }
    /**
     * Implementation of array_column for PHP lower than 5.5
     * @param  array $array      A multi-dimensional array (record set) from which to pull a column of values.
     * @param  type  $column_key The column of values to return. This value may be the integer key of the column
     *                           you wish to retrieve, or it may be the string key name for an associative array.
     *                           It may also be NULL to return complete arrays (useful together with index_key to
     *                           reindex the array).
     * @param  type  $index_key  The column to use as the index/keys for the returned array. This value may be the
     *                           integer key of the column, or it may be the string key name.
     * @return array An array of values representing a single column from the input array.
     */
    public static function array_column(array $array, $column_key = null, $index_key = null)
    {
        if (function_exists('array_column')) {
            return array_column($array, $column_key, $index_key);
        }
        $result = [];
        foreach ($array as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = count($result);
            if (!is_null($index_key) && array_key_exists($index_key, $row)) {
                $key = (string) $row[$index_key];
            }
            if (is_null($column_key)) {
                $result[$key] = $row;
            } elseif (array_key_exists($column_key, $row)) {
                $result[$key] = $row[$column_key];
            }
        }

        return $result;
    }
}
