import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';

export const useContentModelNameLengthRestrictor = () => {
	const { editPost, lockPostSaving, unlockPostSaving } =
		useDispatch( editorStore );
	const { createNotice } = useDispatch( noticesStore );

	const title = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'title' )
	);

	useEffect( () => {
		const trimmedTitle = title?.trim() ?? '';

		if ( trimmedTitle.length === 0 ) {
			lockPostSaving( 'title-empty-lock' );
			return;
		}

		unlockPostSaving( 'title-empty-lock' );

		editPost( { title: trimmedTitle.substring( 0, 20 ) } );

		if ( trimmedTitle.length > 20 ) {
			createNotice(
				'warning',
				__( 'Title is limited to 20 characters.' ),
				{
					type: 'snackbar',
					isDismissible: true,
				}
			);
		}
	}, [ title, lockPostSaving, unlockPostSaving, editPost, createNotice ] );
};
