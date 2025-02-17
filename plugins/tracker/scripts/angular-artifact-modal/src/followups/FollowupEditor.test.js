/*
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

import localVue from "../helpers/local-vue.js";
import { shallowMount } from "@vue/test-utils";
import FollowupEditor from "./FollowupEditor.vue";
import { setCatalog } from "../gettext-catalog";

let value;

function getInstance() {
    return shallowMount(FollowupEditor, {
        localVue,
        propsData: {
            value,
        },
    });
}

describe(`FollowupEditor`, () => {
    beforeEach(() => {
        setCatalog({ getString: () => "" });
        value = {
            format: "text",
            body: "",
        };
    });

    it(`when the content changes, it will emit the "input" event with the new content`, () => {
        const wrapper = getInstance();
        wrapper.vm.content = "chrysopid";

        expect(wrapper.emitted("input")[0]).toEqual([
            {
                format: "text",
                body: "chrysopid",
            },
        ]);
    });

    it(`when the RichTextEditor emits a "format-change" event,
        it will emit the "input" event with the new format and the new content`, () => {
        const wrapper = getInstance();
        wrapper.vm.onFormatChange("commonmark", "chrysopid");

        expect(wrapper.emitted("input")[0]).toEqual([
            {
                format: "commonmark",
                body: "chrysopid",
            },
        ]);
    });
});
