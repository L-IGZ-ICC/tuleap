<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook\PostPush\Commits;

use Tuleap\Gitlab\Repository\Webhook\PostPush\PostPushCommitWebhookData;

class CommitTuleapReferencesParser
{

    public function extractCollectionOfTuleapReferences(
        PostPushCommitWebhookData $commit_webhook_data
    ): CommitTuleapReferenceCollection {
        $matches = [];
        $pattern = '/(?:^|\s|[' . preg_quote('.,;:[](){}|\'"', '/') . '])tuleap-(\d+)/i';
        preg_match_all(
            $pattern,
            $commit_webhook_data->getMessage(),
            $matches
        );

        $parsed_tuleap_references = [];
        if (isset($matches[1])) {
            foreach ($matches[1] as $id) {
                $parsed_tuleap_references[] = new CommitTuleapReference((int) $id);
            }
        }

        return new CommitTuleapReferenceCollection(
            array_unique($parsed_tuleap_references, SORT_REGULAR)
        );
    }
}