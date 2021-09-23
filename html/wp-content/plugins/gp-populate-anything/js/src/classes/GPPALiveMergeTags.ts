import debounce from 'lodash.debounce';
import getFormFieldValues from '../helpers/getFormFieldValues';

const $ = window.jQuery;

export default class GPPALiveMergeTags {

	public $registeredEls!:JQuery;
	public formId!:string|number;
	public mergeTagValuesPromise!:JQueryXHR;

	constructor () {
		this.getRegisteredEls();
		this.bind();
	}

	loadAndPopulateMergeTags = debounce(() => {
		this.showLoadingIndicators();

		if (this.mergeTagValuesPromise && this.mergeTagValuesPromise.state() === 'pending') {
			this.mergeTagValuesPromise.abort();
		}

		this.mergeTagValuesPromise = this.getMergeTagValues();
		this.mergeTagValuesPromise.then(this.replaceMergeTagValues);
	}, 50);

	onPageChange = () => {
		this.getRegisteredEls();
		this.getFormId();

		this.loadAndPopulateMergeTags();
	};

	bind () {
		/* TODO: make sure this works with batch updates, page changes, and input changes that aren't in batch updates */
		/* TODO: Do not update merge tags that don't have an updated field */
		$(document).on('change keyup', '.gform_fields input, .gform_fields select, .gform_fields textarea', function (this: Element, event) {
			if ($(this).closest('.gfield_trigger_change').length) {
				return;
			}

			window.gf_raw_input_change(event, this);
		});

		$(document).on('gform_post_render', this.onPageChange);
		$(document).on('gppa_updated_batch_fields', this.onPageChange);

		window.gform.addAction('gform_input_change', this.loadAndPopulateMergeTags, 10);
	}

	getFormId () {
		this.formId = $('input[name="gform_submit"],input[name="wc_gforms_form_id"]').val();

		return this.formId;
	}

	getRegisteredEls () {
		this.$registeredEls = $('[data-gppa-live-merge-tag]');
	}

	getRegisteredMergeTags () {
		const mergeTags:string[] = [];

		this.$registeredEls.each(function (this: Element) {
			mergeTags.push($(this).data('gppa-live-merge-tag'));
		});

		return mergeTags;
	}

	getMergeTagValues () {

		if (!this.$registeredEls.length) {
			return $.when() as JQueryXHR;
		}

		return $.post(window.GPPA_AJAXURL, {
			'action': 'gppa_get_live_merge_tag_values',
			'form-id': this.formId,
			'field-values': getFormFieldValues(this.formId),
			'merge-tags': this.getRegisteredMergeTags(),
			'security': window.GPPA_NONCE
		}, () => {}, 'json');

	}

	showLoadingIndicators () {
		this.$registeredEls.each(function (this: Element) {
			$(this).html(window.GPPA_I18N.loadingEllipsis);
		});
	}

	replaceMergeTagValues = (mergeTagValues: any) => {
		this.$registeredEls.each(function (this: Element) {
			var elementMergeTag = $(this).data('gppa-live-merge-tag');

			if (!(elementMergeTag in mergeTagValues)) {
				return;
			}

			$(this).html(mergeTagValues[elementMergeTag]);
		});

		return $.when();
	}

}
