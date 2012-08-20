<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../include/autoload.php';
require_once dirname(__FILE__).'/../builders/aPlanning.php';
require_once dirname(__FILE__).'/../../include/Planning/ArtifactLinker.class.php';
require_once dirname(__FILE__).'/../../../tracker/include/constants.php';
require_once TRACKER_BASE_DIR.'/../tests/builders/all.php';

class Planning_ArtifactLinkerTest extends TuleapTestCase {

    public function itDoesntLinkWhenItWasLinkedToAParent() {
        $user   = aUser()->build();
        $story_id = 5698;
        $story = aMockArtifact()->withId($story_id)->build();
        $task = aMockArtifact()->withId(2)->build();
        stub($task)->getAllAncestors($user)->returns(array($story));

        $request = aRequest()->with('link-artifact-id', "$story_id")->withUser($user)->build();

        $story->expectNever('linkArtifact');

        $artifact_factory = mock('Tracker_ArtifactFactory');
        $planning_factory = mock('PlanningFactory');
        $linker = new Planning_ArtifactLinker($artifact_factory, $planning_factory);
        $linker->linkWithParents($request, $task);
    }

    public function itLinksWithAllHierarchyWhenItWasLinkedToAnAssociatedTracker() {
        $user   = aUser()->build();
        $epic_id = 2;
        $epic = aMockArtifact()->withId(2)->build();
        stub($epic)->getAllAncestors($user)->returns(array());

        $product = aMockArtifact()->withId(56)->build();

        $release_id = 7777;
        $release    = aMockArtifact()->withId($release_id)->build();
        stub($release)->getAllAncestors($user)->returns(array($product));

        $request = aRequest()->with('link-artifact-id', "$release_id")->withUser($user)->build();

        $epic->expectNever('linkArtifact');
        $release->expectNever('linkArtifact');
        $product->expectOnce('linkArtifact', array($epic_id, $user));

        $artifact_factory = mock('Tracker_ArtifactFactory');
        stub($artifact_factory)->getArtifactById($release_id)->returns($release);
        $planning_factory = mock('PlanningFactory');
        $linker = new Planning_ArtifactLinker($artifact_factory, $planning_factory);
        $linker->linkWithParents($request, $epic);
    }

}

class Planning_ArtifactLinker_LinkWithPlanningTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->user             = aUser()->build();
        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        $this->planning_factory = mock('PlanningFactory');

        $this->linker = new Planning_ArtifactLinker($this->artifact_factory, $this->planning_factory);

        $this->epic_id      = 2;
        $this->epic_tracker = aTracker()->withId(200)->build();
        $this->epic         = aMockArtifact()->withId($this->epic_id)->withTracker($this->epic_tracker)->build();
    }


    public function itLinksTheEpicWithReleaseWhenReleaseIsParentOfSprint() {
        $release_tracker  = aTracker()->withId(41)->build();
        $release_planning = aPlanning()->withPlanningTracker($release_tracker)->withBacklogTracker($this->epic_tracker)->build();
        $release          = aMockArtifact()->withId(31)->withTracker($release_tracker)->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($release_tracker)->returns($release_planning);

        $sprint  = aMockArtifact()->withId(32)->build();
        stub($sprint)->getAllAncestors()->returns(array($release));

        $release->expectOnce('linkArtifact', array($this->epic_id, $this->user));

        // I create an epic (argument 2) after having created a story in a sprint (argument 3)
        $this->linker->linkWithPlanning($this->user, $this->epic, $sprint);
    }

    public function itLinksTheEpicWithMilestonesCorrespondingToStoryPlanning() {
        $product_tracker  = aTracker()->withId(40)->build();
        $product_planning = aPlanning()->withPlanningTracker($product_tracker)->withBacklogTracker($this->epic_tracker)->build();
        $product          = aMockArtifact()->withId(30)->withTracker($product_tracker)->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($product_tracker)->returns($product_planning);

        $release_tracker  = aTracker()->withId(41)->build();
        $release_planning = aPlanning()->withPlanningTracker($release_tracker)->withBacklogTracker($this->epic_tracker)->build();
        $release          = aMockArtifact()->withId(31)->withTracker($release_tracker)->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($release_tracker)->returns($release_planning);

        $sprint  = aMockArtifact()->withId(32)->build();
        stub($sprint)->getAllAncestors()->returns(array($release, $product));

        $product->expectOnce('linkArtifact', array($this->epic_id, $this->user));
        $release->expectOnce('linkArtifact', array($this->epic_id, $this->user));

        $this->linker->linkWithPlanning($this->user, $this->epic, $sprint);
    }

    public function itDoesntLinkTheEpicWithProductPlanningWhenProductPlanningDoesntManageEpics() {
        $theme_tracker = aTracker()->withId(300)->build();

        $product_tracker  = aTracker()->withId(40)->build();
        $product_planning = aPlanning()->withPlanningTracker($product_tracker)->withBacklogTracker($theme_tracker)->build();
        $product          = aMockArtifact()->withId(30)->withTracker($product_tracker)->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($product_tracker)->returns($product_planning);

        $release_tracker  = aTracker()->withId(41)->build();
        $release_planning = aPlanning()->withPlanningTracker($release_tracker)->withBacklogTracker($this->epic_tracker)->build();
        $release          = aMockArtifact()->withId(31)->withTracker($release_tracker)->build();
        stub($this->planning_factory)->getPlanningByPlanningTracker($release_tracker)->returns($release_planning);

        $sprint  = aMockArtifact()->withId(32)->build();
        stub($sprint)->getAllAncestors()->returns(array($release, $product));

        $product->expectNever('linkArtifact');
        $release->expectOnce('linkArtifact', array($this->epic_id, $this->user));

        $this->linker->linkWithPlanning($this->user, $this->epic, $sprint);
    }
}

?>
