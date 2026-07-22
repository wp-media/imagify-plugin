<?php

$long_strings = [];

for ( $i = 0; $i < 50; $i++ ) {
	$long_strings[] = str_repeat( 'a', 300 ) . $i;
}

$huge_value = str_repeat( 'x', 5000 );

return [
	'test_data' => [
		// An empty input should return an empty array of chunks.
		'shouldReturnEmptyArrayForEmptyInput'          => [
			'config'   => [
				'values' => [],
				'budget' => 8000,
			],
			'expected' => [
				'chunk_count'     => 0,
				'preserves_order' => true,
			],
		],

		// When the whole list fits under the budget, only one chunk should be returned.
		'shouldReturnSingleChunkWhenUnderBudget'       => [
			'config'   => [
				'values' => range( 1, 100 ),
				'budget' => 8000,
			],
			'expected' => [
				'chunk_count'     => 1,
				'preserves_order' => true,
			],
		],

		// Integer IDs should split into several chunks once the budget is exceeded,
		// no value lost, order preserved, every chunk under budget.
		'shouldSplitIntegersIntoMultipleChunks'        => [
			'config'   => [
				'values' => range( 100000, 100999 ),
				'budget' => 100,
			],
			'expected' => [
				'min_chunks'          => 2,
				'max_rendered_length' => 100,
				'preserves_order'     => true,
			],
		],

		// Long quoted string values (e.g. file paths) should split once the rendered
		// (quoted) length exceeds the budget.
		'shouldSplitLongStringValuesIntoMultipleChunks' => [
			'config'   => [
				'values' => $long_strings,
				'budget' => 1000,
			],
			'expected' => [
				'min_chunks'          => 2,
				'max_rendered_length' => 1000,
				'quoted'              => true,
				'preserves_order'     => true,
			],
		],

		// A single value that alone exceeds the budget should still be returned,
		// alone in its own chunk, rather than being dropped.
		'shouldKeepOversizedSingleValueAloneInItsOwnChunk' => [
			'config'   => [
				'values' => [ 1, $huge_value, 2 ],
				'budget' => 100,
			],
			'expected' => [
				'oversized_value' => $huge_value,
				'preserves_order' => true,
			],
		],

		// An invalid budget (0 or negative) should fall back to the default budget
		// instead of producing a chunk per value.
		'shouldFallBackToDefaultBudgetWhenGivenInvalidValue' => [
			'config'   => [
				'values' => range( 1, 10 ),
				'budget' => 0,
			],
			'expected' => [
				'chunk_count'     => 1,
				'preserves_order' => true,
			],
		],
	],
];
