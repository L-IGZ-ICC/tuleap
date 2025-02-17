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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import type { FeatureIdWithProgramIncrement } from "./drag-drop";

export function extractFeatureIndexFromProgramIncrement(
    feature_id_with_increment: FeatureIdWithProgramIncrement
): number {
    const feature_index = feature_id_with_increment.program_increment.features.findIndex(
        (feature) => feature_id_with_increment.feature_id === feature.artifact_id
    );

    if (feature_index === -1) {
        throw Error(
            "No feature with id #" +
                feature_id_with_increment.feature_id +
                " in program increment #" +
                feature_id_with_increment.program_increment.id
        );
    }

    return feature_index;
}
