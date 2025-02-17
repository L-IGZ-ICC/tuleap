<!---
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
    <div>
        <breadcrumb
            v-bind:project_public_name="public_name"
            v-bind:project_short_name="short_name"
            v-bind:project_privacy="privacy"
            v-bind:project_flags="flags"
        />
        <h1 class="program-management-title-header" v-translate>Backlog</h1>
        <div class="program-backlog" data-test="backlog-section">
            <to-be-planned class="to-be-planned" />
            <div class="planning-divider">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="46">
                    <path
                        class="planning-arrows"
                        fill-rule="evenodd"
                        d="M21.769 9.3l-7.706 7.706a.658.658 0 01-.935 0l-1.724-1.724a.658.658 0 010-.934l5.515-5.515-5.515-5.515a.658.658 0 010-.934L13.128.66c.26-.26.675-.26.935 0l7.706 7.706c.26.26.26.675 0 .934zm-10.287-.39l-4.675 4.676a.4.4 0 01-.567 0L5.194 12.54a.4.4 0 010-.567l3.345-3.346-3.345-3.345a.4.4 0 010-.567L6.24 3.669a.4.4 0 01.567 0l4.675 4.675a.4.4 0 010 .567zM3.084 8.67l-2.25 2.25a.192.192 0 01-.274 0l-.503-.503a.192.192 0 010-.273l1.61-1.61-1.61-1.611a.192.192 0 010-.273l.503-.504a.192.192 0 01.273 0l2.25 2.25a.192.192 0 010 .274zM.194 37.3a.658.658 0 010-.934l7.707-7.706c.26-.26.675-.26.934 0l1.724 1.724c.26.26.26.675 0 .934l-5.514 5.515 5.514 5.515c.26.26.26.675 0 .934l-1.724 1.724a.658.658 0 01-.934 0L.195 37.3zm10.288-.39a.4.4 0 010-.566l4.675-4.675a.4.4 0 01.567 0l1.046 1.046a.4.4 0 010 .567l-3.346 3.345 3.346 3.346a.4.4 0 010 .567l-1.046 1.046a.4.4 0 01-.567 0l-4.675-4.675zm8.398-.241a.192.192 0 010-.273l2.25-2.25a.192.192 0 01.273 0l.504.503a.192.192 0 010 .273l-1.61 1.61 1.61 1.61a.192.192 0 010 .274l-.504.503a.192.192 0 01-.273 0l-2.25-2.25z"
                    />
                </svg>
            </div>
            <program-increment-list class="program-increment" />
        </div>
        <error-modal v-if="has_modal_error" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import Breadcrumb from "./Breadcrumb.vue";
import ProgramIncrementList from "./Backlog/ProgramIncrement/ProgramIncrementList.vue";
import ToBePlanned from "./Backlog/ToBePlanned/ToBePlanned.vue";
import type { ProjectFlag, ProjectPrivacy } from "@tuleap/vue-breadcrumb-privacy";
import { init } from "@tuleap/drag-and-drop";
import type {
    Drekkenov,
    PossibleDropCallbackParameter,
    SuccessfulDropCallbackParameter,
} from "@tuleap/drag-and-drop";
import type { HandleDropContextWithProgramId } from "../helpers/drag-drop";
import {
    canMove,
    invalid,
    isConsideredInDropzone,
    isContainer,
    checkAcceptsDrop,
    checkAfterDrag,
} from "../helpers/drag-drop";
import { Action, State, namespace, Getter } from "vuex-class";
import ErrorModal from "./Backlog/ErrorModal.vue";

const configuration = namespace("configuration");

@Component({
    components: { ToBePlanned, ProgramIncrementList: ProgramIncrementList, Breadcrumb, ErrorModal },
})
export default class App extends Vue {
    private drek!: Drekkenov | undefined;

    @Action
    private readonly handleDrop!: (handle_drop: HandleDropContextWithProgramId) => Promise<void>;

    @State
    private readonly has_modal_error!: boolean;

    @configuration.State
    readonly public_name!: string;

    @configuration.State
    readonly short_name!: string;

    @configuration.State
    readonly privacy!: ProjectPrivacy;

    @configuration.State
    readonly flags!: Array<ProjectFlag>;

    @configuration.State
    readonly can_create_program_increment!: boolean;

    @configuration.State
    readonly program_id!: number;

    @Getter
    readonly hasAnElementMovedInsideIncrement!: boolean;

    beforeDestroy(): void {
        window.removeEventListener("beforeunload", this.beforeUnload);
        if (this.drek) {
            this.drek.destroy();
        }
    }

    mounted(): void {
        window.addEventListener("beforeunload", this.beforeUnload);

        if (!this.can_create_program_increment) {
            return;
        }

        this.drek = init({
            mirror_container: this.$el,
            isDropZone: isContainer,
            isDraggable: canMove,
            isInvalidDragHandle: invalid,
            isConsideredInDropzone,
            doesDropzoneAcceptDraggable: (context: PossibleDropCallbackParameter): boolean => {
                return checkAcceptsDrop({
                    dropped_card: context.dragged_element,
                    source_cell: context.source_dropzone,
                    target_cell: context.target_dropzone,
                });
            },
            onDrop: async (context: SuccessfulDropCallbackParameter): Promise<void> => {
                await this.handleDrop({ program_id: this.program_id, ...context });
            },
            cleanupAfterDragCallback: (): void => {
                return checkAfterDrag();
            },
        });
    }

    beforeUnload(event: Event): void {
        if (this.hasAnElementMovedInsideIncrement) {
            event.preventDefault();
            event.returnValue = false;
        }
    }
}
</script>
