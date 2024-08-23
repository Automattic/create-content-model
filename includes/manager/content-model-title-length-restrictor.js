import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';
import { registerPlugin } from '@wordpress/plugins';

const ContentModelLengthRestrictor = () => {
	const { editPost } = useDispatch( editorStore );
	const { createNotice } = useDispatch( noticesStore );

	const title = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'title' )
	);

	useEffect( () => {
		if ( title && title.length > 20 ) {
			editPost( { title: title.substring( 0, 20 ) } );
			createNotice(
				'warning',
				__( 'Title is limited to 20 characters.' ),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	}, [ title, editPost, createNotice ] );
};

registerPlugin( 'content-model-title-length-restrictor', {
	render: ContentModelLengthRestrictor,
} );
