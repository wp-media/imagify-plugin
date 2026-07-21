<?php
declare(strict_types=1);

namespace Imagify\Abilities;

/**
 * Contract for MCP abilities that spend Imagify quota.
 *
 * Implementing this interface opts an ability into
 * `AbstractAbility::guard_credit_confirmation()`, the shared pre-flight
 * quota/confirmation gate.
 *
 * @since 2.4.0
 */
interface CreditConsumingAbilityInterface {

	/**
	 * Returns a small associative array describing what the call is about to do.
	 *
	 * Used by `AbstractAbility::guard_credit_confirmation()` to build the
	 * `confirmation_required` response so the caller/AI can see the impact
	 * before confirming.
	 *
	 * @param array $args Raw input arguments passed to execute().
	 * @return array{unit: string, count: int, total?: int, label: string}
	 */
	public function get_impact_estimate( array $args ): array;
}
