const $ = window.jQuery;

export default function getFormFieldValues(formId?:number|string) {

	var $form = $('#gform_' + formId);

	/* Use entry form if we're in the Gravity Forms admin entry view. */
	if ( $('#wpwrap #entry_form').length ) {
		$form = $('#entry_form');
	}

	var inputsArray = $.grep($form.serializeArray(), function (value?:JQuerySerializeArrayElement) {
		if (!value || value.name.indexOf('input_') !== 0) {
			return false;
		}

		return true;
	});

	var inputsObject:{ [input: string]: string[]|string } = {};

	$.each(inputsArray, function (index, input: any) {
		var value = input.value;
		input = input.name.replace('input_', '');

		/* Handle array-based inputs such as the Time field */
		if (input.indexOf('[]') !== -1) {
			input = input.replace('[]', '');

			if (!(input in inputsObject)) {
				inputsObject[input] = [];
			}

			(inputsObject[input] as string[]).push(value);
		/* Standard inputs */
		} else {
			inputsObject[input] = value;
		}
	});

	return inputsObject;

}
