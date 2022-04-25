Nova.booting((Vue, router, store) => {
    Vue.component('index-dynamic-select', require('./components/IndexField').default);
    Vue.component('detail-dynamic-select', require('./components/DetailField').default);
    Vue.component('form-dynamic-select', require('./components/FormField').default);
})
