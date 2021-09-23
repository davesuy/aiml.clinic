/* Polyfills */
import 'core-js/es/object/assign'
import 'core-js/es/object/values'
import 'core-js/es/object/entries'

import GPPopulateAnything, {fieldMap} from './classes/GPPopulateAnything';
import GPPALiveMergeTags from './classes/GPPALiveMergeTags';
import deepmerge from 'deepmerge';

const gppaMergedFieldMaps:{ [formId: string]: fieldMap } = {};

for( const prop in window ) {
	if ( window.hasOwnProperty( prop ) &&
		( prop.indexOf( 'GPPA_FILTER_FIELD_MAP' ) === 0 || prop.indexOf( 'GPPA_FIELD_VALUE_OBJECT_MAP' ) === 0 )
	) {
		const formId = prop.split('_').pop() as string;
		const map = (window as any)[ prop ];

		if ( !(formId in gppaMergedFieldMaps) ) {
			gppaMergedFieldMaps[formId] = {};
		}

		gppaMergedFieldMaps[formId] = deepmerge(gppaMergedFieldMaps[formId], map[formId]);
	}
}

window.gppaForms = {};

for ( const [formId, fieldMap] of Object.entries(gppaMergedFieldMaps) ) {
	window.gppaForms[formId] = new GPPopulateAnything(formId, fieldMap);
}

window.gppaLiveMergeTags = new GPPALiveMergeTags();
