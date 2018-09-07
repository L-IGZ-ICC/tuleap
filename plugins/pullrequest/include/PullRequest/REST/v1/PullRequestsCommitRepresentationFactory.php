<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\PullRequest\REST\v1;

use Git_Exec;
use Tuleap\Git\GitPHP\Project;
use Tuleap\Git\REST\v1\GitCommitRepresentation;
use Tuleap\PullRequest\PullRequest;

class PullRequestsCommitRepresentationFactory
{
    /**
     * @var Git_Exec
     */
    private $git_exec;
    /**
     * @var Project
     */
    private $project;

    public function __construct(Git_Exec $git_exec, Project $project)
    {
        $this->git_exec     = $git_exec;
        $this->project      = $project;
    }

    /**
     * @return GitCommitRepresentationCollection
     * @throws \Git_Command_Exception
     */
    public function getPullRequestCommits(PullRequest $pull_request, $limit, $offset)
    {
        $all_references = $this->git_exec->revList($pull_request->getSha1Dest(), $pull_request->getSha1Src());
        $total_size     = count($all_references);

        $all_references = array_slice($all_references, $offset, $limit);

        $commits_collection = [];
        foreach ($all_references as $reference) {
            $representation = new GitCommitRepresentation();
            $representation->build(
                $this->project->GetCommit($reference)
            );

            $commits_collection[] = $representation;
        }

        return new GitCommitRepresentationCollection($commits_collection, $total_size);
    }
}
