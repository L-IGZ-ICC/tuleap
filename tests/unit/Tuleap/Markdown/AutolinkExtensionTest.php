<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Markdown;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use PHPUnit\Framework\TestCase;

final class AutolinkExtensionTest extends TestCase
{
    /**
     * @var CommonMarkConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $environment = Environment::createCommonMarkEnvironment();

        $environment->addExtension(new AutolinkExtension());
        $this->converter = new CommonMarkConverter([], $environment);
    }

    public function testCreatesLinksAutomaticallyForSupportedSchemes(): void
    {
        $result = $this->converter->convertToHtml(
            <<<MARKDOWN_CONTENT
            https://example.com

            http://example.com

            ftp://example.com

            foo@example.com
            MARKDOWN_CONTENT
        );

        self::assertEquals(
            <<<EXPECTED_HTML
            <p><a href="https://example.com">https://example.com</a></p>
            <p><a href="http://example.com">http://example.com</a></p>
            <p>ftp://example.com</p>
            <p>foo@example.com</p>\n
            EXPECTED_HTML,
            $result
        );
    }
}
