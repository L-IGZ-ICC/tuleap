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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields;

class FieldArtifactLinkAdapter
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(\Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @throws FieldSynchronizationException
     */
    public function build(\Tracker $source_tracker): FieldData
    {
        $artifact_link_fields = $this->form_element_factory->getUsedArtifactLinkFields($source_tracker);
        if (count($artifact_link_fields) > 0) {
            return new FieldData($artifact_link_fields[0]);
        }
        throw new NoArtifactLinkFieldException($source_tracker->getId());
    }
}