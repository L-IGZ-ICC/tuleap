<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
  -
  -->

<template>
    <button
        class="tlp-button-secondary tlp-button-small artifact-modal-preview-button"
        type="button"
        v-on:click="$emit('commonmark-preview-event')"
    >
        <i
            class="fas tlp-button-icon"
            v-bind:class="{
                'fa-eye': !is_in_preview_mode,
                'fa-pencil-alt': is_in_preview_mode,
            }"
            data-test="button-commonmark-preview-icon"
            aria-hidden="true"
        ></i>
        {{ button_preview_label }}
    </button>
</template>

<script>
import { getEditButtonLabel, getPreviewButtonLabel } from "../gettext-catalog.js";

export default {
    name: "CommonmarkPreviewButton",
    props: {
        is_in_preview_mode: Boolean,
    },
    computed: {
        button_preview_label() {
            if (this.is_in_preview_mode) {
                return getEditButtonLabel();
            }
            return getPreviewButtonLabel();
        },
    },
};
</script>
