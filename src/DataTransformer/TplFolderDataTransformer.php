<?php
/*
 * Core
 * TplFolderDataTransformer.php
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

namespace App\DataTransformer;

use ApiPlatform\Core\DataTransformer\DataTransformerInterface;
use App\CentralAdmin\KeycloakConnector;
use App\DataProvider\KeycloakDataProvider;
use App\Entity\Target;
use App\Entity\TplFolder;

final class TplFolderDataTransformer extends KeycloakDataProvider implements DataTransformerInterface
{

    /**
     * {@inheritdoc}
     */
    public function transform($data, string $to, array $context = [])
    {

        $permissions_right = '';
        if (!empty($this->getRequest()->get('permissions_right'))) {
            $permissions_right = strtoupper($this->getRequest()->get('permissions_right'));
        }

        if (empty($this->getUser()) || $this->getAppClient() === \App\Service\CCETools::param($this->getParameters(),
                                                                                              'CCE_adminclient')) {
            return $data;
        }
        $toInterval   = '9999-01-01';
        $fromInterval = '1900-01-01';
        if (!empty($this->getRequest()->get('fromdate'))) {
            $fromInterval = $this->getRequest()->get('fromdate');
        }

        if (!empty($this->getRequest()->get('todate'))) {
            $toInterval = $this->getRequest()->get('todate');
        }


        // get min and max dates from calendars
        $expectedFolders = 0;
        $periods         = [];
        foreach ($data->getCalendars() as $calendar) {

            $data->setEndDate(max($calendar->getEnd(), $data->getEndDate()));
            // start cannot be null, so force it to first end value, if it is null
            $data->setStartDate($data->getStartDate() ?? $data->getEndDate());
            $data->setStartDate(min($calendar->getStart(), $data->getStartDate()));

            $data->setPeriodEnd(max($calendar->getPeriodEnd(), $data->getPeriodEnd()));
            // periodStart cannot be null, so force it to first end value, if it is null
            $data->setPeriodStart($data->getPeriodStart() ?? $data->getPeriodEnd());
            $data->setPeriodStart(min($calendar->getPeriodStart(), $data->getPeriodStart()));
            if ($context[$context['operation_type'] . '_operation_name'] === 'getexpectation' || $context[$context['operation_type'] . '_operation_name'] === 'periods') {
                $startInterval = new \DateTime($fromInterval);
                $startInterval = $startInterval > $calendar->getStart() ? $startInterval : $calendar->getStart();
                $endInterval   = new \DateTime($toInterval);
                $endInterval   = $endInterval < $calendar->getEnd() ? $endInterval : $calendar->getEnd();
                $calendar->setStart($startInterval);
                $calendar->setEnd($endInterval);
                $expectedFolders += $calendar->getPeriodCount();
                $periods         = $calendar->getPeriods();
            }
        }

        // calculate periods
        $data->setExpectedFolders($expectedFolders * $data->getMinFolders());
        foreach ($periods as $period) {
            $data->addPeriod($period);
        }

        // get targets roles
        foreach ($data->getFolderTargets() as $folderTarget) {
            $targetRoles[] = $folderTarget->getRole();
        }
        // and extract targets having those roles
        $kcTargets = $this->getKeycloakConnector()->getUserGroups($this->getUserId(), KeycloakConnector::DOWNMEMBERSHIP,
                                                                  $targetRoles);
        // create targets objects
        foreach ($kcTargets as &$kcTarget) {
            $target = new Target();
            $target->setId($kcTarget['id']);
            $target->setName($kcTarget['name']);
            $target->setParent($kcTarget['parent']);
            $target->setType($kcTarget['type']);
            $target->setRights([$permissions_right]);
            $data->addTarget($target);
        }

        // remove tplFolderPermissions that related to roles that are not affected to user
        $permissions=$data->getPermissions();
        foreach ($permissions as $permission) {
            if(!in_array(KeycloakConnector::toSymfonyRole($permission->getRole()), $this->getUser()->getRoles())){
                $data->removePermission($permission);
            }
        }
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsTransformation($data, string $to, array $context = []): bool
    {
        return TplFolder::class === $to
            && $data instanceof TplFolder
            && ($context['output']['class'] ?? null) !== null
            && !(empty($this->getUser())
                || $this->getAppClient() === \App\Service\CCETools::param($this->getParameters(), 'CCE_adminclient'));
    }
}
