const $ = window.jQuery;

type fieldID = number|string;

export interface fieldMapFilter {
	gf_field: string
	operator?: string
	property?: string
}

export interface fieldMap {
	[fieldId: string]: fieldMapFilter[]
}

interface fieldDetails {
	field: fieldID
	filters?: fieldMapFilter[]
	$el?: JQuery
	hasChosen: boolean
}

export default class GPPopulateAnything {

	public currentPage = 1;
	public populatedFields:fieldID[] = [];
	public postedValues:{ [input: string]: string } = {};

	constructor(public formId: fieldID, public fieldMap: fieldMap) {

		if ('GPPA_POSTED_VALUES_' + formId in window) {
			this.postedValues = (window as any)['GPPA_POSTED_VALUES_' + formId];
		}

		jQuery(document).on('gform_post_render', this.postRenderSetCurrentPage);
		jQuery(document).on('gform_post_render', this.postRender);

	}

	postRenderSetCurrentPage = (event: JQueryEventObject, formId: any, currentPage: number) => {
		this.currentPage = currentPage;
	};

	postRender = (event: JQueryEventObject, formId: any, currentPage: number) => {

		if (formId != this.formId) {
			return;
		}

		const initialFieldsToLoad:{ field: string, filters: any[] }[] = [];

		/* Bind to change */
		let $form = $('#gform_' + this.formId);

		/* Use entry form if we're in the Gravity Forms admin entry view. */
		if ($('#wpwrap #entry_form').length) {
			$form = $('#entry_form');
		}

		$form.on('change', '[name^="input_"]',  ({ currentTarget: el }) => {
			const fieldId = parseInt($(el).attr('name').replace(/^input_/, ''));
			const dependentFieldIds = this.getDependentFields(fieldId);

			this.getBatchFieldHTML(dependentFieldIds);
		});


		for ( const [gppaField, filters] of Object.entries(this.fieldMap) ) {
			if (
				this.getFieldPage(gppaField) != this.currentPage
				&& this.populatedFields.includes(gppaField)
			) {
				continue;
			}

			if (this.fieldHasPostedValue(gppaField)) {
				continue;
			}

			initialFieldsToLoad.push({field: gppaField, filters: filters});
		}

		if (initialFieldsToLoad.length) {
			this.getBatchFieldHTML(initialFieldsToLoad);
		}

		$form.on('submit', ({ currentTarget: form }) => {
			$(form).find('[name^="input_"]').each( (index, el: Element) => {
				var $el = $(el);
				var id = $el.attr('name').replace('input_', '');
				var fieldId = parseInt(id);

				if (this.getFieldPage(fieldId) != this.currentPage) {
					return;
				}

				this.postedValues[id] = $el.val();
			});
		});

	};

	getFieldFilterValues(filters:fieldMapFilter[]) {

		let $form = $('#gform_' + this.formId);

		/* Use entry form if we're in the Gravity Forms admin entry view. */
		if ($('#wpwrap #entry_form').length) {
			$form = $('#entry_form');
		}

		const formInputValues = $form.serializeArray();
		const gfFieldFilters:string[] = [];
		const values:{ [input: string]: string } = {};

		for ( const filter of filters ) {
			gfFieldFilters.push('input_' + filter.gf_field);
		}

		for ( const input of formInputValues ) {
			if (gfFieldFilters.indexOf(input.name) === -1) {
				continue;
			}

			values[input.name.replace('input_', '')] = input.value;
		}

		return values;

	}

	/**
	 * This is primarily used for field value objects since it has to traverse up
	 * and figure out what other filters are required.
	 *
	 * Regular filters work without this since all of the filters are present in the single field.
	 **/
	recursiveGetDependentFilters(filters:fieldMapFilter[]) {

		let dependentFilters:fieldMapFilter[] = [];

		for ( const filter of filters ) {
			if ('property' in filter || !('gf_field' in filter)) {
				continue;
			}

			var currentField = filter.gf_field;

			if (!(currentField in this.fieldMap)) {
				continue;
			}

			dependentFilters = dependentFilters
				.concat(this.fieldMap[currentField])
				.concat(this.recursiveGetDependentFilters(this.fieldMap[currentField]));
		}

		return dependentFilters;

	}

	getBatchFieldHTML(requestedFields: { field: fieldID, filters: fieldMapFilter[] }[]) {

		let filters:fieldMapFilter[] = [];

		const fieldIDs:fieldID[] = [];
		const fields:fieldDetails[] = [];

		/* Process field array and populate filters */
		for ( const fieldDetails of requestedFields ) {
			const fieldID = fieldDetails.field;

			if (fieldIDs.includes(fieldID)) {
				continue;
			}

			fields.push(Object.assign({}, fieldDetails, {
				$el: $('#field_' + this.formId + '_' + fieldID),
				hasChosen: !!$('#input_' + this.formId + '_' + fieldID).data('chosen')
			}));

			filters = filters
				.concat(fieldDetails.filters)
				.concat(this.recursiveGetDependentFilters(fieldDetails.filters));

			fieldIDs.push(fieldID);
		}

		fields.sort((a, b) => {
			var aIndex = a.$el!.index('[id^=field]');
			var bIndex = b.$el!.index('[id^=field]');

			return aIndex - bIndex;
		});

		const fieldValues = this.getFieldFilterValues(filters);

		$.each(fields, function (index, fieldDetails) {

			var fieldID = fieldDetails.field;
			var $el = fieldDetails.$el!;
			var $fieldContainer = $el.find('.clear-multi, .gform_hidden, .ginput_container').first();
			var spinnerSource = window.GPPA_GF_BASEURL + '/images/spinner.gif';

			/* Prevent multiple choices hidden inputs */
			$el
				.closest('form')
				.find('input[type="hidden"][name="choices_' + fieldID + '"]')
				.remove();

			$fieldContainer.html(`<div class="gppa-loading">
				<img class="gfspinner"  src="${spinnerSource}" />${window.GPPA_I18N.loadingEllipsis}
			</div>`);

		});

		return $.post(window.GPPA_AJAXURL, {
			'action': 'gppa_get_batch_field_html',
			'form-id': this.formId,
			'lead-id': null,
			'field-ids': fields.map((field) => {
				return field.field;
			}),
			'field-values': fieldValues,
			'security': window.GPPA_NONCE,
		},  (fieldHTMLResults) => {

			for ( const fieldDetails of fields ) {
				var fieldID = fieldDetails.field;
				var $field = fieldDetails.$el!;
				var $fieldContainer = $field.find('.clear-multi, .gform_hidden, .ginput_container').first();

				$fieldContainer.replaceWith(fieldHTMLResults[fieldID]);

				this.populatedFields.push(fieldID);

				if( fieldDetails.hasChosen ) {
					window.gformInitChosenFields( ('#input_{0}_{1}' as any).format( this.formId, fieldID ), window.GPPA_I18N.chosen_no_results );
				}

				window.gform.doAction('gform_input_change', $fieldContainer, this.formId, fieldID);
			}

			this.runAndBindCalculationEvents();

			$(document).trigger('gppa_updated_batch_fields');

		}, 'json');

	}

	/**
	 * Run the calculation events for any field that is dependent on a GPPA-populated field that has been updated.
	 */
	runAndBindCalculationEvents() {

		if (!window.gf_global || !window.gf_global.gfcalc || !window.gf_global.gfcalc[this.formId]) {
			return;
		}

		var GFCalc = window.gf_global.gfcalc[this.formId];

		for (var i = 0; i < GFCalc.formulaFields.length; i++) {
			var formulaField = $.extend({}, GFCalc.formulaFields[i]);
			GFCalc.runCalc(formulaField, this.formId);
		}

	}

	getFieldPage(fieldId:fieldID) {

		var $field = $('#field_' + this.formId + '_' + fieldId);
		var $page = $field.closest('.gform_page');

		if (!$page.length) {
			return 1;
		}

		return $page.prop('id').replace('gform_page_' + this.formId + '_', '');

	}

	getDependentFields(fieldId:fieldID) {

		const dependentFields = [];

		let currentFieldDependents;
		let currentFields = [fieldId.toString()];

		while (currentFields) {

			currentFieldDependents = [];

			for ( const [field, filters] of Object.entries(this.fieldMap) ) {
				for ( const filter of Object.values(filters) ) {
					if ('gf_field' in filter && currentFields.includes(filter.gf_field.toString())) {
						currentFieldDependents.push(field);
						dependentFields.push({field: field, filters: filters});
					}
				}
			}

			if (!currentFieldDependents.length) {
				break;
			}

			currentFields = currentFieldDependents;

		}

		return dependentFields;

	}

	fieldHasPostedValue(fieldId:fieldID) {

		var hasPostedField = false;

		for ( const inputId of Object.keys(this.postedValues) ) {
			const currentFieldId = parseInt(inputId);

			if (currentFieldId == fieldId) {
				hasPostedField = true;

				break;
			}
		}

		return hasPostedField;

	}

}
