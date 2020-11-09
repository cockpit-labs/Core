<?php
/*
 * Core
 * User.php
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
use App\Traits\resourceableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * User
 * @ApiResource()
 *
 */
class User
{

    /**
     * add a resource (entity name) and iri field automatically filled
     */
    use resourceableEntity;

    /**
     * Unique identifier for the object.
     * @ApiProperty(identifier=true)
     *
     * @var string | null
     * @Groups({"User:Read"})
     * @Groups({"FolderTpl:Read"})
     * @Groups({"FolderTpl:Update"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     */
    public $id;

    /**
     * @var string
     * @Groups({"User:Read"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     *
     */
    public $username;

    /**
     * @var string
     * @Groups({"User:Read"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     *
     */
    public $firstname;

    /**
     * @var string
     * @Groups({"User:Read"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     *
     */
    public $lastname;

    /**
     * @var string
     * @Groups({"User:Read"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     *
     */
    public $email;

    /**
     * @var string
     *
     * @Groups({"FolderTpl:Read"})
     * @Groups({"FolderTplTarget:Read"})
     * @Groups({"Folder:Read"})
     * @Groups({"Folder:Update"})
     * @Groups({"Questionnaire:Update"})
     * @Groups({"Questionnaire:Read"})
     * @Groups({"Task:Read"})
     * @Groups({"Task:Update"})
     */
    public $uuid;

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return $this
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param string|null $firstname
     *
     * @return $this
     */
    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;

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
     * @return $this
     */
    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    /**
     * @param string|null $lastname
     *
     * @return $this
     */
    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     *
     * @return $this
     */
    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * @param string|null $uuid
     *
     * @return $this
     */
    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @param array $user
     */
    public function populateUser(array $user)
    {
        $user = array_change_key_case($user);
        $this->setId($user['id']);
        $this->setUuid($user['id']);
        $this->setUsername($user['username']);
        $this->setFirstname($user['firstname']);
        $this->setLastname($user['lastname']);
        $this->setEmail($user['email']);
    }


}
