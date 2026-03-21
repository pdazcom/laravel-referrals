# Rewards Boundary for 2.x

## Decision

Laravel Referrals 2.x should support reward execution as a lightweight extension surface, not as a built-in reward platform.

That means 2.x should continue to support:

- opt-in hooks that connect common conversion events to referral reward triggers
- simple reward handler implementations selected through package configuration
- practical safety guards that keep reward triggering usable in production
- documentation and examples that show how applications can implement their own reward logic

2.x should not grow into a package-managed system for reward policy, accounting, review, payout operations, or analytics.

## Why this boundary fits 2.x

The current package shape is still centered on referral attribution, link capture, relationship storage, and event-driven reward execution. Recent 2.4 work already fits that model:

- reward hooks connect application events such as signup or first purchase to `ReferralCase`
- reward programs remain application-defined handlers behind `config('referrals.programs')`
- `FixedRewardProgram` is an intentionally simple reference implementation
- duplicate and self-referral guards reduce common production risks without introducing a larger domain model

This is the right level for 2.x because it improves real-world utility while preserving the package's current mental model: "the package tells you when a referral conversion happened, and your program class decides what reward action to take."

## Recommended 2.x Scope

Reward-related work belongs in 2.x when it strengthens one of these layers without changing the package into a reward platform:

### 1. Triggering and integration points

Accept in 2.x:

- new opt-in hooks for a small number of common conversion events
- clearer configuration for resolving the referred user or reward payload from application events
- documentation that explains event lifecycles and hook behavior

Reject for 2.x:

- broad catalogs of prebuilt business events
- package-owned orchestration across multiple application systems

### 2. Execution extension points

Accept in 2.x:

- small improvements to the program/handler contract
- additional simple example programs that demonstrate intended extension patterns
- helper behavior that makes custom reward handlers easier to write without imposing policy

Reject for 2.x:

- package-owned rule builders
- visual or declarative reward editors
- first-class reward campaign management

### 3. Safety and idempotency

Accept in 2.x:

- guards against accidental duplicate rewards
- protections against obviously invalid referral cases
- lightweight observability such as logs or explicit skip behavior

Reject for 2.x:

- a full reward ledger
- reconciliation tooling
- dispute, approval, or manual review workflows

## Explicit Non-Goals for 2.x

The following concepts should be treated as future-major-version work unless a narrower incremental version can be justified:

- reward states such as pending, approved, reversed, paid, expired, or clawed back
- payout orchestration to wallets, coupons, external billing systems, or cash providers
- campaign segmentation, eligibility engines, tiering, or audience targeting
- multi-step rules such as "first purchase over X within Y days, then bonus Z"
- built-in fraud detection, moderation, or human review queues
- package-managed reporting dashboards, attribution analytics, or ROI analysis
- tenant-grade reward configuration UX or admin tools
- a canonical accounting model for balances, liabilities, or settlement history

## Decision Test for Future 2.x Issues

A reward-related proposal belongs in 2.x if the answer to both questions is "yes":

1. Does it make referral-triggered rewards easier or safer to integrate into an application?
2. Can it be delivered without adding a new package-owned reward lifecycle or policy model?

If the proposal requires the package to answer questions like "when is a reward earned, pending, payable, reversible, reportable, or reviewable?" it has crossed into future-major-version territory.

## Trade-Offs

### What we gain

- a clearer, more focused 2.x roadmap
- practical reward support for common application use cases
- room for application-specific logic without locking the package into premature abstractions
- easier evaluation of follow-up reward issues against a stable boundary

### What we give up

- 2.x will not satisfy teams looking for an end-to-end affiliate or incentive platform
- some teams will still need to build application-layer reward policy and payout logic themselves
- analytics and financial operations remain outside the package for now

These are acceptable trade-offs because the package is currently strongest as referral infrastructure with reward extension points, not as a business-operations platform.

## What Should Trigger a Future Major Version Discussion

Open a 3.x-level architecture discussion when the project needs one or more of the following:

- persistent reward records beyond the referral relationship itself
- multiple reward states or transitions over time
- package-owned payout or settlement behavior
- cross-cutting analytics or reporting that depends on a richer reward domain model
- rule composition that cannot live cleanly inside user-defined program classes

At that point, the package would need a clearer reward domain model, storage strategy, and boundaries between attribution, policy, accounting, and reporting.

## Recommendation

For 2.x, continue investing in reward hooks, handler ergonomics, and production-safety guards. Do not add platform-style reward features unless they can be framed as narrow extension support that preserves the current package model.
