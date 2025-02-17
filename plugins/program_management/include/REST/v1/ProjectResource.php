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

namespace Tuleap\ProgramManagement\REST\v1;

use BackendLogger;
use Luracast\Restler\RestException;
use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\Cardwall\BackgroundColor\BackgroundColorBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\ArtifactsExplicitTopBacklogDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\ProcessTopBacklogChange;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\CanPrioritizeFeaturesDAO;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Program\PlanningAdapter;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\CannotManipulateTopBacklog;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogChange;
use Tuleap\ProgramManagement\Program\Backlog\TopBacklog\TopBacklogUpdater;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsDAO;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\ProgramIncrementsRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureRepresentationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanDao;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PlanTrackerException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Adapter\Program\ProgramDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\BackgroundColorRetriever;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeaturesDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureElementsRetriever;
use Tuleap\ProgramManagement\Adapter\Program\ProgramUserGroupBuildAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerAdapter;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ProgramManagement\Adapter\Team\TeamAdapter;
use Tuleap\ProgramManagement\Adapter\Team\TeamDao;
use Tuleap\ProgramManagement\Adapter\Team\TeamException;
use Tuleap\ProgramManagement\Program\Backlog\Feature\RetrieveFeatures;
use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrementBuilder;
use Tuleap\ProgramManagement\Program\Plan\CannotPlanIntoItselfException;
use Tuleap\ProgramManagement\Program\Plan\CreatePlan;
use Tuleap\ProgramManagement\Program\Plan\InvalidProgramUserGroup;
use Tuleap\ProgramManagement\Program\Plan\PlanCreator;
use Tuleap\ProgramManagement\Team\Creation\TeamCreator;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDecoratorRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

final class ProjectResource extends AuthenticatedResource
{
    private const MAX_LIMIT = 50;

    /**
     * @var RetrieveFeatures
     */
    private $features_retriever;
    /**
     * @var TeamCreator
     */
    private $team_creator;
    /**
     * @var CreatePlan
     */
    private $plan_creator;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var ProgramIncrementBuilder
     */
    private $program_increments_builder;

    public function __construct()
    {
        $this->user_manager   = \UserManager::instance();
        $plan_dao             = new PlanDao();
        $tracker_adapter      = new ProgramTrackerAdapter(\TrackerFactory::instance());
        $project_manager      = \ProjectManager::instance();
        $program_dao          = new ProgramDao();
        $explicit_backlog_dao = new ExplicitBacklogDao();
        $build_program        = new ProgramAdapter(
            $project_manager,
            new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                \EventManager::instance()
            ),
            $program_dao
        );
        $this->plan_creator   = new PlanCreator(
            $build_program,
            $tracker_adapter,
            new ProgramUserGroupBuildAdapter(new UserGroupRetriever(new \UGroupManager())),
            $plan_dao
        );

        $team_adapter       = new TeamAdapter($project_manager, $program_dao, $explicit_backlog_dao);
        $team_dao           = new TeamDao();
        $this->team_creator = new TeamCreator($build_program, $team_adapter, $team_dao);

        $artifact_factory                 = \Tracker_ArtifactFactory::instance();
        $form_element_factory             = \Tracker_FormElementFactory::instance();
        $this->features_retriever         = new FeatureElementsRetriever(
            $build_program,
            new FeaturesDao(),
            new FeatureRepresentationBuilder(
                $artifact_factory,
                $form_element_factory,
                new BackgroundColorRetriever(new BackgroundColorBuilder(new BindDecoratorRetriever())),
                new ArtifactsLinkedToParentDao(),
                new PlanningAdapter(\PlanningFactory::build())
            )
        );
        $this->program_increments_builder = new ProgramIncrementBuilder(
            $build_program,
            new ProgramIncrementsRetriever(
                new ProgramIncrementsDAO(),
                $artifact_factory,
                new TimeframeBuilder(
                    new SemanticTimeframeBuilder(new SemanticTimeframeDao(), $form_element_factory),
                    BackendLogger::getDefaultLogger()
                )
            )
        );
    }

    /**
     * @url OPTIONS {id}/program_plan
     *
     * @param int $id Id of the project
     */
    public function options(int $id): void
    {
        Header::allowOptionsPut();
    }

    /**
     * Define a program plan
     *
     * Define the program increment and the tracker plannable inside
     * <br/>
     * <strong>"custom_label"</strong> and <strong>"custom_sub_label"</strong> are optional.
     * They will be used to have a custom label for Program Increment in UI.
     * <br/>
     * If there are not used, by default:
     * <pre>
     * {<br/>
     * &nbsp;"custom_label": "Program Increments",<br/>
     * &nbsp;"custom_sub_label": "program increment"<br/>
     * }
     * </pre>
     *
     * @url    PUT {id}/program_plan
     *
     * @param int                                  $id Id of the program project
     * @param ProjectResourcePutPlanRepresentation $representation {@from body}
     *
     *
     * @throws RestException 404
     * @throws RestException 400
     */
    protected function putPlan(int $id, ProjectResourcePutPlanRepresentation $representation): void
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $this->plan_creator->create(
                $user,
                $id,
                $representation->program_increment_tracker_id,
                $representation->plannable_tracker_ids,
                $representation->permissions->can_prioritize_features,
                $representation->custom_label,
                $representation->custom_sub_label
            );
        } catch (ProjectIsNotAProgramException | CannotPlanIntoItselfException | PlanTrackerException | ProgramTrackerException | InvalidProgramUserGroup $e) {
            throw new RestException(400, $e->getMessage());
        } catch (ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        }
    }

    /**
     * Define team projects of a program
     *
     * @url    PUT {id}/program_teams
     *
     * @param int                                   $id Id of the program project
     * @param ProjectResourcePutTeamsRepresentation $representation {@from body}
     *
     *
     * @throws RestException 404
     * @throws RestException 400
     */
    protected function putTeam(int $id, ProjectResourcePutTeamsRepresentation $representation): void
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $this->team_creator->create(
                $user,
                $id,
                $representation->team_ids
            );
        } catch (TeamException $e) {
            throw new RestException(400, $e->getMessage());
        } catch (ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        }
    }

    /**
     * Get program backlog
     *
     * Get the to be planned elements of a program
     *
     * @url GET {id}/program_backlog
     * @access hybrid
     *
     * @param int $id Id of the program
     * @param int $limit Number of elements displayed per page {@min 0} {@max 50}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return FeatureRepresentation[]
     *
     * @throws RestException 401
     * @throws RestException 400
     */
    public function getBacklog(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $elements = $this->features_retriever->retrieveFeaturesToBePlanned($id, $user);

            Header::sendPaginationHeaders($limit, $offset, count($elements), self::MAX_LIMIT);

            return array_slice($elements, $offset, $limit);
        } catch (\Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        } catch (\Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException $e) {
            throw new RestException(400, $e->getMessage());
        }
    }

    /**
     * Manipulate the program backlog
     *
     * @url PATCH {id}/program_backlog
     *
     * @param int $id ID of the program
     * @param BacklogPatchRepresentation $backlog_patch_representation {@from body}
     *
     * @throws RestException 401
     * @throws RestException 400
     */
    protected function patchBacklog(int $id, BacklogPatchRepresentation $backlog_patch_representation): void
    {
        $user = $this->user_manager->getCurrentUser();

        $feature_ids_to_remove = [];
        foreach ($backlog_patch_representation->remove as $feature_to_remove) {
            $feature_ids_to_remove[] = $feature_to_remove->id;
        }
        $feature_ids_to_add = [];
        foreach ($backlog_patch_representation->add as $feature_to_add) {
            $feature_ids_to_add[] = $feature_to_add->id;
        }

        $project_access_checker = new ProjectAccessChecker(
            new RestrictedUserCanAccessProjectVerifier(),
            \EventManager::instance()
        );

        $project_manager     = \ProjectManager::instance();
        $top_backlog_updater = new TopBacklogUpdater(
            new ProgramAdapter($project_manager, $project_access_checker, new ProgramDao()),
            new ProcessTopBacklogChange(
                \Tracker_ArtifactFactory::instance(),
                new PrioritizeFeaturesPermissionVerifier(
                    $project_manager,
                    $project_access_checker,
                    new CanPrioritizeFeaturesDAO()
                ),
                new ArtifactsExplicitTopBacklogDAO(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            )
        );

        try {
            $top_backlog_updater->updateTopBacklog($id, new TopBacklogChange($feature_ids_to_add, $feature_ids_to_remove), $user);
        } catch (ProgramAccessException | CannotManipulateTopBacklog $e) {
            throw new RestException(404);
        } catch (ProjectIsNotAProgramException $e) {
            throw new RestException(403, $e->getMessage());
        }
    }

    /**
     * @url OPTIONS {id}/program_backlog
     *
     * @param int $id Id of the project
     */
    public function optionsBacklog(int $id): void
    {
        Header::allowOptionsGetPatch();
    }

    /**
     * @url OPTIONS {id}/program_increments
     *
     * @param int $id ID of the program
     */
    public function optionsProgramIncrements(int $id): void
    {
        Header::allowOptionsGet();
    }

    /**
     * Get program increments
     *
     * @url GET {id}/program_increments
     * @access hybrid
     *
     * @param int $id ID of the program
     * @param int $limit Number of elements displayed per page {@min 1} {@max 50}
     * @param int $offset Position of the first element to display {@min 0}
     *
     * @return ProgramIncrementRepresentation[]
     *
     * @throws RestException 401
     * @throws RestException 400
     */
    public function getProgramIncrements(int $id, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $user = $this->user_manager->getCurrentUser();
        try {
            $program_increments = $this->program_increments_builder->buildOpenProgramIncrements($id, $user);
        } catch (\Tuleap\ProgramManagement\Adapter\Program\Plan\ProgramAccessException $e) {
            throw new RestException(404, $e->getMessage());
        } catch (\Tuleap\ProgramManagement\Adapter\Program\Plan\ProjectIsNotAProgramException $e) {
            throw new RestException(400, $e->getMessage());
        }

        Header::sendPaginationHeaders($limit, $offset, count($program_increments), self::MAX_LIMIT);

        $representations = [];
        foreach (array_slice($program_increments, $offset, $limit) as $program_increment) {
            $representations[] = ProgramIncrementRepresentation::fromProgramIncrement($program_increment);
        }
        return $representations;
    }
}
