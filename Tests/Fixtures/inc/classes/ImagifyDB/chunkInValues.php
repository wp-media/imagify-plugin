<?php

$long_strings = [];

for ( $i = 0; $i < 50; $i++ ) {
	$long_strings[] = str_repeat( 'a', 300 ) . $i;
}

$huge_value = str_repeat( 'x', 5000 );

return [
	'test_data' => [
		// An empty input should return an empty array of chunks.
		'shouldReturnEmptyArrayForEmptyInput'              => [
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
		'shouldReturnSingleChunkWhenUnderBudget'           => [
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
		// no value lost, order preserved, every chunk under budget and packed full.
		// 1000 values of 6 chars: 14 per chunk (6 + 13 * 7 = 97), so 71 full chunks + 1.
		'shouldSplitIntegersIntoMultipleChunks'            => [
			'config'   => [
				'values' => range( 100000, 100999 ),
				'budget' => 100,
			],
			'expected' => [
				'chunk_count'         => 72,
				'max_rendered_length' => 100,
				'fully_packed'        => true,
				'preserves_order'     => true,
			],
		],

		// Long quoted string values (e.g. file paths) should split once the rendered
		// (quoted) length exceeds the budget.
		// 50 values rendering to 303/304 chars: 3 per chunk, so 16 full chunks + 1.
		'shouldSplitLongStringValuesIntoMultipleChunks'    => [
			'config'   => [
				'values' => $long_strings,
				'budget' => 1000,
			],
			'expected' => [
				'chunk_count'         => 17,
				'max_rendered_length' => 1000,
				'quoted'              => true,
				'fully_packed'        => true,
				'preserves_order'     => true,
			],
		],

		// Regression: every chunk after the first must be filled up to the budget too.
		// A chunk length counter that is not reset on flush yields one value per chunk
		// from the second chunk on (31 chunks instead of 3 here).
		'shouldFillEveryChunkUpToTheBudget'                => [
			'config'   => [
				'values' => range( 1, 50 ),
				'budget' => 50,
			],
			'expected' => [
				'chunk_count'         => 3,
				'exact_chunks'        => [
					range( 1, 20 ),
					range( 21, 37 ),
					range( 38, 50 ),
				],
				'max_rendered_length' => 50,
				'fully_packed'        => true,
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
