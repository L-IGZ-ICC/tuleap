/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import { getUploadImageOptions } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import type {
    RichTextEditorFactory,
    RichTextEditorOptions,
} from "@tuleap/plugin-tracker-rich-text-editor";
import type { TextFieldFormat } from "../../constants/fields-constants";
import { isValidTextFormat, TEXT_FORMAT_TEXT } from "../../constants/fields-constants";
import { initMentionsOnEditorDataReady } from "./init-mentions";

const NEW_FOLLOWUP_TEXTAREA_ID = "tracker_followup_comment_new";
const NEW_FOLLOWUP_ID_SUFFIX = "new";
const TEXT_FIELDS_SELECTOR = ".tracker_artifact_field textarea";

type OnEditorInitCallback = (editor: CKEDITOR.editor, textarea: HTMLTextAreaElement) => void;

export interface TextFieldEditorsOptions {
    onEditorInit: OnEditorInitCallback;
}

export class RichTextEditorsCreator {
    constructor(
        private readonly doc: Document,
        private readonly image_upload_factory: UploadImageFormFactory,
        private readonly editor_factory: RichTextEditorFactory
    ) {}

    public createNewFollowupEditor(): void {
        const new_followup_textarea = this.doc.getElementById(NEW_FOLLOWUP_TEXTAREA_ID);
        if (!(new_followup_textarea instanceof HTMLTextAreaElement)) {
            // When copying artifacts or browsing as anonymous, there is no "new follow-up" textarea
            return;
        }
        const help_block = this.image_upload_factory.createHelpBlock(new_followup_textarea);
        const options: RichTextEditorOptions = {
            format_selectbox_id: NEW_FOLLOWUP_ID_SUFFIX,
            getAdditionalOptions: getUploadImageOptions,
            onFormatChange: (new_format) => help_block?.onFormatChange(new_format),
            onEditorInit: (ckeditor, textarea) =>
                this.image_upload_factory.initiateImageUpload(ckeditor, textarea),
            onEditorDataReady: initMentionsOnEditorDataReady,
        };
        this.editor_factory.createRichTextEditor(new_followup_textarea, options);
    }

    /**
     * @throws
     */
    public createTextFieldEditors(text_fields_options?: TextFieldEditorsOptions): void {
        const text_field_textareas = this.doc.querySelectorAll(TEXT_FIELDS_SELECTOR);
        for (const text_field_textarea of text_field_textareas) {
            if (!(text_field_textarea instanceof HTMLTextAreaElement)) {
                continue;
            }

            const match = text_field_textarea.id.match(/_(\d+)$/);
            if (!match) {
                throw new Error(
                    `Text field textarea's id must finish by an underscore and the field ID. Got ${text_field_textarea.id} instead`
                );
            }
            const field_id = match[1];
            const format_name = `artifact[${field_id}][format]`;
            const format_value = this.getTextFieldFormatOrDefault(field_id);

            const help_block = this.image_upload_factory.createHelpBlock(text_field_textarea);
            const options: RichTextEditorOptions = {
                format_selectbox_id: text_field_textarea.id,
                format_selectbox_name: format_name,
                format_selectbox_value: format_value,
                getAdditionalOptions: getUploadImageOptions,
                onFormatChange: (new_format) => help_block?.onFormatChange(new_format),
                onEditorInit: (ckeditor, textarea) => {
                    this.image_upload_factory.initiateImageUpload(ckeditor, textarea);
                    text_fields_options?.onEditorInit(ckeditor, textarea);
                },
                onEditorDataReady: initMentionsOnEditorDataReady,
            };
            this.editor_factory.createRichTextEditor(text_field_textarea, options);
        }
    }

    private getTextFieldFormatOrDefault(field_id: string): TextFieldFormat {
        const format_hidden_input = this.doc.getElementById(`artifact[${field_id}]_body_format`);
        if (format_hidden_input instanceof HTMLInputElement) {
            const format = format_hidden_input.value;
            if (isValidTextFormat(format)) {
                return format;
            }
        }
        return TEXT_FORMAT_TEXT;
    }
}
