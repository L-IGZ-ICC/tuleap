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

import { shallowMount } from "@vue/test-utils";
import ToBePlanned from "./ToBePlanned.vue";
import * as retriever from "../../../helpers/ToBePlanned/element-to-plan-retriever";
import { createProgramManagementLocalVue } from "../../../helpers/local-vue-for-test";
import type { DefaultData } from "vue/types/options";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import type { ProgramElement } from "../../../type";

describe("ToBePlanned", () => {
    it("Displays the empty state when no artifact are found", async () => {
        jest.spyOn(retriever, "getToBePlannedElements").mockResolvedValue([]);

        const store = createStoreMock({
            state: { to_be_planned_elements: [], configuration: { program_id: 202 } },
        });
        const wrapper = shallowMount(ToBePlanned, {
            mocks: { $store: store },
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ToBePlanned> {
                return {
                    is_loading: false,
                    has_error: false,
                };
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
    });

    it("Displays an error when rest route fail", async () => {
        jest.spyOn(retriever, "getToBePlannedElements").mockResolvedValue([]);
        const store = createStoreMock({
            state: { to_be_planned_elements: [], configuration: { program_id: 202 } },
        });
        const wrapper = shallowMount(ToBePlanned, {
            mocks: { $store: store },
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ToBePlanned> {
                return {
                    is_loading: false,
                    has_error: true,
                    error_message: "Oups, something happened",
                };
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(true);
    });

    it("Displays the elements to be planned", async () => {
        const element_one = {
            artifact_id: 1,
            artifact_title: "My artifact",
            tracker: {
                label: "bug",
            },
        } as ProgramElement;
        const element_two = {
            artifact_id: 2,
            artifact_title: "My user story",
            tracker: {
                label: "user_stories",
            },
        } as ProgramElement;

        jest.spyOn(retriever, "getToBePlannedElements").mockResolvedValue([
            element_one,
            element_two,
        ]);

        const store = createStoreMock({
            state: {
                to_be_planned_elements: [element_one, element_two],
                configuration: { program_id: 202 },
            },
        });
        const wrapper = shallowMount(ToBePlanned, {
            mocks: { $store: store },
            localVue: await createProgramManagementLocalVue(),
            data(): DefaultData<ToBePlanned> {
                return {
                    is_loading: false,
                    has_error: false,
                    error_message: "",
                };
            },
        });

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=to-be-planned-elements]").exists()).toBe(true);
        expect(wrapper.find("[data-test=to-be-planned-error]").exists()).toBe(false);
    });

    it("During loading, Then elements are retrieved and stored in store", async () => {
        const element_one = {
            artifact_id: 1,
            artifact_title: "My artifact",
            tracker: {
                label: "bug",
            },
        } as ProgramElement;
        const element_two = {
            artifact_id: 2,
            artifact_title: "My user story",
            tracker: {
                label: "user_stories",
            },
        } as ProgramElement;

        jest.spyOn(retriever, "getToBePlannedElements").mockImplementation(() =>
            Promise.resolve([element_one, element_two])
        );

        const store = createStoreMock({
            state: { to_be_planned_elements: [], configuration: { program_id: 202 } },
        });

        const wrapper = shallowMount(ToBePlanned, {
            mocks: { $store: store },
            localVue: await createProgramManagementLocalVue(),
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.vm.$store.commit).toHaveBeenCalledWith("setToBePlannedElements", [
            element_one,
            element_two,
        ]);
    });
});
