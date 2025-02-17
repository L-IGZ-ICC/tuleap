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

import type { ProgramElement, State } from "../type";
import mutations from "./mutations";
import type { Feature } from "../helpers/ProgramIncrement/Feature/feature-retriever";
import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";

describe("Mutations", () => {
    describe("addProgramIncrement", () => {
        it("When there is the same program increment in state, Then error is thrown", () => {
            const state = {
                program_increments: [
                    {
                        id: 14,
                        features: [{ artifact_id: 588 } as Feature],
                    } as ProgramIncrement,
                ],
            } as State;

            const program_increment = {
                id: 14,
            } as ProgramIncrement;

            expect(() => mutations.addProgramIncrement(state, program_increment)).toThrowError(
                "Program increment with id #14 already exists"
            );
        });

        it("When program increment does not exist in state, Then it is added", () => {
            const state = {
                program_increments: [
                    {
                        id: 15,
                    } as ProgramIncrement,
                ],
            } as State;

            const program_increment = {
                id: 14,
                features: [{ artifact_id: 588 } as Feature],
            } as ProgramIncrement;

            mutations.addProgramIncrement(state, program_increment);
            expect(state.program_increments.length).toEqual(2);
            expect(state.program_increments[0]).toEqual({ id: 15 });
            expect(state.program_increments[1]).toEqual({
                id: 14,
                features: [{ artifact_id: 588 }],
            });
        });
    });

    describe("addToBePlannedElement", () => {
        it("When element already exists, Then error is thrown", () => {
            const state = {
                to_be_planned_elements: [{ artifact_id: 14 }] as ProgramElement[],
            } as State;

            const to_be_planned_element = {
                artifact_id: 14,
            } as ProgramElement;

            expect(() =>
                mutations.addToBePlannedElement(state, to_be_planned_element)
            ).toThrowError("To be planned element with id #14 already exist");
        });

        it("When element does not exist in state, Then it is added", () => {
            const state = {
                to_be_planned_elements: [{ artifact_id: 14 }] as ProgramElement[],
            } as State;

            const to_be_planned_element = {
                artifact_id: 125,
            } as ProgramElement;

            mutations.addToBePlannedElement(state, to_be_planned_element);
            expect(state.to_be_planned_elements.length).toEqual(2);
            expect(state.to_be_planned_elements[0]).toEqual({ artifact_id: 14 });
            expect(state.to_be_planned_elements[1]).toEqual({ artifact_id: 125 });
        });
    });

    describe("removeToBePlannedElement", () => {
        it("When feature exist, Then it is deleted from state", () => {
            const state = {
                to_be_planned_elements: [
                    { artifact_id: 14 },
                    { artifact_id: 125 },
                ] as ProgramElement[],
            } as State;

            const element_to_remove = {
                artifact_id: 125,
            } as ProgramElement;

            mutations.removeToBePlannedElement(state, element_to_remove);
            expect(state.to_be_planned_elements.length).toEqual(1);
            expect(state.to_be_planned_elements[0]).toEqual({ artifact_id: 14 });
        });

        it("When feature does not exist, Then it is not deleted", () => {
            const state = {
                to_be_planned_elements: [
                    { artifact_id: 14 },
                    { artifact_id: 125 },
                ] as ProgramElement[],
            } as State;

            const element_to_remove = {
                artifact_id: 536,
            } as ProgramElement;

            mutations.removeToBePlannedElement(state, element_to_remove);
            expect(state.to_be_planned_elements.length).toEqual(2);
            expect(state.to_be_planned_elements[0]).toEqual({ artifact_id: 14 });
            expect(state.to_be_planned_elements[1]).toEqual({ artifact_id: 125 });
        });
    });

    describe("startMoveElementInAProgramIncrement", () => {
        it("When the element is not already moving, Then it is added in state", () => {
            const state = {
                ongoing_move_elements_id: [14],
            } as State;

            mutations.startMoveElementInAProgramIncrement(state, 536);
            expect(state.ongoing_move_elements_id.length).toEqual(2);
            expect(state.ongoing_move_elements_id[0]).toEqual(14);
            expect(state.ongoing_move_elements_id[1]).toEqual(536);
        });

        it("When the element is already moving, Then error is thrown", () => {
            const state = {
                ongoing_move_elements_id: [536],
            } as State;

            expect(() => mutations.startMoveElementInAProgramIncrement(state, 536)).toThrowError(
                "Program element #536 is already moving"
            );
        });
    });

    describe("finishMoveElement", () => {
        it("When element is moving, Then it is deleted from state", () => {
            const state = {
                ongoing_move_elements_id: [536, 537],
            } as State;

            mutations.finishMoveElement(state, 536);
            expect(state.ongoing_move_elements_id.length).toEqual(1);
            expect(state.ongoing_move_elements_id[0]).toEqual(537);
        });

        it("When element is not moving, Then it is not deleted", () => {
            const state = {
                ongoing_move_elements_id: [536, 537],
            } as State;

            mutations.finishMoveElement(state, 14);
            expect(state.ongoing_move_elements_id.length).toEqual(2);
            expect(state.ongoing_move_elements_id[0]).toEqual(536);
            expect(state.ongoing_move_elements_id[1]).toEqual(537);
        });
    });
});
