# Anti-Abuse Architecture Boundaries

## Decision

Laravel Referrals 2.x should include a narrow set of package-level anti-abuse guardrails that protect referral integrity at the attribution and reward-trigger boundary, while leaving higher-risk enforcement, operations, and fraud policy to the host application.

That means 2.x should own:

- validation that prevents obviously invalid referral relationships
- idempotency and duplicate-reward protection around package-managed reward triggering
- small extension points that let applications add stricter checks before or around referral execution
- documentation that makes the package boundary explicit

2.x should not own a broader fraud platform, trust-scoring system, moderation workflow, or investigation surface.

## Why this boundary fits the package

The current package model is still intentionally narrow:

- attribution resolves an input to a `ReferralLink`
- relationship storage records who referred whom
- reward handling reacts to a `ReferralCase`
- reward programs remain application-defined classes

Recent work already points to the right anti-abuse layer for 2.x:

- `prevent_self_referral` blocks the most obvious invalid relationship without adding review state
- `prevent_duplicate_rewards` protects reward idempotency per referral relationship
- reward execution still happens through application-owned program classes instead of a package ledger

Those are useful production safeguards, but they do not require the package to decide whether a user is suspicious, whether a conversion is fraudulent, or whether an operator should intervene.

## Core Boundary for 2.x

Anti-abuse behavior belongs in package core when it protects invariants that are already implied by the current package domain.

### 1. Relationship integrity

Package core should enforce or support checks that answer:

- is this referral relationship structurally valid?
- does this conversion point back to an existing referral relationship?
- is the referred user trying to claim their own referral link?

This fits package core because these checks operate on package-owned concepts such as `ReferralLink`, `ReferralRelationship`, and the reward event pipeline.

### 2. Reward-trigger idempotency

Package core should own protection against accidental repeated reward issuance when the same relationship is processed more than once.

This is already aligned with the current `prevent_duplicate_rewards` guard:

- the package can determine whether a relationship has already been rewarded
- the package can skip duplicate processing safely
- applications do not need to rebuild the same concurrency or replay protection around every reward program

This is a package invariant, not a fraud-policy decision.

### 3. Lightweight pre-flight extension points

Package core may expose narrow hooks for applications to reject referral cases before relationship creation or reward execution, as long as the package does not try to standardize the policy itself.

Examples of acceptable extension support:

- a callback or contract that can veto a referral relationship
- an event or hook that allows application-side abuse screening before reward execution
- explicit skip reasons or logs that make application enforcement observable

The package should provide the seam, not the policy catalog.

## What Stays Application-Specific

Anti-abuse behavior belongs in the application when it depends on product policy, trust signals, operations teams, or data the package does not own.

### 1. Identity and account-trust decisions

Application code should decide:

- whether multiple accounts from one household are allowed
- whether email-domain, device, IP, phone, payment-method, or KYC checks should block a referral
- whether new accounts must age before becoming reward-eligible
- whether referred users must meet custom order, subscription, or spend thresholds

These are product and risk-policy choices, not package truths.

### 2. Fraud detection and scoring

The package should not attempt to provide:

- fraud scores
- anomaly detection
- velocity checks across accounts or campaigns
- risk heuristics based on device, geography, payments, or support history

Those systems require application data, tuning, and operational ownership far beyond the current package scope.

### 3. Operations and review workflows

The package should not own:

- manual approval queues
- dispute handling
- reward freezing, reversing, or clawback workflows
- investigator tooling or audit dashboards

If a product needs human review, the application should stop or defer reward execution in its own layer and only call package reward flows after approval.

## Near-Term 2.x Scope

The right 2.x anti-abuse scope is narrow and explicit.

### Accept in 2.x

- keeping self-referral prevention as an opt-in core guard
- keeping duplicate-reward prevention as an opt-in core guard
- small hardening improvements around relationship validity or replay-safe reward execution
- narrow extension seams that let applications add custom screening logic
- clearer docs showing where package safeguards end and application enforcement begins

### Defer beyond 2.x

- built-in IP, device, payment-method, or fingerprint matching
- configurable fraud rules or rule builders
- package-owned approval or moderation states
- reward reversal, clawback, or case-management tooling
- cross-program abuse analytics or operator dashboards
- shared blacklists, sanctions, or trust repositories

## How this connects to existing 2.x work

The current self-referral and duplicate-reward work should be treated as the baseline anti-abuse layer for 2.x.

### Self-referral guard

`prevent_self_referral` belongs in core because it prevents an obviously invalid relationship using only package-owned data:

- the referral link owner
- the referred user attempting to claim the referral

That is a universal integrity check, not a product-specific policy.

### Duplicate-reward guard

`prevent_duplicate_rewards` belongs in core because it protects the reward pipeline from repeat execution for the same relationship.

That is especially important as more event hooks are added. Applications should not have to independently solve replay protection each time they wire signup or purchase events into `ReferralCase`.

## Extension Points to Preserve for Future Versions

2.x should keep room for stricter anti-abuse layers without forcing the package to implement them now.

### 1. Relationship-validation seam

Future versions may need an explicit application hook before relationship creation so products can reject referrals based on their own trust model.

Examples:

- blocking same-household referrals
- blocking referrals from flagged accounts
- requiring inviter eligibility before relationship creation

### 2. Reward-eligibility seam

Future versions may need an application-facing seam before reward execution so products can delay or deny rewards based on business-specific checks.

Examples:

- only reward after the first paid invoice clears
- require order fraud checks to pass
- hold rewards until a cooling-off window expires

### 3. Skip-reason observability

If more safeguards are added, the package should prefer explicit skip reasons, logs, or events over hidden policy behavior.

That keeps debugging manageable and lets applications build monitoring or review workflows outside the package.

## Decision Test for Future Issues

An anti-abuse proposal belongs in 2.x package core if both answers are yes:

1. Does it protect a package-owned invariant around attribution, relationship validity, or reward-trigger idempotency?
2. Can it be implemented without introducing a package-owned fraud policy, review workflow, or trust model?

If the proposal requires the package to answer questions like "is this user risky, suspicious, reviewable, reversible, or operator-approved?" it has crossed into application-layer or future-major-version territory.

## Trade-Offs

### What we gain

- clearer responsibility boundaries for package users
- safer default reward execution without overcommitting the architecture
- compatibility with many different product risk policies
- a stable test for evaluating follow-up anti-abuse requests

### What we give up

- the package will not solve complex fraud programs end to end
- applications still need to own trust signals and review operations
- some teams will need custom code for enforcement beyond self-referral and duplicate prevention

Those are acceptable trade-offs because Laravel Referrals is strongest as referral infrastructure with targeted safety guards, not as a full anti-fraud platform.

## Recommendation

For 2.x, keep anti-abuse in core only where it protects package-owned invariants: invalid self-referrals, invalid relationship flows, and duplicate reward issuance. Add extension seams and observability where needed, but leave identity risk, fraud heuristics, approvals, and reversal workflows to the application layer.
