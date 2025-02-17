<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div
        class="tlp-form-element"
        v-bind:class="{
            'tlp-form-element-disabled': disabled,
            'tlp-form-element-error': is_required_and_empty,
        }"
        data-test="text-field"
    >
        <format-selector
            v-bind:id="id"
            v-bind:label="field.label"
            v-bind:disabled="disabled"
            v-bind:required="field.required"
            v-bind:value="format"
            v-bind:is_in_preview_mode="is_in_preview_mode"
            v-on:interpret-content-event="togglePreview"
        />
        <rich-text-editor
            v-bind:id="id"
            v-bind:format="format"
            v-bind:disabled="disabled"
            v-bind:required="field.required"
            rows="5"
            v-model="content"
            v-on:upload-image="reemit"
            v-on:format-change="onFormatChange"
        />
    </div>
</template>
<script>
import RichTextEditor from "../../common/RichTextEditor.vue";
import FormatSelector from "../../common/FormatSelector.vue";
import { isDisabled } from "../disabled-field-detector.js";

export default {
    name: "TextField",
    components: { FormatSelector, RichTextEditor },
    props: {
        field: Object,
        value: Object,
    },
    data() {
        return {
            is_in_preview_mode: false,
        };
    },
    computed: {
        disabled() {
            return isDisabled(this.field);
        },
        content: {
            get() {
                return this.value.content;
            },
            set(new_content) {
                this.$emit("input", { format: this.format, content: new_content });
            },
        },
        format: {
            get() {
                return this.value.format;
            },
        },
        id() {
            return "tracker_field_" + this.field.field_id;
        },
        is_required_and_empty() {
            return this.field.required && this.content === "";
        },
    },
    methods: {
        onFormatChange(new_format, new_content) {
            this.$emit("input", { format: new_format, content: new_content });
        },
        reemit(...args) {
            this.$emit("upload-image", ...args);
        },
        togglePreview() {
            this.is_in_preview_mode = !this.is_in_preview_mode;
        },
    },
};
</script>
