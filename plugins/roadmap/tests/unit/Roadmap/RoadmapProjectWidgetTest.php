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

namespace Tuleap\Roadmap;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\MappingRegistry;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class RoadmapProjectWidgetTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RoadmapWidgetDao
     */
    private $dao;
    /**
     * @var RoadmapProjectWidget
     */
    private $widget;

    protected function setUp(): void
    {
        $this->dao = Mockery::mock(RoadmapWidgetDao::class);

        $template_render = new class extends \TemplateRenderer {
            public function renderToString($template_name, $presenter): string
            {
                return '';
            }
        };


        $this->widget = new RoadmapProjectWidget(
            ProjectTestBuilder::aProject()->withId(101)->build(),
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $template_render,
        );
    }

    public function testCloneContentBlindlyCloneContentIfNoTrackerMapping(): void
    {
        $this->dao
            ->shouldReceive('cloneContent')
            ->with(101, "g", 102, "g")
            ->once();

        $this->widget->cloneContent(
            ProjectTestBuilder::aProject()->build(),
            ProjectTestBuilder::aProject()->build(),
            "42",
            "102",
            "g",
            new MappingRegistry([])
        );
    }

    public function testCloneContentBlindlyCloneContentIfContentIdCannotBeFound(): void
    {
        $this->dao
            ->shouldReceive('searchContent')
            ->with(42, 101, "g")
            ->once()
            ->andReturn([]);

        $this->dao
            ->shouldReceive('cloneContent')
            ->with(101, "g", 102, "g")
            ->once();

        $mapping_registry = new MappingRegistry([]);
        $mapping_registry->setCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY, [111 => 222]);
        $this->widget->cloneContent(
            ProjectTestBuilder::aProject()->build(),
            ProjectTestBuilder::aProject()->build(),
            "42",
            "102",
            "g",
            $mapping_registry
        );
    }

    public function testCloneContentTakeThePreviousTrackerIdIfItIsNotPartOfTheMapping(): void
    {
        $this->dao
            ->shouldReceive('searchContent')
            ->with(42, 101, "g")
            ->once()
            ->andReturn([
                'title'      => 'Roadmap',
                'tracker_id' => 110,
            ]);

        $this->dao
            ->shouldReceive('insertContent')
            ->with(102, "g", 'Roadmap', 110)
            ->once();

        $mapping_registry = new MappingRegistry([]);
        $mapping_registry->setCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY, [111 => 222]);
        $this->widget->cloneContent(
            ProjectTestBuilder::aProject()->build(),
            ProjectTestBuilder::aProject()->build(),
            "42",
            "102",
            "g",
            $mapping_registry
        );
    }

    public function testCloneContentTakeTheTrackerIdFromTheMapping(): void
    {
        $this->dao
            ->shouldReceive('searchContent')
            ->with(42, 101, "g")
            ->once()
            ->andReturn([
                'title'      => 'Roadmap',
                'tracker_id' => 111,
            ]);

        $this->dao
            ->shouldReceive('insertContent')
            ->with(102, "g", 'Roadmap', 222)
            ->once();

        $mapping_registry = new MappingRegistry([]);
        $mapping_registry->setCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY, [111 => 222]);
        $this->widget->cloneContent(
            ProjectTestBuilder::aProject()->build(),
            ProjectTestBuilder::aProject()->build(),
            "42",
            "102",
            "g",
            $mapping_registry
        );
    }
}
