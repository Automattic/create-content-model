<?php
/**
 * Handles HTML nodes.
 *
 * @package create-content-model
 */

declare( strict_types = 1 );

/**
 * Handles HTML associated with a block.
 */
class Content_Model_Html_Manipulator {
	/**
	 * The DOM tree from the $markup.
	 *
	 * @var DOMDocument
	 */
	private $dom;

	/**
	 * Creates an instance of the handler, with the HTML to be manipulated as the argument.
	 *
	 * @param string $markup The HTML to be manipulated.
	 */
	public function __construct( $markup ) {
		$dom = new DOMDocument();
		$dom->loadXML( $markup, LIBXML_NOXMLDECL );

		$this->dom = $dom;
	}

	/**
	 * Extract attribute value from the markup.
	 *
	 * @param array $attribute_metadata The attribute metadata from the block.json file.
	 *
	 * @return mixed|null The attribute value.
	 */
	public function extract_attribute( $attribute_metadata ) {
		$matches = $this->get_matches( $attribute_metadata['selector'] );

		if ( ! $matches ) {
			return null;
		}

		foreach ( $matches as $match ) {
			if ( $match instanceof \DOMElement ) {
				if ( 'attribute' === $attribute_metadata['source'] ) {
					return $match->getAttribute( $attribute_metadata['attribute'] );
				}

				return implode(
					'',
					array_map(
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						fn( $node ) => $node->ownerDocument->saveXML( $node ),
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
						iterator_to_array( $match->childNodes ),
					)
				);
			}
		}

		return null;
	}

	/**
	 * Replace attribute value in the markup.
	 *
	 * @param array $attribute_metadata The attribute metadata from the block.json file.
	 * @param mixed $attribute_value The attribute value.
	 *
	 * @return string The updated markup.
	 */
	public function replace_attribute( $attribute_metadata, $attribute_value ) {
		$matches = $this->get_matches( $attribute_metadata['selector'] );

		foreach ( $matches as $match ) {
			if ( $match instanceof \DOMElement ) {
				if ( 'attribute' === $attribute_metadata['source'] ) {
					$attribute = $attribute_metadata['attribute'];
					$value     = $match->getAttribute( $attribute );

					if ( 'class' === $attribute ) {
						$value .= ' ' . $attribute_value;
					} else {
						$value = $attribute_value;
					}

					$match->setAttribute( $attribute, $value );
				} else {
					self::swap_element_inner_html( $match, $attribute_value );
				}
			}
		}

		return $this->get_markup();
	}

	/**
	 * Returns matches for a given selector.
	 *
	 * @param string $selector The selector.
	 *
	 * @return DOMNodeList|false
	 */
	private function get_matches( $selector ) {
		$xpath = new DOMXPath( $this->dom );

		$query = '(//*';

		foreach ( explode( ',', $selector ) as $possible_selector ) {
			$query .= ' | ' . $possible_selector;
		}

		$query .= ')[last()]';

		return $xpath->query( $query );
	}

	/**
	 * Returns the markup associated with the DOMDocument instance.
	 *
	 * @return string
	 */
	private function get_markup() {
		return $this->dom->saveXML( $this->dom->documentElement, LIBXML_NOXMLDECL );
	}

	/**
	 * Swap the node's innerHTML with the given HTML.
	 *
	 * @param \DOMElement $node The HTML node.
	 * @param string      $html The desired inner HTML.
	 *
	 * @return void
	 */
	private static function swap_element_inner_html( $node, $html ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$fragment = $node->ownerDocument->createDocumentFragment();
		$fragment->appendXML( $html );

		while ( $node->hasChildNodes() ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$node->removeChild( $node->firstChild );
		}

		$node->appendChild( $fragment );
	}
}
