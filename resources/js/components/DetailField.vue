<template>
    <div>
        <panel-item :field="dynamicField" v-if="dynamicField.multiselect">
            <template slot="value">
                <div class="relative rounded-lg bg-white shadow border border-60" v-if="dynamicField.value.length">
                    <div class="overflow-hidden rounded-b-lg rounded-t-lg">
                        <div class="border-b border-50 cursor-text font-mono text-sm py-2 px-4" v-for="(option, i) of dynamicField.value" :key="i">
                            {{ option.label }}
                        </div>
                    </div>
                </div>

                <div v-else>—</div>
            </template>
        </panel-item>
        <panel-item :field="dynamicField" v-else />
    </div>
</template>

<script>
export default {
    props: ['resource', 'resourceName', 'resourceId', 'field'],

    computed: {
        dynamicField() {
            if (this.field.multiselect) {
                return this.field;
            }

            this.field.value = this.field.options.find(item => item['value'] == this.field.value).label;
            return this.field;
        },
    },
}
</script>
