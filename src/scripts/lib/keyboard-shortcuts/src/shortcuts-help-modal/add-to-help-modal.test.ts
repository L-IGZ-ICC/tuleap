/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { GLOBAL_SCOPE } from "../type";
import type { ShortcutsGroup } from "../type";

import { createShortcutsGroupInHelpModal } from "./add-to-help-modal";
import * as getter_shortcuts_group_head from "./create-shortcuts-group-head";
import * as getter_shortcuts_group_table from "./create-shortcuts-group-table";
import * as getter_shortcut_section from "./get-shortcuts-section";

jest.mock("./create-shortcuts-group-head");
jest.mock("./create-shortcuts-group-table");
jest.mock("./get-shortcuts-section");

describe("add-to-help-modal.ts", () => {
    let doc: Document;

    let global_shortcuts_section: HTMLElement;
    let specific_shortcuts_section: HTMLElement;

    let shortcuts_group_head: HTMLElement;
    let shortcuts_group_table: HTMLTableElement;

    const shortcuts_group: ShortcutsGroup = {} as ShortcutsGroup;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();

        shortcuts_group_head = doc.createElement("div");
        shortcuts_group_table = doc.createElement("table");

        global_shortcuts_section = doc.createElement("section");
        specific_shortcuts_section = doc.createElement("section");

        jest.spyOn(getter_shortcuts_group_head, "createShortcutsGroupHead").mockReturnValue(
            shortcuts_group_head
        );
        jest.spyOn(getter_shortcuts_group_table, "createShortcutsGroupTable").mockReturnValue(
            shortcuts_group_table
        );
        jest.spyOn(getter_shortcut_section, "getGlobalShortcutsSection").mockReturnValue(
            global_shortcuts_section
        );
        jest.spyOn(getter_shortcut_section, "getSpecificShortcutsSection").mockReturnValue(
            specific_shortcuts_section
        );
    });

    it("adds to the global shortcuts section in the shortcuts modal if GLOBAL_SCOPE is provided", () => {
        const get_global_shortcuts_section = jest.spyOn(
            getter_shortcut_section,
            "getGlobalShortcutsSection"
        );
        createShortcutsGroupInHelpModal(doc, shortcuts_group, GLOBAL_SCOPE);

        expect(get_global_shortcuts_section).toHaveBeenCalled();
    });

    it("adds to the specific shortcuts section in the shortcuts modal if no scope is provided", () => {
        const get_specific_shortcuts_section = jest.spyOn(
            getter_shortcut_section,
            "getSpecificShortcutsSection"
        );
        createShortcutsGroupInHelpModal(doc, shortcuts_group);

        expect(get_specific_shortcuts_section).toHaveBeenCalled();
    });

    it("appends a group head and table to the shortcuts section", () => {
        createShortcutsGroupInHelpModal(doc, shortcuts_group);

        expect(specific_shortcuts_section.firstChild).toBe(shortcuts_group_head);
        expect(specific_shortcuts_section.lastChild).toBe(shortcuts_group_table);
    });
});
