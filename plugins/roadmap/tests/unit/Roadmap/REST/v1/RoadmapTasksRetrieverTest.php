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

namespace Tuleap\Roadmap\REST\v1;

use Luracast\Restler\RestException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use ProjectManager;
use Tracker;
use TrackerFactory;
use Tuleap\Roadmap\RoadmapWidgetDao;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\TrackerColor;

class RoadmapTasksRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ROADMAP_ID = 42;
    private const PROJECT_ID = 101;
    private const TRACKER_ID = 111;

    /**
     * @var RoadmapTasksRetriever
     */
    private $retriever;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RoadmapWidgetDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\URLVerification
     */
    private $url_verification;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_ArtifactFactory
     */
    private $artifact_factory;

    protected function setUp(): void
    {
        $this->dao                        = Mockery::mock(RoadmapWidgetDao::class);
        $this->project_manager            = Mockery::mock(ProjectManager::class);
        $this->user_manager               = Mockery::mock(\UserManager::class);
        $this->url_verification           = Mockery::mock(\URLVerification::class);
        $this->tracker_factory            = Mockery::mock(TrackerFactory::class);
        $this->semantic_timeframe_builder = Mockery::mock(SemanticTimeframeBuilder::class);
        $this->artifact_factory           = Mockery::mock(\Tracker_ArtifactFactory::class);

        $this->retriever = new RoadmapTasksRetriever(
            $this->dao,
            $this->project_manager,
            $this->user_manager,
            $this->url_verification,
            $this->tracker_factory,
            $this->semantic_timeframe_builder,
            $this->artifact_factory,
        );

        $this->user = UserTestBuilder::anActiveUser()->build();
    }

    public function test404IfRoadmapNotFound(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn([]);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfProjectNotFound(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->once()
            ->andThrow(Project_AccessProjectNotFoundException::class);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test403IfUserCannotAccessProject(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->once()
            ->andThrow(Mockery::spy(Project_AccessException::class));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfTrackerNotFound(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification->shouldReceive('userCanAccessProject')->once();

        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturnNull();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfTrackerIsNotActive(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn(ProjectTestBuilder::aProject()->build());

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification->shouldReceive('userCanAccessProject')->once();

        $tracker = Mockery::mock(
            Tracker::class,
            ['isActive' => false, 'userCanView' => true]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test404IfTrackerIsNotAccessibleForUser(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $tracker = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => false]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfTrackerDoesNotHaveTitleField(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $tracker = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => null]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfTitleFieldIsNotReadable(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => false]);
        $tracker     = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => $title_field]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfTimeframeIsNotDefined(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => $title_field]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->once()
            ->andReturn(new SemanticTimeframe($tracker, null, null, null));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfStartDateIsNotReadable(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => $title_field]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->once()
            ->andReturn(
                new SemanticTimeframe(
                    $tracker,
                    Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => false]),
                    null,
                    Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]),
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfEndDateIsNotReadable(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => $title_field]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->once()
            ->andReturn(
                new SemanticTimeframe(
                    $tracker,
                    Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]),
                    null,
                    Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => false]),
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function test400IfDurationIsNotReadable(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            ['isActive' => true, 'userCanView' => true, 'getTitleField' => $title_field]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->once()
            ->andReturn(
                new SemanticTimeframe(
                    $tracker,
                    Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]),
                    Mockery::mock(\Tracker_FormElement_Field_Numeric::class, ['userCanRead' => false]),
                    null,
                )
            );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
    }

    public function testItReturnsAPaginatedListOfReadableTaskRepresentation(): void
    {
        $this->dao
            ->shouldReceive('searchById')
            ->with(self::ROADMAP_ID)
            ->once()
            ->andReturn(
                [
                    'id'         => self::ROADMAP_ID,
                    'owner_id'   => self::PROJECT_ID,
                    'owner_type' => 'g',
                    'title'      => 'My Roadmap',
                    'tracker_id' => self::TRACKER_ID
                ]
            );

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager
            ->shouldReceive('getProject')
            ->with(self::PROJECT_ID)
            ->once()
            ->andReturn($project);

        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($this->user);

        $this->url_verification
            ->shouldReceive('userCanAccessProject')
            ->with($this->user, $project)
            ->once();

        $title_field = Mockery::mock(\Tracker_FormElement_Field_String::class, ['userCanRead' => true]);
        $tracker     = Mockery::mock(
            Tracker::class,
            [
                'isActive'      => true,
                'userCanView'   => true,
                'getTitleField' => $title_field,
                'getId'         => self::TRACKER_ID,
                'getColor'      => TrackerColor::fromName('acid-green')
            ]
        );
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(self::TRACKER_ID)
            ->andReturn($tracker);

        $this->semantic_timeframe_builder
            ->shouldReceive('getSemantic')
            ->with($tracker)
            ->once()
            ->andReturn(
                new SemanticTimeframe(
                    $tracker,
                    Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]),
                    null,
                    Mockery::mock(\Tracker_FormElement_Field_Date::class, ['userCanRead' => true]),
                )
            );

        $this->artifact_factory
            ->shouldReceive('getPaginatedArtifactsByTrackerId')
            ->with(self::TRACKER_ID, 0, 10, false)
            ->once()
            ->andReturn(
                new \Tracker_Artifact_PaginatedArtifacts(
                    [
                        Mockery::mock(
                            Artifact::class,
                            [
                                'userCanView' => true,
                                'getId'       => 201,
                                'getXRef'     => 'task #201',
                                'getUri'      => '/plugins/tracker?aid=201',
                                'getTitle'    => 'Do this',
                            ]
                        ),
                        Mockery::mock(
                            Artifact::class,
                            [
                                'userCanView' => false,
                                'getId'       => 202,
                                'getXRef'     => 'task #202',
                                'getUri'      => '/plugins/tracker?aid=202',
                                'getTitle'    => 'Do that',
                            ]
                        ),
                        Mockery::mock(
                            Artifact::class,
                            [
                                'userCanView' => true,
                                'getId'       => 203,
                                'getXRef'     => 'task #203',
                                'getUri'      => '/plugins/tracker?aid=203',
                                'getTitle'    => 'Do those',
                            ]
                        ),
                    ],
                    3
                )
            );

        $collection = $this->retriever->getTasks(self::ROADMAP_ID, 0, 10);
        self::assertEquals(3, $collection->getTotalSize());
        self::assertCount(2, $collection->getRepresentations());
        self::assertEquals(
            [
                new TaskRepresentation(201, 'task #201', '/plugins/tracker?aid=201', 'Do this', 'acid-green'),
                new TaskRepresentation(203, 'task #203', '/plugins/tracker?aid=203', 'Do those', 'acid-green'),
            ],
            $collection->getRepresentations()
        );
    }
}
