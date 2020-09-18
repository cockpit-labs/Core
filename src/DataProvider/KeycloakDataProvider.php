<?php
/*
 * Core
 * KeycloakDataProvider.php
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

namespace App\DataProvider;

use ApiPlatform\Core\Api\IriConverterInterface;
use App\CentralAdmin;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class KeycloakDataProvider
{
    /**
     * @var \Symfony\Component\HttpFoundation\Request|null
     */
    private $request;
    /**
     * @var \Twig\Environment;
     */
    private $twig;
    /**
     * @var \League\Flysystem\FilesystemInterface
     */
    private $mediaFS;
    /**
     * @var CentralAdmin\KeycloakConnector
     */
    private $keycloakConnector;
    /**
     * @var \ApiPlatform\Core\Api\IriConverterInterface
     */
    private $iriService;
    /**
     * @var String
     */
    private $keycloakUrl;
    /**
     * @var String
     */
    private $keycloakSecret;
    /**
     * @var String
     */
    private $keycloakClient;
    /**
     * @var String
     */
    private $keycloakRealm;
    /**
     * @var string
     */
    private $userId = '';
    /**
     * @var UserInterface
     */
    private $user;
    /**
     * @var string
     */
    private $appClient = '';
    /**
     * @var NormalizerInterface|null
     */
    private $normalizer = null;
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ParameterBagInterface|null
     */
    private $parameters;
    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    private $kernel;

    public function __construct(
        Security $security,
        ParameterBagInterface $params,
        IriConverterInterface $iriService,
        NormalizerInterface $normalizer,
        EntityManagerInterface $entityManager,
        \Twig\Environment $twig,
        FilesystemInterface $mediafsFilesystem,
        RequestStack $requestStack,
        KernelInterface $kernel
    ) {
        $this->mediaFS       = $mediafsFilesystem;
        $this->normalizer    = $normalizer;
        $this->entityManager = $entityManager;
        $this->parameters    = $params;
        $this->twig          = $twig;
        $this->kernel        = $kernel;
        if ($security->getToken() != null) {
            // get user
            $this->user      = $security->getToken()->getUser();
            $this->appClient = $this->user->getClient();
        }
        // set keycloak env
        $this->keycloakSecret = \App\Service\CCETools::param($params, 'CCE_KEYCLOAKSECRET');
        $this->keycloakUrl    = \App\Service\CCETools::param($params, 'CCE_KEYCLOAKURL');
        $this->keycloakClient = \App\Service\CCETools::param($params, 'CCE_coreclient');
        $this->keycloakRealm  = \App\Service\CCETools::param($params, 'CCE_KEYCLOAKREALM');
        $this->iriService     = $iriService;
        $this->request        = $requestStack->getCurrentRequest();
    }

    /**
     * @return string
     */
    public function getAppClient(): string
    {
        return $this->appClient;
    }

    /**
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @return \ApiPlatform\Core\Api\IriConverterInterface
     */
    public function getIriService(): IriConverterInterface
    {
        return $this->iriService;
    }

    /**
     * @return \Symfony\Component\HttpKernel\KernelInterface
     */
    public function getKernel(): \Symfony\Component\HttpKernel\KernelInterface
    {
        return $this->kernel;
    }

    /**
     * @return CentralAdmin\KeycloakConnector
     */
    public function getKeycloakConnector(): CentralAdmin\KeycloakConnector
    {
        if (empty($this->keycloakConnector)) {
            $this->keycloakConnector = new CentralAdmin\KeycloakConnector(
                $this->keycloakUrl,
                $this->keycloakSecret,
                $this->keycloakClient,
                $this->keycloakRealm
            );
        }
        return $this->keycloakConnector;
    }

    /**
     * @return \League\Flysystem\FilesystemInterface
     */
    public function getMediaFS(): \League\Flysystem\FilesystemInterface
    {
        return $this->mediaFS;
    }

    /**
     * @return \Symfony\Component\Serializer\Normalizer\NormalizerInterface|null
     */
    public function getNormalizer(): ?NormalizerInterface
    {
        return $this->normalizer;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface|null
     */
    public function getParameters(): ?ParameterBagInterface
    {
        return $this->parameters;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request|null
     */
    public function getRequest(): ?\Symfony\Component\HttpFoundation\Request
    {
        return $this->request;
    }

    /**
     * @return \Twig\Environment
     */
    public function getTwig(): \Twig\Environment
    {
        return $this->twig;
    }

    /**
     * @return object|string
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        if (empty($this->userId)) {
            $this->userId = $this->getUser()->getId();
        }
        return $this->userId;
    }
}
