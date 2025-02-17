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

import { recursiveGet } from "tlp";
import type { ProgramElement } from "../../../type";

export interface Feature extends ProgramElement {
    has_user_story_planned: boolean;
}

export function getFeatures(increment_id: number): Promise<Feature[]> {
    return recursiveGet(`/api/v1/program_increment/${encodeURIComponent(increment_id)}/content`, {
        params: {
            limit: 50,
            offset: 0,
        },
    });
}
