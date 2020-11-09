<?php
/*
 * Core
 * Config.php
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


namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * Config
 * @ApiResource()
 *
 */
class Config
{

    /**
     * Unique identifier for the object.
     * @ApiProperty(identifier=true)
     *
     * @var string | null
     * @Groups({"Config:Read"})
     * @SerializedName("resource")
     */
    public $id;

    /**
     * @var string
     * @SerializedName("auth-server-url")
     * @Groups({"Config:Read"})
     *
     */
    public $keycloakAuth;

    /**
     * @var int
     * @Groups({"Config:Read"})
     *
     */
    public $confidentialPort = 0;

    /**
     * @var boolean
     * @SerializedName("public-client")
     * @Groups({"Config:Read"})
     *
     */
    public $publicClient = true;

    /**
     * @var string | null
     * @SerializedName("realm")
     * @Groups({"Config:Read"})
     *
     */
    public $realm = '';

    /**
     * @var string | null
     * @SerializedName("ssl-required")
     * @Groups({"Config:Read"})
     *
     */
    public $sslRequired = 'external';

    /**
     * @return int
     */
    public function getConfidentialPort(): int
    {
        return $this->confidentialPort;
    }

    /**
     * @param int $confidentialPort
     *
     * @return Config
     */
    public function setConfidentialPort(int $confidentialPort): Config
    {
        $this->confidentialPort = $confidentialPort;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     *
     * @return Config
     */
    public function setId(?string $id): Config
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeycloakAuth(): string
    {
        return $this->keycloakAuth;
    }

    /**
     * @param string $keycloakAuth
     *
     * @return Config
     */
    public function setKeycloakAuth(string $keycloakAuth): Config
    {
        $this->keycloakAuth = $keycloakAuth;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRealm(): ?string
    {
        return $this->realm;
    }

    /**
     * @param string|null $realm
     *
     * @return Config
     */
    public function setRealm(?string $realm): Config
    {
        $this->realm = $realm;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSslRequired(): ?string
    {
        return $this->sslRequired;
    }

    /**
     * @param string|null $sslRequired
     *
     * @return Config
     */
    public function setSslRequired(?string $sslRequired): Config
    {
        $this->sslRequired = $sslRequired;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPublicClient(): bool
    {
        return $this->publicClient;
    }

    /**
     * @param bool $publicClient
     *
     * @return Config
     */
    public function setPublicClient(bool $publicClient): Config
    {
        $this->publicClient = $publicClient;
        return $this;
    }
}
