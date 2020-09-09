<?php
/*
 * Core
 * CCETools.php
 *
 * Copyright (c) 2020 Sentinelo
 *
 * @author  Christophe AGNOLA
 * @license MIT License (https://mit-license.org)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the “Software”), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT
 * NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace App\Service;


use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;
use Symfony\Component\HttpFoundation\ParameterBag;

class CCETools
{
    public static function filename($param, $name, $default = '')
    {
        $result = $default;
        if (is_a($param, Container::class)) {
            $param = $param->getParameterBag();
        }
        if (is_a($param, Parameter::class)
            || is_a($param, ParameterBag::class)
            || is_a($param, FrozenParameterBag::class)
            || is_a($param, ContainerBag::class)
            && $param->has($name)) {
            $result = file_exists($param->get($name)) ? $param->get($name) : $default;
        }

        return $result;
    }

    public static function param($param, $name, $default = '')
    {
        $result = $default;
        if (is_a($param, Container::class)) {
            $param = $param->getParameterBag();
        }
        if (is_a($param, Parameter::class)
            || is_a($param, ParameterBag::class)
            || is_a($param, FrozenParameterBag::class)
            || is_a($param, ContainerBag::class)
            && $param->has($name)) {
            $result = file_exists($param->get($name)) ? file_get_contents($param->get($name)) : $param->get($name);
            if (empty($result)) {
                $result = $default;
            }
        }

        return $result;
    }
}
