<?php
/**
 * Helper functions for the Create Content Model plugin.
 *
 * @package create-content-model
 */

/**
 * Iterates through blocks, calling a callback for each block.
 *
 * @param array    $blocks The blocks to be iterated upon.
 * @param callable $callback The callback to apply to each block.
 * @param boolean  $depth_first Whether to go deep first, or breadth first.
 *
 * @return mixed The iterated blocks, or the early return value of the callback.
 */
function content_model_block_walker( $blocks, $callback, $depth_first = true ) {
	foreach ( $blocks as &$block ) {
		if ( $depth_first && ! empty( $block['innerBlocks'] ) ) {
			$iteration = content_model_block_walker( $block['innerBlocks'], $callback, $depth_first );

			if ( ! is_array( $iteration ) ) {
				return $iteration;
			}

			$block['innerBlocks'] = $iteration;
		}

		$iteration = call_user_func( $callback, $block );

		if ( ! is_array( $iteration ) ) {
			return $iteration;
		}

		$block = $iteration;

		if ( ! $depth_first && ! empty( $block['innerBlocks'] ) ) {
			$iteration = content_model_block_walker( $block['innerBlocks'], $callback, $depth_first );

			if ( ! is_array( $iteration ) ) {
				return $iteration;
			}

			$block['innerBlocks'] = $iteration;
		}
	}

	return $blocks;
}
