import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';

export const useContentModelNameLengthRestrictor = () => {
	const { editPost, lockPostSaving, unlockPostSaving } =
		useDispatch( editorStore );
	const { createNotice, removeNotice } = useDispatch( noticesStore );

	const { title, isPublishSidebarOpened, isPublishingPost } = useSelect(
		( select ) => ( {
			title: select( editorStore ).getEditedPostAttribute( 'title' ),
			isPublishSidebarOpened:
				select( editorStore ).isPublishSidebarOpened(),
			isPublishingPost: select( editorStore ).isPublishingPost(),
		} )
	);

	useEffect( () => {
		const trimmedTitle = title?.trim() ?? '';

		if ( trimmedTitle.length === 0 ) {
			lockPostSaving( 'title-empty-lock' );
		} else {
			unlockPostSaving( 'title-empty-lock' );
			removeNotice( 'title-empty-notice' );

			editPost( { title: trimmedTitle.substring( 0, 20 ) } );

			if ( trimmedTitle.length > 20 ) {
				createNotice(
					'warning',
					__( 'Model name is limited to 20 characters.' ),
					{
						id: 'title-length-notice',
						type: 'snackbar',
						isDismissible: true,
					}
				);
			}
		}

		if (
			( isPublishSidebarOpened || isPublishingPost ) &&
			trimmedTitle.length === 0
		) {
			createNotice(
				'warning',
				__( 'Please enter a model name in order to publish.' ),
				{
					id: 'title-empty-notice',
					isDismissible: true,
				}
			);
		}
	}, [
		title,
		isPublishSidebarOpened,
		isPublishingPost,
		lockPostSaving,
		unlockPostSaving,
		editPost,
		createNotice,
		removeNotice,
	] );
};
