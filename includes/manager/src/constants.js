// https://github.com/WordPress/WordPress/blob/master/wp-includes/class-wp-block.php#L246-L251
export const SUPPORTED_BLOCK_ATTRIBUTES = {
	'core/group': [ 'content' ],
	'core/paragraph': [ 'content' ],
	'core/heading': [ 'content' ],
	'core/image': [ 'id', 'url', 'title', 'alt' ],
	'core/button': [ 'url', 'text', 'linkTarget', 'rel' ],
};

export const { BINDINGS_KEY, BLOCK_VARIATION_NAME_ATTR, POST_TYPE_NAME } =
	window.contentModelData;
