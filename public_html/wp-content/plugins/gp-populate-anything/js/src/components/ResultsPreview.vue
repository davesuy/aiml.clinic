<template>
	<div :class="{ 'gppa-results': true, 'gppa-results-loading': loading }"
		 v-if="field && objectTypeInstance.fieldValueObject !== true">
		<div id="gppa-results-thickbox" style="display: none;" v-if="results && results.length">
			<div class="gppa-results-preview-contents">
				<table class="wp-list-table widefat fixed striped">
					<thead>
					<th v-for="column in resultColumns">{{ column }}</th>
					</thead>
					<tbody>
					<tr v-for="row in results">
						<td v-for="columnValue in row">{{ columnValue }}</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>

		<template v-if="hasFilterFieldValue">
			<strong>Heads-up!</strong> Cannot preview results when filtering by<br/>Form Field Value.
		</template>
		<template v-else-if="missingTemplates.length">
			Select
			<span v-for="(missingTemplate, index) in missingTemplates">
						<strong>{{missingTemplate}}</strong><span
				v-if="index + 1 < missingTemplates.length"> and </span>
					</span>
			{{ missingTemplates.length > 1 ? 'templates' : 'template' }} to preview results.
		</template>
		<template v-else-if="results && 'error' in results">
			<strong>Error Loading Results:</strong> <code>{{ results.error }}</code>
		</template>
		<template v-else-if="loading">
			<img :src="gfBaseUrl + '/images/spinner.gif'"/> Loading Results
		</template>
		<template v-else-if="results && results.length === 0">
			<strong>{{ results.length }}</strong> results found
		</template>
		<template v-else-if="results && results.length > 0">
			<a class="thickbox" title="Results Preview"
			   href="#TB_inline?width=600&height=450&inlineId=gppa-results-thickbox"
			   onClick="tb_click.call(this);">
				<strong>{{ results.length }}{{ results.length > 500 ? '+' : '' }}</strong> {{ results.length ===
				1 ? 'result' : 'results' }}</a>
			found.
		</template>
	</div>
</template>

<script lang="ts">
	import Vue from 'vue';
	import debounce from 'lodash.debounce';

	const $ = window.jQuery;

	const updateResults = function () {
		var vm = this;

		if (this.missingTemplates.length || this.hasFilterFieldValue) {
			return;
		}

		this.loading = true;

		this.getPreviewResults().done(function (results) {
			vm.loading = false;
			vm.results = results;
		}).fail(function () {
			vm.results = null;
			vm.loading = false;
		});
	};

	export default Vue.extend({
		data: function () {
			return {
				loading: false,
				previewResultsPromise: null,
				results: null,
				gfBaseUrl: window.GPPA_GF_BASEURL,
			};
		},
		created: function () {
			this.updateResults();
		},
		props: [
			'populate',
			'enabled',
			'field',
			'objectTypeInstance',
			'filterGroups',
			'templates',
			'templateRows',
			'orderingMethod',
			'orderingProperty',
			'uniqueResults',
		],
		watch: {
			filterGroups: {
				handler: function () {
					this.updateResultsDebounced();
				},
				deep: true,
			},
			templates: {
				handler: function () {
					this.updateResultsDebounced();
				},
				deep: true,
			},
			orderingProperty: function () {
				this.updateResults();
			},
			orderingMethod: function () {
				this.updateResults();
			},
			uniqueResults: function () {
				this.updateResults();
			},
			objectTypeInstance: function () {
				this.results = null;
			},
		},
		computed: {
			resultColumns: function () {
				if (!this.results || !this.results.length) {
					return [];
				}

				return Object.keys(this.results[0]);
			},
			hasFilterFieldValue: function () {

				var hasFilterFieldValue = false;

				this.filterGroups.forEach(function (filterGroup) {
					filterGroup.forEach(function (filter) {
						if (typeof filter.value === 'string' && filter.value.indexOf('gf_field') === 0) {
							hasFilterFieldValue = true;
						}
					});
				});

				return hasFilterFieldValue;

			},
			missingTemplates: function () {
				var vm = this;
				var missingTemplates = [];

				this.templateRows.forEach(function (templateRow) {
					if (!(templateRow.id in vm.templates) || !vm.templates[templateRow.id]) {
						missingTemplates.push(templateRow.label);
					}
				});

				return missingTemplates;
			},
		},
		methods: {
			updateResults: updateResults,
			updateResultsDebounced: debounce(updateResults, 500),
			getPreviewResults: function () {
				if (this.previewResultsPromise && this.previewResultsPromise.state() !== 'resolved') {
					this.previewResultsPromise.abort();
				}

				this.previewResultsPromise = $.post(window.ajaxurl, {
					action: 'gppa_get_query_results',
					templateRows: this.templateRows,
					gppaPopulate: this.populate,
					fieldSettings: JSON.stringify(window.field)
				}, null, 'json');

				return this.previewResultsPromise;
			},
		}
	});
</script>
