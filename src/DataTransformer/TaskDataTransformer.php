<?php
/*
 * Core
 * FolderDataTransformer.php
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

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\DataProvider\CommonDataProvider;
use App\Entity\Target;
use App\Entity\Task;
use App\Entity\User;
use App\Traits\stateableEntity;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class TaskDataTransformer extends CommonDataProvider implements DataTransformerInterface
{

    private $context;
    private $inputData;

    private function createTask(Task $task): Task
    {

        $task->setResponsibleId($this->inputData['responsible']['id'] ?? null);
        $informed    = $this->inputData['informed'] ?? [];
        $informedIds = [];
        $task->removeInformed();
        foreach ($informed as $userData) {
            $informedIds[] = $userData['id'];
            $user          = new User();
            $user->populateUser($userData);
            $task->addInformed($user);
        }
        $task->setInformedIds(implode('|', $informedIds));
        return $task;
    }

    private function done(Task &$task): Task
    {
        // only responsible can set task to DONE state
        if ($task->getResponsibleId() !== $this->getUser()->getUsername()) {
            throw new AccessDeniedHttpException();
        }
        $this->context['groups'][] = "State";
        $task->setState(stateableEntity::getStateDone());
        return $task;
    }

    private function getTask(Task &$task): Task
    {
        $kcuser = $this->getKeycloakConnector()->getUser($task->getResponsibleId());
        if (empty($kcuser)) {
            throw new UnprocessableEntityHttpException("responsible user " . $task->getResponsibleId() . " do not exists");
        }
        $user = new User();
        $user->populateUser($kcuser);
        $task->setResponsible($user);

        $informedIds = explode('|', $task->getInformedIds());
        foreach ($informedIds as $informedId) {
            if (!empty($informedId)) {
                $kcuser = $this->getKeycloakConnector()->getUser($informedId);
                $user   = new User();
                $user->populateUser($kcuser);
                $task->addInformed($user);
            }
        }

        $targetId = $task->getQuestionnaire()->getFolder()->getTarget();
        $kcTarget = $this->getKeycloakConnector()->getGroup($targetId) ?? $this->getKeycloakConnector()->getUser($targetId);
        if (!empty($kcTarget)) {
            $target = new Target();
            $target->populateTarget($kcTarget);
            $task->setTarget($target);
        } else {
            throw new UnprocessableEntityHttpException("target do not exists " . $targetId . " do not exists");
        }
        return $task;
    }

    private function update(Task &$task): Task
    {
        return $task;
    }

    private function updateInputData(array &$task)
    {
        // fill user uuids with user objects
        $task['responsibleId'] = $task['responsible']['username'] ?? null;
        if (!$this->getKeycloakConnector()->userExists($task['responsibleId'])) {
            throw new UnprocessableEntityHttpException("responsible user do not exists");
        }

        $informed = $task['informed'] ?? [];

        $informedIds = [];
        foreach ($informed as $user) {
            if (!$this->getKeycloakConnector()->userExists($user['username'])) {
                throw new UnprocessableEntityHttpException("informed user " . $user['username'] . " do not exists");
            }
            $informedIds[] = $user['username'];
        }
        $task['informedIds'] = implode('|', $informedIds);
    }

    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        if (Task::class !== $to) {
            // just transform task
            return false;
        }
        if (is_array($data)) {
            // save inutData to fix object before saving in DB
            $this->updateInputData($data);
            $this->inputData = $data;
        }
        if (($context['input']['class'] ?? null) !== null
            && in_array($context[$context['operation_type'] . '_operation_name'],
                        ['post', 'patch', 'create', 'update', 'done'])
        ) {
            return true;
        }

        if ($data instanceof Task
            && in_array($context[$context['operation_type'] . '_operation_name'], ['get', 'submit'])
        ) {
            return true;
        }


        return false;

    }

    /**
     * @param object $data
     * @param string $to
     * @param array  $context
     *
     * @return object
     */
    public function transform($data, string $to, array $context = [])
    {
        if (!$data instanceof Task) {
            return $data;
        }
        $task          = $data;
        $this->context = $context;


        switch ($context[$context['operation_type'] . '_operation_name']) {
            case 'create':
                // it's a folder creation
                $task = $this->createTask($task);
                break;

            case 'submit':
            case 'get':
                $task = $this->getTask($task);
                break;

            case 'update':
                // it's folder update
                $task = $this->update($task);
                break;

            case 'done':
                // it's folder update
                $task = $this->done($task);
                break;

            default:
                // what?
                // an unknown operation?
                break;
        }
        return $task;
    }
}
