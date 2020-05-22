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

namespace Tuleap\Document\DownloadFolderAsZip;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Document\Tree\DocumentTreeProjectExtractor;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DocumentFolderZipStreamerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DocumentFolderZipStreamer
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|DocumentTreeProjectExtractor
     */
    private $project_extractor;

    protected function setUp(): void
    {
        $this->project_extractor = M::mock(DocumentTreeProjectExtractor::class);
        $logging_helper          = M::mock(ZipStreamerLoggingHelper::class);
        $this->controller        = new DocumentFolderZipStreamer($this->project_extractor, $logging_helper);
    }

    public function testItThrowsNotFoundWhenNoFolderID(): void
    {
        $this->project_extractor->shouldReceive('getProject')->andReturn(new \Project(['group_id' => 101]));
        $user    = UserTestBuilder::aUser()->withId(110)->build();
        $request = HTTPRequestBuilder::get()->withUser($user)->build();

        $this->expectException(NotFoundException::class);
        $this->controller->process($request, LayoutBuilder::build(), []);
    }
}