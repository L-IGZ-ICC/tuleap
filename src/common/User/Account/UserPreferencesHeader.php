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
 *
 */

declare(strict_types=1);

namespace Tuleap\User\Account;

use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\JavascriptAsset;

final class UserPreferencesHeader
{
    public function display(string $title, BaseLayout $layout, array $additional_classes = []): void
    {
        $layout->addCssAsset(new AccountCssAsset());
        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new \Tuleap\Layout\IncludeCoreAssets(),
                'account/preferences-nav.js'
            )
        );

        $layout->header(
            [
                'title' => $title,
                'main_classes' => array_merge(
                    [ 'tlp-framed', 'user-preferences-frame' ],
                    $additional_classes
                )
            ]
        );
    }
}
