<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
  - along with Tuleap. If not, see http://www.gnu.org/licenses/.
  -
  -
  -->

<template>
    <div>
        <fake-caret v-bind:item="item" />
        <i class="fa fa-fw document-folder-content-icon" v-bind:class="icon"></i>
        <a v-bind:href="wiki_url" class="document-folder-subitem-link">
            {{ item.title }}
        </a>
    </div>
</template>

<script>
import { mapState } from "vuex";
import FakeCaret from "./FakeCaret.vue";
import { ICON_WIKI } from "../../../constants";

export default {
    name: "WikiCellTitle",
    components: { FakeCaret },
    props: {
        item: Object,
    },
    computed: {
        ...mapState("configuration", ["project_id"]),
        wiki_url() {
            return `/plugins/docman/?group_id=${this.project_id}&action=show&id=${this.item.id}`;
        },
        icon() {
            return ICON_WIKI;
        },
    },
};
</script>
