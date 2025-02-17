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
import type { Feature } from "./Feature/feature-retriever";

export interface ProgramIncrement {
    id: number;
    title: string;
    status: string;
    start_date: string | null;
    end_date: string | null;
    user_can_update: boolean;
    user_can_plan: boolean;
    artifact_link_field_id: number | null;
    features: Feature[];
}

export function getProgramIncrements(program_id: number): Promise<ProgramIncrement[]> {
    return recursiveGet(`/api/v1/projects/${encodeURIComponent(program_id)}/program_increments`, {
        params: {
            limit: 50,
            offset: 0,
        },
    });
}
