( function () {
	'use strict';

	var __ = wp.i18n.__;
	var el = wp.element.createElement;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var SelectControl = wp.components.SelectControl;
	var Placeholder = wp.components.Placeholder;
	var PanelBody = wp.components.PanelBody;
	var ServerSideRender = wp.serverSideRender;

	var data = window.clickwhaleBlockData || {};
	var smartDisplays = data.smartDisplays || [];
	var iconUrl = data.iconUrl || '';

	var icon = iconUrl
		? el( 'img', { src: iconUrl, width: 24, height: 24, alt: '' } )
		: 'star-filled';

	var selectOptions = [ { value: 0, label: __( '— Select Smart Display —', 'clickwhale' ) } ].concat(
		smartDisplays.map( function ( sd ) {
			return { value: sd.id, label: sd.title };
		} )
	);

	registerBlockType( 'clickwhale/smart-display', {
		title: __( 'Smart Display', 'clickwhale' ),
		description: __( 'Embed a Smart Display on your page.', 'clickwhale' ),
		icon: icon,
		category: 'clickwhale',
		keywords: [ 'smart display', 'clickwhale', 'affiliate' ],

		attributes: {
			id: {
				type: 'number',
				default: 0,
			},
		},

		edit: function ( props ) {
			var blockProps = useBlockProps();
			var id = props.attributes.id;

			function onChange( val ) {
				props.setAttributes( { id: parseInt( val, 10 ) || 0 } );
			}

			var inspectorControls = el(
				InspectorControls, null,
				el(
					PanelBody,
					{ title: __( 'Smart Display', 'clickwhale' ), initialOpen: true },
					el( SelectControl, {
						label: __( 'Selected Smart Display', 'clickwhale' ),
						value: id,
						options: selectOptions,
						onChange: onChange,
					} )
				)
			);

			// No smart display selected — show placeholder with inline dropdown
			if ( ! id ) {
				return el(
					'div', blockProps,
					inspectorControls,
					el(
						Placeholder,
						{
							icon: icon,
							label: __( 'Smart Display', 'clickwhale' ),
							instructions: smartDisplays.length
								? __( 'Select a Smart Display to embed on this page.', 'clickwhale' )
								: __( 'No Smart Displays found. Create one first.', 'clickwhale' ),
						},
						smartDisplays.length
							? el( SelectControl, {
								value: id,
								options: selectOptions,
								onChange: onChange,
							} )
							: null
					)
				);
			}

			// Smart display selected — render server-side preview
			return el(
				'div', blockProps,
				inspectorControls,
				el( ServerSideRender, {
					block: 'clickwhale/smart-display',
					attributes: props.attributes,
				} )
			);
		},

		save: function () {
			// Dynamic block — rendered server-side
			return null;
		},
	} );
}() );
