<?php
/*
 * Core
 * ConfigDataProvider.php
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
 * The above copyright notice and this permission notice shall be included in all copies
 * or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT
 * NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Config;
use App\Service\CCETools;
use Doctrine\ORM\EntityNotFoundException;
use Generator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


/**
 * Class ConfigDataProvider
 */
final class ConfigDataProvider extends CommonDataProvider implements CollectionDataProviderInterface, ItemDataProviderInterface, RestrictedDataProviderInterface
{

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     *
     * @return \Generator
     */
    public function getCollection(string $resourceClass, string $operationName = null): Generator
    {
        foreach (['CCE_viewclient', 'CCE_adminclient'] as $client) {
            $data = [
                "auth-server-url"   => $this->getKeycloakUrl().'/auth',
                "confidential-port" => 0,
                "public-client"     => true,
                "realm"             => $this->getKeycloakRealm(),
                "resource"          => CCETools::param($this->getParameters(), $client),
                "ssl-required"      => "external"
            ];
            yield $data;
        }
        return [];
    }

    public function getItem(string $resourceClass, $id, string $operationName = null, array $context = []): ?Config
    {
        $id=strtolower($id);
        $client=CCETools::param($this->getParameters(), "CCE_${id}client", 'none');

        if($client=='none'){
            throw new NotFoundHttpException(sprintf('The client "%s" does not exist.', $id));
        }
        $config=new Config();

        return $this->populateConfig($config, $client);

    }

    private function populateConfig(Config &$config, $clientName): Config
    {
        $config->setId($clientName);
        $config->setKeycloakAuth($this->getKeycloakClientAuthUrl());
        $config->setRealm($this->getKeycloakRealm());
        return $config;
    }

    /**
     * @param string      $resourceClass
     * @param string|null $operationName
     * @param array       $context
     *
     * @return bool
     */
    public function supports(
        string $resourceClass,
        string $operationName = null,
        array $context = []
    ): bool {
        return Config::class === $resourceClass;
    }
}
