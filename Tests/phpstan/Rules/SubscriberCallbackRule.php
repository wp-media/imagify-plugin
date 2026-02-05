<?php

namespace Imagify\Tests\phpstan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * PHPStan rule to validate get_subscribed_events() callbacks for SubscriberInterface implementations.
 *
 * Ensures that:
 * - All array elements have @filter or @action PHPDoc annotations
 * - Filter callbacks return non-void values
 * - Action callbacks return void
 * - Parameter counts match accepted_args when specified
 *
 * @implements Rule<ClassMethod>
 */
class SubscriberCallbackRule implements Rule
{
	public function getNodeType(): string
	{
		return ClassMethod::class;
	}

	public function processNode( Node $node, Scope $scope ): array
	{
		// Only process get_subscribed_events() methods
		if ( $node->name->toString() !== 'get_subscribed_events' ) {
			return [];
		}

		// Check if class implements SubscriberInterface
		if ( ! $this->implementsSubscriberInterface( $scope ) ) {
			return [];
		}

		// Find the array to analyze
		$arrayNode = $this->findArrayNode( $node, $scope );

		if ( $arrayNode === null ) {
			// Skip validation for delegated implementations or complex patterns
			return [];
		}

		// Collect all errors for this method
		$errors = [];

		// Process each array element
		foreach ( $arrayNode->items as $item ) {
			// Skip null items (trailing commas)
			if ( $item === null ) {
				continue;
			}

			// Parse PHPDoc annotation for hook type
			$hookType = $this->getHookType( $item );

			if ( $hookType === null ) {
				// Missing annotation error
				$errors[] = RuleErrorBuilder::message( 'Hook element missing required @filter or @action annotation' )
					->line( $item->getStartLine() )
					->identifier( 'wpmedia.subscriber.missingAnnotation' )
					->addTip( 'Add /** @filter */ or /** @action */ annotation before this array element' )
					->build();
				continue;
			}

			// Extract callback information
			$callbacks = $this->extractCallbacks( $item->value );

			// Validate each callback
			foreach ( $callbacks as $callbackInfo ) {
				$methodName = $callbackInfo['method'];
				$acceptedArgs = $callbackInfo['accepted_args'] ?? null;

				// Validate callback return type
				$returnTypeErrors = $this->validateCallbackReturnType(
					$scope,
					$methodName,
					$hookType,
					$item->getStartLine()
				);
				$errors = array_merge( $errors, $returnTypeErrors );

				// Validate parameter count if accepted_args is specified
				if ( $acceptedArgs !== null ) {
					$paramErrors = $this->validateParameterCount(
						$scope,
						$methodName,
						$acceptedArgs,
						$item->getStartLine()
					);
					$errors = array_merge( $errors, $paramErrors );
				}
			}
		}

		return $errors;
	}

	/**
	 * Check if the current class implements SubscriberInterface
	 */
	private function implementsSubscriberInterface( Scope $scope ): bool
	{
		$classReflection = $scope->getClassReflection();

		if ( $classReflection === null ) {
			return false;
		}

		return $classReflection->implementsInterface( 'Imagify\EventManagement\SubscriberInterface' );
	}

	/**
	 * Find the array node to analyze from the method body
	 * Handles inline arrays and simple variable assignments
	 */
	private function findArrayNode( ClassMethod $method, Scope $scope ): ?Array_
	{
		$stmts = $method->getStmts();

		if ( $stmts === null ) {
			return null;
		}

		$variableArrays = [];

		// Look through statements
		foreach ( $stmts as $stmt ) {
			// Direct return of array
			if ( $stmt instanceof Return_ && $stmt->expr instanceof Array_ ) {
				$array = $stmt->expr;
				// Skip empty arrays
				if ( empty( $array->items ) ) {
					return null;
				}
				return $array;
			}

			// Variable assignment to array
			if ( $stmt instanceof Node\Stmt\Expression
				&& $stmt->expr instanceof Assign
				&& $stmt->expr->var instanceof Variable
				&& $stmt->expr->expr instanceof Array_
			) {
				$varName = $stmt->expr->var->name;
				if ( is_string( $varName ) ) {
					$variableArrays[ $varName ] = $stmt->expr->expr;
				}
			}

			// Return of variable
			if ( $stmt instanceof Return_ && $stmt->expr instanceof Variable ) {
				$varName = $stmt->expr->name;
				if ( is_string( $varName ) && isset( $variableArrays[ $varName ] ) ) {
					$array = $variableArrays[ $varName ];
					// Skip empty arrays
					if ( empty( $array->items ) ) {
						return null;
					}
					return $array;
				}
			}
		}

		// Delegated or complex implementation - skip validation
		return null;
	}

	/**
	 * Get hook type (@filter or @action) from array item comment
	 */
	private function getHookType( ArrayItem $item ): ?string
	{
		$comments = $item->getAttribute( 'comments' );

		if ( empty( $comments ) ) {
			return null;
		}

		// Check all comments attached to this node
		foreach ( $comments as $comment ) {
			$text = $comment->getText();

			// Match // @filter or // @action
			if ( preg_match( '/\/\/\s*@filter\b/i', $text ) ) {
				return 'filter';
			}

			if ( preg_match( '/\/\/\s*@action\b/i', $text ) ) {
				return 'action';
			}
		}

		return null;
	}

	/**
	 * Extract callback information from array value
	 * Handles: 'method', ['method'], ['method', priority], ['method', priority, accepted_args]
	 * and nested arrays for multiple callbacks per hook
	 *
	 * @return array[] Array of callback info: [['method' => string, 'accepted_args' => ?int], ...]
	 */
	private function extractCallbacks( Node\Expr $value ): array
	{
		$callbacks = [];

		// Simple string method name
		if ( $value instanceof String_ ) {
			$callbacks[] = [
				'method' => $value->value,
				'accepted_args' => null,
			];
			return $callbacks;
		}

		// Array format
		if ( $value instanceof Array_ ) {
			// Check if nested array (multiple callbacks for same hook)
			$isNested = false;
			if ( ! empty( $value->items ) ) {
				$firstItem = $value->items[0];
				if ( $firstItem !== null && $firstItem->value instanceof Array_ ) {
					$isNested = true;
				}
			}

			if ( $isNested ) {
				// Process each nested array
				foreach ( $value->items as $nestedItem ) {
					if ( $nestedItem === null || ! ( $nestedItem->value instanceof Array_ ) ) {
						continue;
					}
					$callbacks = array_merge( $callbacks, $this->extractCallbackFromArray( $nestedItem->value ) );
				}
			} else {
				// Single callback array
				$callbacks = $this->extractCallbackFromArray( $value );
			}
		}

		return $callbacks;
	}

	/**
	 * Extract callback info from array node
	 * Format: ['method'], ['method', priority], or ['method', priority, accepted_args]
	 */
	private function extractCallbackFromArray( Array_ $array ): array
	{
		if ( empty( $array->items ) ) {
			return [];
		}

		$items = array_values( array_filter( $array->items ) );

		// First element should be method name
		$firstItem = $items[0] ?? null;
		if ( $firstItem === null || ! ( $firstItem->value instanceof String_ ) ) {
			return [];
		}

		$methodName = $firstItem->value->value;
		$acceptedArgs = null;

		// Third element (index 2) is accepted_args
		if ( isset( $items[2] ) && $items[2]->value instanceof Int_ ) {
			$acceptedArgs = $items[2]->value->value;
		}

		return [
			[
				'method' => $methodName,
				'accepted_args' => $acceptedArgs,
			]
		];
	}

	/**
	 * Validate callback return type based on hook type
	 */
	private function validateCallbackReturnType( Scope $scope, string $methodName, string $hookType, int $lineNumber ): array
	{
		$errors = [];
		$classReflection = $scope->getClassReflection();

		if ( $classReflection === null ) {
			return $errors;
		}

		// Check if method exists
		if ( ! $classReflection->hasNativeMethod( $methodName ) ) {
			// Let PHPStan's native undefined method detection handle this
			return $errors;
		}

		$methodReflection = $classReflection->getNativeMethod( $methodName );
		$variants = $methodReflection->getVariants();

		if ( empty( $variants ) ) {
			return $errors;
		}

		$returnType = $variants[0]->getReturnType();

		if ( $hookType === 'filter' ) {
			$errors = array_merge( $errors, $this->validateFilterReturnType( $returnType, $lineNumber ) );
		} else {
			$errors = array_merge( $errors, $this->validateActionReturnType( $returnType, $lineNumber ) );
		}

		return $errors;
	}

	/**
	 * Validate filter callback return type (must return non-void)
	 */
	private function validateFilterReturnType( Type $returnType, int $lineNumber ): array
	{
		// Skip non-explicit mixed types (will be handled by PHPStan)
		if ( $returnType instanceof MixedType ) {
			return [];
		}

		// Filter must NOT return void/never
		if ( ( $returnType->isVoid()->no() ) && ! $this->isExplicitNever( $returnType ) ) {
			return [];
		}

		return [
			RuleErrorBuilder::message( 'Filter callback return statement is missing.' )
				->line( $lineNumber )
				->identifier( 'wpmedia.subscriber.filter.missingReturn' )
				->build()
		];
	}

	/**
	 * Validate action callback return type (must return void)
	 */
	private function validateActionReturnType( Type $returnType, int $lineNumber ): array
	{
		// Skip non-explicit mixed types (will be handled by PHPStan)
		if ( $returnType instanceof MixedType && ! $returnType->isExplicitMixed() ) {
			return [];
		}

		// Action must return void or explicit never
		if ( $returnType->isVoid()->yes() || $this->isExplicitNever( $returnType ) ) {
			return [];
		}

		return [
			RuleErrorBuilder::message(
				sprintf(
					'Action callback returns %s but should not return anything.',
					$returnType->describe( VerbosityLevel::getRecommendedLevelByType( $returnType ) )
				)
			)
				->line( $lineNumber )
				->identifier( 'wpmedia.subscriber.action.unexpectedReturn' )
				->build()
		];
	}

	/**
	 * Check if type is explicit never
	 */
	private function isExplicitNever( Type $type ): bool
	{
		return $type instanceof NeverType && $type->isExplicit();
	}

	/**
	 * Validate parameter count against accepted_args
	 */
	private function validateParameterCount( Scope $scope, string $methodName, int $acceptedArgs, int $lineNumber ): array
	{
		$errors = [];
		$classReflection = $scope->getClassReflection();

		if ( $classReflection === null ) {
			return $errors;
		}

		// Check if method exists
		if ( ! $classReflection->hasNativeMethod( $methodName ) ) {
			return $errors;
		}

		$methodReflection = $classReflection->getNativeMethod( $methodName );
		$variants = $methodReflection->getVariants();

		if ( empty( $variants ) ) {
			return $errors;
		}

		$variant = $variants[0];
		$allParameters = $variant->getParameters();
		$requiredParameters = array_filter(
			$allParameters,
			static function ( $parameter ): bool {
				return ! $parameter->isOptional();
			}
		);

		$maxArgs = count( $allParameters );
		$minArgs = count( $requiredParameters );

		// Valid: accepted_args within min-max range
		if ( ( $acceptedArgs >= $minArgs ) && ( $acceptedArgs <= $maxArgs ) ) {
			return $errors;
		}

		// Valid: accepted_args >= minArgs and callback is variadic
		if ( ( $acceptedArgs >= $minArgs ) && $variant->isVariadic() ) {
			return $errors;
		}

		// Special case: no required params but accepted_args is 1 (common pattern)
		if ( $minArgs === 0 && $acceptedArgs === 1 ) {
			return $errors;
		}

		// Build error message
		$expectedParametersMessage = (string) $minArgs;
		if ( $maxArgs !== $minArgs ) {
			$expectedParametersMessage = sprintf( '%d-%d', $minArgs, $maxArgs );
		}

		$message = ( $expectedParametersMessage === '1' )
			? 'Callback expects %s parameter, $accepted_args is set to %d.'
			: 'Callback expects %s parameters, $accepted_args is set to %d.';

		if ( $variant->isVariadic() ) {
			$message = ( $minArgs === 1 )
				? 'Callback expects at least %s parameter, $accepted_args is set to %d.'
				: 'Callback expects at least %s parameters, $accepted_args is set to %d.';
		}

		$errors[] = RuleErrorBuilder::message(
			sprintf(
				$message,
				$expectedParametersMessage,
				$acceptedArgs
			)
		)
			->line( $lineNumber )
			->identifier( 'wpmedia.subscriber.arguments.count' )
			->build();

		return $errors;
	}
}
