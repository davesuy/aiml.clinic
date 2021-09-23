<div>
    <cx-vui-button
        @click="configVisible = !configVisible"
    >
        <template slot="label"><span class="dashicons dashicons-admin-settings"></span></template>
    </cx-vui-button>
    <ul v-show="configVisible">
        <li v-for="(filter, index) in filters">
            <label :key="index">
                <input
                     type="checkbox"
                     name="index"
                     v-model="filters[index].visibility"
                     @check=""
                >
                {{filter.label}}
            </label>
        </li>
    </ul>
</div>