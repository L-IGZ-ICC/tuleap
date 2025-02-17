<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\FloatingPointNumber;

use Tracker_Artifact_ChangesetValue_Float;

class ChangesChecker
{

    public function hasChanges(Tracker_Artifact_ChangesetValue_Float $old_value, $new_value): bool
    {
        $value_to_compare = null;
        if ($new_value !== null && $new_value !== '') {
            $value_to_compare = (float) $new_value;
        }

        return $old_value->getValue() !== $new_value;
    }
}
