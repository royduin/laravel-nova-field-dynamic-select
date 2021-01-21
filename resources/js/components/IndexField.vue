<template>
    <span>{{ value }}</span>
</template>

<script>
export default {
    props: ['resourceName', 'field'],
    computed: {
        value: function () {
            if (this.field.multiselect) {
                return this.field.value.map((val) => val.label).join(', ')
            }

            return this.field.options.find(item => item['value'] == this.field.value).label;
        }
    }
}
</script>
