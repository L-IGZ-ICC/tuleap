<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Adapter\Program\Feature;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Monolog\Test\TestCase;
use Psr\Log\Test\TestLogger;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Content\ContentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureInProgramIncrementPlanner;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\ArtifactsLinkedToParentDao;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\FeatureToLinkBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Feature\Links\UserStoriesLinkedToMilestoneBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Plan\PrioritizeFeaturesPermissionVerifier;
use Tuleap\ProgramManagement\Adapter\Team\MirroredMilestones\MirroredMilestoneRetriever;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\FeaturePlanChange;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\RetrieveProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\Feature\FieldData;
use Tuleap\ProgramManagement\Program\Backlog\Feature\ProgramIncrementChanged;
use Tuleap\ProgramManagement\Team\MirroredMilestone\MirroredMilestone;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Tracker\Artifact\Artifact;

final class FeatureInProgramIncrementPlannerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ArtifactsLinkedToParentDao
     */
    private $features_linked_to_milestone_builder;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ContentDao
     */
    private $content_dao;

    /**
     * @var FeatureInProgramIncrementPlanner
     */
    private $planner;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|MirroredMilestoneRetriever
     */
    private $mirrored_milestone_retriever;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|FeatureToLinkBuilder
     */
    private $feature_to_plan_builder;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PrioritizeFeaturesPermissionVerifier
     */
    private $prioritize_features_permission_verifier;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|RetrieveProgramIncrement
     */
    private $retrieve_program_increment;

    protected function setUp(): void
    {
        $db_transaction_executor                    = new DBTransactionExecutorPassthrough();
        $this->feature_to_plan_builder              = \Mockery::mock(FeatureToLinkBuilder::class);
        $this->tracker_artifact_factory             = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->mirrored_milestone_retriever         = \Mockery::mock(MirroredMilestoneRetriever::class);
        $this->content_dao                          = \Mockery::mock(ContentDao::class);
        $this->features_linked_to_milestone_builder = \Mockery::mock(UserStoriesLinkedToMilestoneBuilder::class);

        $this->planner = new FeatureInProgramIncrementPlanner(
            $db_transaction_executor,
            $this->feature_to_plan_builder,
            $this->tracker_artifact_factory,
            $this->mirrored_milestone_retriever,
            $this->content_dao,
            $this->features_linked_to_milestone_builder,
            new TestLogger()
        );
    }

    public function testItAddLinksToMirroredMilestones(): void
    {
        $user   = UserTestBuilder::aUser()->build();
        $change = new ProgramIncrementChanged(1, 10, $user);

        $feature_id = 1234;
        $this->content_dao->shouldReceive('searchContent')->once()
            ->andReturn(['artifact_id' => 101]);
        $this->feature_to_plan_builder->shouldReceive('buildFeatureChange')->andReturn(
            new FeaturePlanChange([$feature_id])
        );

        $milestone_id = 666;
        $this->mirrored_milestone_retriever->shouldReceive('retrieveMilestonesLinkedTo')->with(1)
            ->once()->andReturn([new MirroredMilestone($milestone_id)]);

        $milestone = \Mockery::mock(Artifact::class);
        $milestone->shouldReceive('getId')->andReturn($milestone_id);
        $field_artifact_link = \Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $field_artifact_link->shouldReceive('getId')->andReturn(1);
        $milestone->shouldReceive('getAnArtifactLinkField')->andReturn($field_artifact_link);
        $this->tracker_artifact_factory->shouldReceive('getArtifactById')->once()->with($milestone_id)->andReturn(
            $milestone
        );

        $this->features_linked_to_milestone_builder->shouldReceive('build')->andReturn(
            [1234 => 1]
        );

        $fields_data = new FieldData(
            [1234],
            [],
            1
        );

        $milestone->shouldReceive('createNewChangeset')->with($fields_data->getFieldDataForChangesetCreationFormat(), "", $user)->once();

        $this->planner->plan($change);
    }

    public function testItDoesNothingWhenArtifactLinkIsNotFound(): void
    {
        $user   = UserTestBuilder::aUser()->build();
        $change = new ProgramIncrementChanged(1, 10, $user);

        $feature_id = 1234;
        $this->content_dao->shouldReceive('searchContent')->once()
            ->andReturn(['artifact_id' => 101]);
        $this->feature_to_plan_builder->shouldReceive('buildFeatureChange')->andReturn(
            new FeaturePlanChange([$feature_id])
        );

        $milestone_id = 666;
        $this->mirrored_milestone_retriever->shouldReceive('retrieveMilestonesLinkedTo')->with(1)
            ->once()->andReturn([new MirroredMilestone($milestone_id)]);

        $milestone = \Mockery::mock(Artifact::class);
        $milestone->shouldReceive('getAnArtifactLinkField')->andReturnNull();

        $this->tracker_artifact_factory->shouldReceive('getArtifactById')
            ->once()->with($milestone_id)->andReturn($milestone);

        $milestone->shouldReceive('createNewChangeset')->never();

        $this->planner->plan($change);
    }

    public function testItDoesNothingWhenMilestoneIsNotFound(): void
    {
        $user   = UserTestBuilder::aUser()->build();
        $change = new ProgramIncrementChanged(1, 10, $user);

        $feature_id = 1234;
        $this->content_dao->shouldReceive('searchContent')->once()
            ->andReturn(['artifact_id' => 101]);
        $this->feature_to_plan_builder->shouldReceive('buildFeatureChange')->andReturn(
            new FeaturePlanChange([$feature_id])
        );

        $milestone_id = 666;
        $this->mirrored_milestone_retriever->shouldReceive('retrieveMilestonesLinkedTo')->with(1)
            ->once()->andReturn([new MirroredMilestone($milestone_id)]);

        $this->tracker_artifact_factory->shouldReceive('getArtifactById')
            ->once()->with($milestone_id)->andReturn([]);

        $this->planner->plan($change);
    }
}
