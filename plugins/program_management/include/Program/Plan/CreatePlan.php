<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Program\Plan;

use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;

interface CreatePlan
{
    /**
     * @param int[] $trackers_id
     * @param non-empty-list<string> $can_possibly_prioritize_ugroups
     *
     * @throws CannotPlanIntoItselfException
     * @throws PlanTrackerException
     * @throws ProgramAccessException
     * @throws ProjectIsNotAProgramException
     * @throws ProgramTrackerException
     * @throws InvalidProgramUserGroup
     */
    public function create(
        \PFUser $user,
        int $project_id,
        int $program_increment_id,
        array $trackers_id,
        array $can_possibly_prioritize_ugroups,
        ?string $custom_label,
        ?string $custom_sub_label
    ): void;
}
