<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Workspace\UnusedComponentCleaner;

final class WorkspaceDAO extends DataAccessObject implements UnusedComponentCleaner
{
    public function dropUnusedComponents(): void
    {
        $sql = 'DELETE
                    plugin_program_management_plan.*,
                    plugin_program_management_can_prioritize_features.*,
                    plugin_program_management_team_projects.*,
                    plugin_program_management_pending_mirrors.*,
                    plugin_program_management_explicit_top_backlog.*,
                    plugin_program_management_workflow_action_add_top_backlog.*,
                    plugin_program_management_label_program_increment.*
                FROM `groups`
                LEFT JOIN tracker ON (tracker.group_id = `groups`.group_id)
                LEFT JOIN plugin_program_management_plan ON (plugin_program_management_plan.program_increment_tracker_id = tracker.group_id OR plugin_program_management_plan.plannable_tracker_id = tracker.group_id)
                LEFT JOIN plugin_program_management_can_prioritize_features ON (plugin_program_management_can_prioritize_features.program_increment_tracker_id = plugin_program_management_plan.program_increment_tracker_id)
                LEFT JOIN plugin_program_management_team_projects ON (plugin_program_management_team_projects.team_project_id = `groups`.group_id OR plugin_program_management_team_projects.program_project_id = `groups`.group_id)
                LEFT JOIN tracker_artifact ON (tracker_artifact.tracker_id = tracker.id)
                LEFT JOIN plugin_program_management_pending_mirrors ON (plugin_program_management_pending_mirrors.program_artifact_id = tracker_artifact.id)
                LEFT JOIN plugin_program_management_explicit_top_backlog ON (plugin_program_management_explicit_top_backlog.artifact_id = tracker_artifact.id)
                LEFT JOIN tracker_workflow ON (tracker_workflow.tracker_id = tracker.id)
                LEFT JOIN tracker_workflow_transition ON (tracker_workflow_transition.workflow_id = tracker_workflow.workflow_id)
                LEFT JOIN plugin_program_management_workflow_action_add_top_backlog ON (plugin_program_management_workflow_action_add_top_backlog.transition_id = tracker_workflow_transition.transition_id)
                LEFT JOIN plugin_program_management_label_program_increment ON (plugin_program_management_label_program_increment.program_increment_tracker_id = tracker.group_id)
                WHERE `groups`.status = "D"';

        $this->getDB()->run($sql);
    }
}
