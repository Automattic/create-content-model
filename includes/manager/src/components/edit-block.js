import {
	Button,
	ButtonGroup,
	TextControl,
	__experimentalGrid as Grid,
	CardBody,
	Card,
	__experimentalItemGroup as ItemGroup,
	__experimentalItem as Item,
	Flex,
	FlexBlock,
	FlexItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import {
	blockDefault,
	paragraph,
	image,
	heading,
	group,
	button,
} from '@wordpress/icons';

import { SUPPORTED_BLOCK_ATTRIBUTES } from '../constants';

const EditBlockForm = ( {
	block = {
		label: '',
		slug: '',
		description: '',
		type: 'text',
		visible: false,
	},
	onChange = () => {},
} ) => {
	const [ formData, setFormData ] = useState( block );

	useEffect( () => {
		onChange( formData );
	}, [ formData ] );

	return (
		<>
			<Card>
				<CardBody>
					<ButtonGroup
						style={ {
							marginBottom: '1rem',
						} }
					>
						<Button
							icon={ blockIcon( formData.type ) }
							title={ formData.type }
						>
							{ __( 'Block Binding' ) }
						</Button>
					</ButtonGroup>

					<Grid columns={ 3 }>
						<TextControl
							label={ __( 'Name' ) }
							value={ formData.label }
							disabled={ true }
						/>
						<TextControl
							label={ __( 'Key' ) }
							value={ formData.slug }
							disabled={ true }
						/>
						<TextControl
							label={ __( 'Description (optional)' ) }
							value={ formData.description }
							onChange={ ( value ) =>
								setFormData( {
									...formData,
									description: value,
								} )
							}
						/>
					</Grid>

					<BlockAttributes
						slug={ formData.slug }
						type={ formData.type }
					/>
				</CardBody>
			</Card>
		</>
	);
};

const BlockAttributes = ( { slug, type } ) => {
	const supportedAttributes = SUPPORTED_BLOCK_ATTRIBUTES[ type ];
	return (
		<ItemGroup isBordered isSeparated>
			{ supportedAttributes.map( ( attribute ) => (
				<Item key={ attribute }>
					<Flex>
						<FlexBlock>{ attribute }</FlexBlock>
						<FlexItem>
							<span>
								{ 'post_content' === slug ? (
									<code>{ slug }</code>
								) : (
									<code>{ `${ slug }__${ attribute }` }</code>
								) }
							</span>
						</FlexItem>
					</Flex>
				</Item>
			) ) }
		</ItemGroup>
	);
};

const blockIcon = ( type ) => {
	switch ( type ) {
		case 'core/paragraph':
			return paragraph;
		case 'core/image':
			return image;
		case 'core/heading':
			return heading;
		case 'core/group':
			return group;
		case 'core/button':
			return button;
		default:
			return blockDefault;
	}
};

export default EditBlockForm;
