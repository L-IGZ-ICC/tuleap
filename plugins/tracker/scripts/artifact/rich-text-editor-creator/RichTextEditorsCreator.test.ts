/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import type { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import type { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import { RichTextEditorsCreator } from "./RichTextEditorsCreator";

// Mock @tuleap/mention because it needs jquery in tests
jest.mock("@tuleap/mention", () => {
    return { initMentions: jest.fn() };
});

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe(`RichTextEditorsCreator`, () => {
    let doc: Document,
        creator: RichTextEditorsCreator,
        image_upload_factory: UploadImageFormFactory,
        editor_factory: RichTextEditorFactory;
    beforeEach(() => {
        doc = createDocument();
        image_upload_factory = {
            createHelpBlock: jest.fn(),
            initiateImageUpload: jest.fn(),
        };
        editor_factory = ({
            createRichTextEditor: jest.fn(),
        } as unknown) as RichTextEditorFactory;
        creator = new RichTextEditorsCreator(doc, image_upload_factory, editor_factory);
    });

    describe(`createNewFollowupEditor()`, () => {
        it(`when there is no "new followup" textarea in the document, it does nothing`, () => {
            creator.createNewFollowupEditor();

            expect(editor_factory.createRichTextEditor).not.toHaveBeenCalled();
        });

        describe(`when there is a "new followup" textarea in the document`, () => {
            let textarea: HTMLTextAreaElement;
            beforeEach(() => {
                textarea = doc.createElement("textarea");
                textarea.id = "tracker_followup_comment_new";
                doc.body.append(textarea);
            });

            it(`enables image upload and creates a rich text editor on it`, () => {
                const createRichTextEditor = jest.spyOn(editor_factory, "createRichTextEditor");

                creator.createNewFollowupEditor();

                expect(image_upload_factory.createHelpBlock).toHaveBeenCalled();
                expect(createRichTextEditor).toHaveBeenCalled();
                const options = createRichTextEditor.mock.calls[0][1];

                expect(options.format_selectbox_id).toEqual("new");
            });

            it(`sets up the onEditorInit callback`, () => {
                const initiateImageUpload = jest.spyOn(image_upload_factory, "initiateImageUpload");
                const createRichTextEditor = jest.spyOn(editor_factory, "createRichTextEditor");

                creator.createNewFollowupEditor();

                const options = createRichTextEditor.mock.calls[0][1];
                if (options.onEditorInit === undefined) {
                    throw new Error(
                        "Expected an onEditorInit callback to be passed to rich text editor factory, but none was passed"
                    );
                }
                const fake_ckeditor = {} as CKEDITOR.editor;
                options.onEditorInit(fake_ckeditor, textarea);

                expect(initiateImageUpload).toHaveBeenCalled();
            });
        });
    });

    describe(`createTextFieldEditors()`, () => {
        it(`when there is no text field textarea, it does nothing`, () => {
            creator.createTextFieldEditors();

            expect(editor_factory.createRichTextEditor).not.toHaveBeenCalled();
        });

        it(`when a text field textarea has an id that does not end with underscore and its field id,
            it throws`, () => {
            doc.body.insertAdjacentHTML(
                "beforeend",
                `<div class="tracker_artifact_field"><textarea id="bad_id"></textarea>`
            );

            expect(() => creator.createTextFieldEditors()).toThrow();
        });

        describe(`when there are text field textareas in the document`, () => {
            it(`and no matching hidden input fields,
                it enables image upload and creates a rich text editor on each one
                and defaults the format to "text"`, () => {
                doc.body.insertAdjacentHTML(
                    "beforeend",
                    `<div class="tracker_artifact_field"><textarea id="field_1234"></textarea>`
                );
                const createRichTextEditor = jest.spyOn(editor_factory, "createRichTextEditor");

                creator.createTextFieldEditors();

                expect(image_upload_factory.createHelpBlock).toHaveBeenCalled();
                expect(createRichTextEditor).toHaveBeenCalled();
                const options = createRichTextEditor.mock.calls[0][1];

                expect(options.format_selectbox_id).toEqual("field_1234");
                expect(options.format_selectbox_name).toEqual("artifact[1234][format]");
                expect(options.format_selectbox_value).toEqual("text");
            });

            it(`and matching hidden input fields,
                it will pass the hidden input value as selected format option`, () => {
                doc.body.insertAdjacentHTML(
                    "beforeend",
                    `
                    <div class="tracker_artifact_field">
                        <textarea id="field_1234"></textarea>
                        <input type="hidden" id="artifact[1234]_body_format" value="html">
                    </div>
                    <div class="tracker_artifact_field">
                      <textarea id="field_4567"></textarea>
                      <input type="hidden" id="artifact[4567]_body_format" value="text">
                  </div>`
                );
                const createRichTextEditor = jest.spyOn(editor_factory, "createRichTextEditor");

                creator.createTextFieldEditors();

                expect(image_upload_factory.createHelpBlock).toHaveBeenCalled();
                expect(createRichTextEditor).toHaveBeenCalled();
                const first_options = createRichTextEditor.mock.calls[0][1];

                expect(first_options.format_selectbox_id).toEqual("field_1234");
                expect(first_options.format_selectbox_name).toEqual("artifact[1234][format]");
                expect(first_options.format_selectbox_value).toEqual("html");

                const second_options = createRichTextEditor.mock.calls[1][1];

                expect(second_options.format_selectbox_id).toEqual("field_4567");
                expect(second_options.format_selectbox_name).toEqual("artifact[4567][format]");
                expect(second_options.format_selectbox_value).toEqual("text");
            });

            it(`and when options were passed,
                it will set up the onEditorInit callback`, () => {
                const wrapper_div = doc.createElement("div");
                wrapper_div.classList.add("tracker_artifact_field");
                const textarea = doc.createElement("textarea");
                textarea.id = "field_1234";
                const hidden_input = doc.createElement("input");
                hidden_input.type = "hidden";
                hidden_input.id = "artifact[1234]_body_format";
                hidden_input.value = "html";
                wrapper_div.append(textarea, hidden_input);
                doc.body.append(wrapper_div);
                const initiateImageUpload = jest.spyOn(image_upload_factory, "initiateImageUpload");
                const createRichTextEditor = jest.spyOn(editor_factory, "createRichTextEditor");

                const options = { onEditorInit: jest.fn() };
                creator.createTextFieldEditors(options);

                const rte_options = createRichTextEditor.mock.calls[0][1];
                const fake_ckeditor = {} as CKEDITOR.editor;

                if (rte_options.onEditorInit === undefined) {
                    throw new Error(
                        "Expected an onEditorInit callback to be passed to rich text editor factory, but none was passed"
                    );
                }
                rte_options.onEditorInit(fake_ckeditor, textarea);
                expect(options.onEditorInit).toHaveBeenCalled();
                expect(initiateImageUpload).toHaveBeenCalled();
            });
        });
    });
});
