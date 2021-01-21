<template>
    <default-field :field="field" :errors="errors">
        <template slot="field">
            <multiselect
                v-model="value"
                :options="options"
                :multiple="field.multiselect"
                :searchable="true"
                :disabled="isReadonly"
                track-by="value"
                label="label"
                :placeholder="field.placeholder"
                :selectLabel="field.selectLabel"
                :deselectLabel="field.deselectLabel"
                :selectedLabel="field.selectedLabel"
                @input="onChange">
            </multiselect>
        </template>
    </default-field>
</template>

<script>
import { FormField, HandlesValidationErrors } from 'laravel-nova';
import Multiselect from "vue-multiselect";

export default {
    components: { Multiselect },
    mixins: [FormField, HandlesValidationErrors],

    props: ['resourceName', 'resourceId', 'field'],

    data() {
        return {
            options: []
        };
    },

    created() {
        if (this.field.dependsOn) {
            this.field.dependsOn.forEach(function(item) {
                let dependsOn = this.checkForNestedForm() ? this.addNestedFormPrefix() : this.addFlexibleContentPrefix(item, this.field);
                Nova.$on("nova-dynamic-select-changed-" + dependsOn, this.onDependencyChanged);
            }, this);
        }
    },

    beforeDestroy() {
        if (this.field.dependsOn) {
            this.field.dependsOn.forEach(function(item) {
                let dependsOn = this.checkForNestedForm() ? this.addNestedFormPrefix() : this.addFlexibleContentPrefix(item, this.field);
                Nova.$off("nova-dynamic-select-changed-" + dependsOn, this.onDependencyChanged);
            }, this);
        }
    },

    methods: {

        /*
         * Check if a property exists, set by Nested Forms package
         */
        checkForNestedForm() {
            return this.field.hasOwnProperty('originalAttribute');
        },

        /*
         * Adds the nested version of the attribute
         */
        addNestedFormPrefix() {
            return this.field.attribute.replace(/\[[^\][]*\]$/, '['+ this.field.dependsOn +']');
        },

        addFlexibleContentPrefix(item, field) {
            var splitted = field.attribute.toLowerCase().split('__');
            return (splitted.length === 2 ? splitted[0] + '__' : '') + item;
        },

        removeFlexibleContentPrefix(field) {
            return field.split('__').length === 2 ? field.split('__')[field.split('__').length - 1] : field
        },

        /*
         * Set the initial, internal value for the field.
         */
        setInitialValue() {
            this.options = this.field.options;

            if (this.field.value) {
                if (this.field.multiselect) {
                    this.value = this.field.value;
                } else {
                    this.value = this.options.find(item => item['value'] == this.field.value);
                }
            }
        },

        /**
         * Fill the given FormData object with the field's internal value.
         */
        fill(formData) {
            if (this.field.multiselect) {
                if (this.value && this.value.length) {
                    this.value.forEach((v, i) => {
                        formData.append(`${this.field.attribute}[${i}]`, JSON.stringify(v));
                    });
                } else {
                    formData.append(this.field.attribute, '');
                }
            } else {
                formData.append(
                    this.field.attribute,
                    typeof this.value == 'undefined' || !this.value ? '' : this.value.value
                )
            }
        },

        /**
         * Update the field's internal value.
         */
        handleChange(value) {
            this.value = value
        },

        getDependValues(value, field) {
            this.field.dependValues[this.removeFlexibleContentPrefix(field)] = value;
            return this.field.dependValues;
        },

        async onChange(row) {
            Nova.$emit("nova-dynamic-select-changed-" + this.field.attribute.toLowerCase(), {
                value: row ? row.value : null,
                field: this.field
            });
        },

        async onDependencyChanged(dependsOnValue) {
            Nova.$emit("nova-dynamic-select-changed-" + this.field.attribute.toLowerCase(), {
                value: this.value,
                field: this.field
            });

            let originalDependsOnAttribute = this.field.originalAttribute
                ? dependsOnValue.field.originalAttribute.toLowerCase()
                : dependsOnValue.field.attribute.toLowerCase();

            this.options = (await Nova.request().post("/nova-vendor/dynamic-select/options/"+this.resourceName, {
                attribute: this.field.originalAttribute ? this.field.originalAttribute : this.removeFlexibleContentPrefix(this.field.attribute),
                depends: this.getDependValues(dependsOnValue.value, originalDependsOnAttribute)
            })).data.options;

            if(this.value) {
                this.value = this.options.find(item => item['value'] == this.value['value']);
            }
        }
    },
}
</script>

<style src="vue-multiselect/dist/vue-multiselect.min.css"></style>
<style>
    .multiselect {
        box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.05);
        min-height: 36px !important;
        border-radius: 0.5rem;
    }
    .multiselect__tags {
        min-height: 36px !important;
        border: 1px solid var(--60) !important;
        color: var(--80);
        border-radius: 0.5rem !important;
    }
    .multiselect__select {
        background-repeat: no-repeat;
        background-size: 10px 6px;
        background-position: center right 0.75rem;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 6"><path fill="#35393C" fill-rule="nonzero" d="M8.293.293a1 1 0 0 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4A1 1 0 0 1 1.707.293L5 3.586 8.293.293z"/></svg>');
    }
    .multiselect__select:before {
        content: none !important;
    }
</style>
