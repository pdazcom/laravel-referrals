# Attribution Rule Options for Future Versions

## Decision

Laravel Referrals 2.x should keep a simple default attribution model and preserve extension room for richer policies later, rather than introducing a package-owned attribution engine now.

That means 2.x should continue to treat attribution as:

- a referral entry surface such as a link visit or manual code entry
- resolution to a concrete `ReferralLink`
- relationship creation for the referred user
- reward handling that reacts to the resulting relationship

2.x should not hard-code broader policy decisions such as global first-click vs last-click, source-priority matrices, manual override workflows, or package-managed attribution ledgers.

## Why this fits the package today

The current package shape already has a narrow and useful attribution core:

- web entry via `StoreReferralCode` accepts both the legacy UUID code and the user-facing `referral_code`
- manual entry via `registerWithCode()` resolves the submitted value to the same `ReferralLink`
- referral persistence happens through `ReferralRelationship`
- reward logic is downstream from attribution rather than bundled into the attribution step

That is a good 2.x boundary because it supports the common product surfaces package users actually need today without forcing the package to define a universal affiliate-policy model.

## Current Baseline to Preserve

Future work in 2.x should preserve these assumptions:

### 1. Entry surface is not the policy

A clicked link and a manually entered code should remain two ways to identify the same attribution target, not two separate attribution systems.

2.x should keep treating both as inputs that resolve to a `ReferralLink`.

### 2. Resolution should stay simple

The package should continue to support a clear default path:

- accept one attribution input
- resolve it to a referral link
- create the relationship if the referral is valid

That default should stay easy to explain in docs and easy to integrate into typical Laravel signup or conversion flows.

### 3. Rewards should stay downstream

Attribution should decide "which referral relationship applies." Reward programs should decide "what happens because that relationship exists."

That separation matters because future attribution complexity and future reward complexity will evolve independently.

## Likely Future Attribution Modes

These are the most plausible directions for future package users.

### 1. Last-touch within an attribution window

This is the most natural extension from the current cookie-first web flow.

Typical behavior:

- the most recent qualifying referral touch wins
- each touch expires after a configured window
- the eventual signup or conversion uses the still-valid winning touch

Why it matters:

- aligns with familiar link-driven referral behavior
- works well for web signup funnels
- keeps implementation and mental model relatively simple

### 2. First-touch lock

Some teams will want the first qualifying referral to remain attached even if later links or codes are used.

Typical behavior:

- the earliest valid referral touch wins
- later touches are ignored unless explicitly cleared

Why it matters:

- fits advocacy, ambassador, or invite-friend programs where the original referrer should keep credit
- avoids noisy reassignment from repeated sharing surfaces

### 3. Source-priority rules

Some products will care less about time order and more about which source should take precedence.

Examples:

- manual code entry overrides a previously stored web cookie
- a creator code overrides a generic campaign link
- an internal support code is allowed to override both

Why it matters:

- real products often mix self-serve links, creator codes, support-assisted onboarding, and campaign traffic
- source trust and business intent can matter more than chronological order

### 4. Program-scoped attribution

The package already allows multiple referral programs. Future attribution may need to decide winners per program instead of globally.

Typical behavior:

- one referral source may be valid for a signup bonus program
- another may be valid for a first-purchase or affiliate program
- conflicts are resolved within each program boundary

Why it matters:

- avoids forcing unrelated programs into one universal winner
- fits the current package model better than a single cross-program attribution truth

### 5. Manual override or reviewed reassignment

Some teams will eventually want a support or operations flow that reassigns attribution after the fact.

Typical behavior:

- an operator corrects a missed or incorrect referral
- the system records why the override happened

Why it matters:

- common in support-assisted onboarding and code-entry edge cases
- useful, but clearly beyond current 2.x package scope

## What 2.x Should Explicitly Keep Flexible

To avoid architectural dead ends, current 2.x work should preserve these boundaries.

### 1. Do not bind attribution identity to one transport

The package should keep the distinction between:

- how attribution arrives: URL param, code field, API payload, import, support action
- what attribution points to: a `ReferralLink`

Future rules become much harder if transport-specific logic is treated as the core domain model.

### 2. Do not assume one universal precedence rule

2.x can keep a practical default behavior, but it should avoid baking in the idea that the package has permanently chosen:

- last-click forever
- first-click forever
- code-over-link forever
- one global precedence stack for every product

Those are policy choices, not core package truths.

### 3. Do not couple reward semantics to attribution semantics

Attribution answers who gets credit. Reward logic answers whether, when, and how value is issued.

Future attribution rules may change without requiring a new reward model, and future reward states may change without requiring a new attribution policy.

### 4. Avoid collapsing all future logic into the cookie contract

The current cookie flow is a good default for web entry, but future attribution may also come from:

- manual code entry during signup
- native mobile onboarding
- server-side API calls
- imported or backfilled referral claims

2.x should not make the browser cookie the only conceptual source of attribution truth.

## Recommended 2.x Constraints

Future 2.x issues should stay inside these limits.

### Accept in 2.x

- clearer documentation of the default attribution behavior
- small refactors that keep link-based and code-based entry resolving to the same referral identity
- per-program thinking where it clarifies existing behavior without adding a policy engine
- narrow hooks or extension points that let applications decide precedence outside the package
- lightweight metadata or structure that keeps future rule work possible without changing current behavior

### Defer beyond 2.x

- a configurable first-click vs last-click engine
- admin-defined attribution rule builders
- package-owned manual override workflows
- multi-touch attribution history and weighted credit
- channel analytics, reporting, or reconciliation tied to attribution state
- approval, dispute, or fraud-review flows for attribution decisions

## Interaction with Links, Codes, and Rewards

### Links and codes

The package should continue to model links and codes as related entry surfaces that resolve to the same underlying referral entity.

That lets 2.x keep a simple story:

- links are best for click flows
- codes are best for manual, offline, or cross-device flows
- attribution policy should not fork just because the user arrived through a different input surface

### Rewards

Reward handling should continue to depend on the resulting referral relationship, not on whether the user clicked a link or typed a code.

If future products need different reward outcomes by source type, that should be a higher-layer application policy until the package has a stronger attribution domain model.

## Major-Version Signals

Move attribution into a future-major-version architecture discussion when one or more of these become necessary:

- the package must record multiple attribution touches instead of just the winning relationship
- attribution precedence becomes configurable inside the package
- attribution decisions require source categories, operator actions, or audit trails
- attribution must work consistently across browser, mobile SDK, and API-first entry points
- rewards, reporting, or compliance features need a richer attribution history than the current model provides

At that point the package would likely need a clearer domain split between attribution inputs, attribution decisions, stored relationships, and downstream reward/reporting consumers.

## Trade-Offs

### What we gain

- a stable 2.x design boundary
- room for current code and docs to improve without overcommitting to a universal policy engine
- better compatibility with code-based, link-based, and future API/mobile entry paths
- clearer evaluation criteria for later attribution issues

### What we give up

- 2.x will not satisfy teams that want a full affiliate attribution platform
- some precedence rules will remain application-defined rather than package-configurable
- manual correction and advanced operations workflows remain outside the package for now

Those are acceptable trade-offs because the package is strongest today as referral infrastructure with flexible entry points, not as a complete attribution-management system.

## Recommendation

For 2.x, keep one simple default attribution path and explicitly preserve room for future policy layers. Treat links and codes as input surfaces, keep `ReferralLink` as the resolved attribution identity, and defer package-owned rule engines, override workflows, and multi-touch history to future major-version discussion.
