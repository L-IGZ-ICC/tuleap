<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    <div class="breadcrumb-container document-breadcrumb">
        <breadcrumb-privacy
            v-bind:project_flags="project_flags"
            v-bind:privacy="privacy"
            v-bind:project_public_name="project_public_name"
        />
        <nav class="breadcrumb">
            <div class="breadcrumb-item breadcrumb-project">
                <a v-bind:href="project_url" class="breadcrumb-link">
                    {{ project_public_name }}
                </a>
            </div>
            <div v-bind:class="get_breadcrumb_class">
                <router-link
                    v-bind:to="{ name: 'root_folder' }"
                    class="breadcrumb-link"
                    v-bind:title="document_tree_title"
                >
                    <i class="breadcrumb-link-icon far fa-folderpen"></i>
                    <translate>Documents</translate>
                </router-link>
                <div class="breadcrumb-switch-menu-container">
                    <nav class="breadcrumb-switch-menu" v-if="is_admin">
                        <span class="breadcrumb-dropdown-item">
                            <a
                                class="breadcrumb-dropdown-link"
                                v-bind:href="document_administration_url"
                                v-bind:title="document_administration_title"
                                data-test="breadcrumb-administrator-link"
                            >
                                <i class="fa fa-cog fa-fw"></i>
                                <translate>Administration</translate>
                            </a>
                        </span>
                    </nav>
                </div>
            </div>

            <span
                class="breadcrumb-item breadcrumb-item-disabled"
                v-if="is_ellipsis_displayed"
                data-test="breadcrumb-ellipsis"
            >
                <span class="breadcrumb-link" v-bind:title="ellipsis_title">...</span>
            </span>
            <document-breadcrumb-element
                v-for="parent in current_folder_ascendant_hierarchy_to_display"
                v-bind:key="parent.id"
                v-bind:item="parent"
            />
            <span
                class="breadcrumb-item"
                v-if="is_loading_ascendant_hierarchy"
                data-test="document-breadcrumb-skeleton"
            >
                <a class="breadcrumb-link" href="#">
                    <span class="tlp-skeleton-text"></span>
                </a>
            </span>
            <document-breadcrumb-document
                v-if="is_current_document_displayed"
                v-bind:current_document="currently_previewed_item"
                v-bind:parent_folder="current_folder"
                data-test="breadcrumb-current-document"
            />
        </nav>
    </div>
</template>

<script>
import { mapState } from "vuex";
import DocumentBreadcrumbElement from "./DocumentBreadcrumbElement.vue";
import DocumentBreadcrumbDocument from "./DocumentBreadcrumbDocument.vue";
import { BreadcrumbPrivacy } from "@tuleap/vue-breadcrumb-privacy";

export default {
    name: "DocumentBreadcrumb",
    components: { DocumentBreadcrumbElement, DocumentBreadcrumbDocument, BreadcrumbPrivacy },
    data() {
        return {
            max_nb_to_display: 5,
        };
    },
    computed: {
        ...mapState([
            "project_url",
            "current_folder_ascendant_hierarchy",
            "is_loading_ascendant_hierarchy",
            "currently_previewed_item",
            "current_folder",
            "privacy",
            "project_flags",
        ]),
        ...mapState("configuration", ["project_id", "project_public_name", "user_is_admin"]),
        document_tree_title() {
            return this.$gettext("Project documentation");
        },
        document_administration_url() {
            return "/plugins/docman/?group_id=" + this.project_id + "&action=admin";
        },
        document_administration_title() {
            return this.$gettext("Administration");
        },
        is_admin() {
            return this.user_is_admin;
        },
        get_breadcrumb_class() {
            if (this.user_is_admin === true) {
                return "breadcrumb-switchable breadcrumb-item";
            }

            return "breadcrumb-item";
        },
        is_ellipsis_displayed() {
            if (this.is_loading_ascendant_hierarchy) {
                return false;
            }

            return this.current_folder_ascendant_hierarchy.length > this.max_nb_to_display;
        },
        ellipsis_title() {
            return this.$gettext("Parent folders are not displayed to not clutter the interface");
        },
        current_folder_ascendant_hierarchy_to_display() {
            return this.current_folder_ascendant_hierarchy
                .filter((parent) => parent.parent_id !== 0)
                .slice(-this.max_nb_to_display);
        },
        is_current_document_displayed() {
            return this.currently_previewed_item !== null && this.current_folder !== null;
        },
    },
};
</script>
