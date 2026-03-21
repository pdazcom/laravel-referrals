# Analytics and Reporting Direction

## Decision

Laravel Referrals 2.x should support a narrow reporting foundation built on package-owned entities and lifecycle timestamps, while leaving dashboards, attribution warehousing, ROI analysis, and broader analytics products to the host application.

That means 2.x should support:

- metrics that can be derived from `ReferralLink`, `ReferralRelationship`, and `rewarded_at`
- clear docs describing which package events and records are stable reporting inputs
- lightweight extension seams so applications can emit their own analytics events at the right moments
- examples of practical reporting questions users can answer without changing the package into a reporting platform

2.x should not grow into a package-owned analytics system, admin dashboard, event warehouse, or BI layer.

## Why this boundary fits 2.x

The current package already captures a useful but intentionally small set of referral facts:

- `ReferralLink` identifies the referrer, program, share URL/code, and click count
- `ReferralRelationship` records that a referred user was attached to a specific link
- `rewarded_at` records whether the package has already issued a reward for that relationship when duplicate-reward protection is enabled
- `UserReferred` and `ReferralCase` mark the package lifecycle moments that applications can observe

This is enough for basic operational reporting such as "how many links exist," "how many relationships were created," "which programs convert," and "how many relationships were rewarded." It is not enough for a full analytics product with time-series event history, multi-touch attribution, campaign ROI, or finance-grade payout reporting.

That is the right 2.x boundary. The package should expose the stable facts it already owns, not invent a larger analytics domain before the rest of the product model exists.

## Recommended Medium-Term Reporting Surface

The best 2.x reporting surface is documentation-first plus application-owned querying.

### 1. Stable package records

Package users should treat these records as the canonical reporting inputs:

- `referral_programs`: program catalog and reporting dimension
- `referral_links`: created referral assets, share surfaces, and click totals
- `referral_relationships`: successful attribution outcomes
- `rewarded_at`: whether the relationship has already produced a package-managed reward outcome

This gives users a practical reporting spine without requiring the package to ship its own reporting UI.

### 2. Stable lifecycle moments

Applications should be able to rely on these moments for their own instrumentation:

- referral touch captured via `StoreReferralCode`
- relationship creation via `UserReferred` -> `ReferUser`
- reward trigger attempt via `ReferralCase`
- reward execution through `RewardUser`

The package does not need to persist every one of these as first-class analytics records in 2.x, but it should document them as the moments where application analytics can subscribe safely.

### 3. Queryable examples instead of package dashboards

The package should prefer:

- docs that explain how to answer common questions with SQL, Eloquent, or app-side analytics tooling
- examples of metrics by program, link, referrer, and conversion stage
- clear statements about which numbers are exact today versus estimated or application-defined

The package should avoid:

- admin reporting screens
- charting components
- package-owned CSV exports
- package-specific analytics APIs

## Recommended Metrics for 2.x

These are the reporting questions the current package model can support cleanly.

### 1. Link creation and inventory

Supported metrics:

- number of referral links created
- links created by program
- links created by user or cohort
- percentage of eligible users with at least one referral link

Why this works:

- `referral_links` is already a stable package-owned record
- creation timestamp and program relationship already exist

### 2. Top-of-funnel engagement

Supported metrics:

- total clicks by referral link
- top-performing links by clicks
- clicks by program

Why this works:

- `referral_links.clicks` already exists as a lightweight aggregate

Important limitation:

- this is aggregate engagement, not event history
- 2.x should not pretend it can answer session-level or time-series questions from a single counter

### 3. Successful attribution and conversion

Supported metrics:

- number of referral relationships created
- referred users by link or by program
- conversion rate from links to relationships
- conversion rate from clicks to relationships, where teams accept counter-based limitations

Why this works:

- `referral_relationships` is the package-owned record of successful attribution

Important limitation:

- because click tracking is a cumulative counter and not an event log, "click to relationship conversion" is directional reporting, not a warehouse-quality funnel

### 4. Reward completion

Supported metrics:

- rewarded relationships count
- reward completion rate by program
- time from relationship creation to reward completion when `rewarded_at` is used

Why this works:

- `rewarded_at` gives the package a narrow but useful downstream milestone

Important limitation:

- this is not a complete reward ledger
- it cannot answer payout finance questions such as amount paid over time, reversals, liabilities, or settlement status unless the application owns that data

## Event and Model Assumptions Needed Today

To make the above reporting surface trustworthy, 2.x should preserve these assumptions.

### 1. Relationship creation remains the canonical conversion record

`ReferralRelationship` should continue to mean "the package accepted this referral attribution outcome."

That matters because many reporting questions depend on one stable conversion record rather than on transient cookies, request inputs, or external business events.

### 2. `rewarded_at` remains a simple milestone, not a reward ledger

`rewarded_at` should continue to mean only "a reward for this relationship has already been processed by the package flow."

It should not be overloaded to represent:

- reward amount
- payout status
- approval state
- reversal state
- financial reconciliation state

Those belong to application-owned reward operations unless the package later adopts a richer reward domain model.

### 3. Clicks stay lightweight unless the package adopts an event store

`referral_links.clicks` is useful as a lightweight aggregate. It is not enough for:

- time-series reporting
- deduplicated visitor counts
- source breakdowns
- campaign segmentation
- cohort retention analysis

2.x should document this limitation instead of stretching the meaning of the counter.

### 4. Events remain instrumentation seams, not analytics products

`UserReferred`, `ReferralCase`, and related listeners are the right place for applications to attach analytics, notifications, warehousing, or downstream syncing.

The package should keep those seams stable and documented, but it should not take ownership of the downstream analytics stack.

## What 2.x Should Explicitly Keep Flexible

### 1. Reporting by program, not one global funnel only

The package already supports multiple referral programs. Reporting guidance should preserve the idea that teams may need metrics:

- globally
- per program
- per link
- per referrer

2.x should avoid assumptions that one universal funnel view is enough.

### 2. Application-owned revenue and payout enrichment

Many users will want business metrics such as:

- revenue from referred customers
- customer lifetime value of referred cohorts
- payout cost per acquired user
- ROI by creator or campaign

Those should remain application-owned enrichments layered onto package facts, not package-owned metrics in 2.x.

### 3. Analytics transport choice

Different products will want to send reporting data to different systems:

- SQL queries in the application database
- Laravel events and listeners
- data warehouse pipelines
- product analytics tools
- internal admin tools

2.x should stay compatible with all of them and avoid introducing a package-preferred analytics backend.

## Explicit Non-Goals for 2.x

The following should be treated as future-major-version or application-layer work:

- package-owned analytics dashboards or admin reporting pages
- event-level clickstream storage
- multi-touch attribution analytics
- campaign ROI and spend analysis
- payout accounting or finance reporting
- cohort retention, LTV, or revenue attribution modeling
- CSV export tooling and scheduled reporting jobs owned by the package
- benchmark or leaderboard products for creators, affiliates, or campaigns

## Decision Test for Future Reporting Issues

A reporting proposal belongs in 2.x if both answers are yes:

1. Can it be derived from package-owned referral records or documented lifecycle seams?
2. Can it be delivered without adding a package-owned analytics product, event warehouse, or business-operations model?

If the proposal requires the package to answer questions like "what was the acquisition ROI, campaign efficiency, payout liability, time-series performance trend, or multi-touch contribution?" it has moved beyond the right 2.x scope.

## Trade-Offs

### What we gain

- a clear analytics boundary that supports real operational reporting
- stable reporting inputs for package users without overbuilding the domain
- compatibility with app-specific BI, data warehouse, and admin tooling
- a clean bridge from current 2.x records to future platform work if demand appears

### What we give up

- the package will not satisfy teams looking for built-in referral analytics dashboards
- top-of-funnel reporting remains approximate where only aggregate counters exist
- business-value metrics still require application data joins and custom instrumentation

These are acceptable trade-offs because Laravel Referrals is strongest today as referral infrastructure with reporting-friendly records, not as a full analytics platform.

## Major-Version Signals

Move reporting into a future-major-version architecture discussion when one or more of these become necessary:

- event-level reporting beyond aggregate counters
- package-owned attribution history or multi-touch analytics
- reward reporting that depends on a richer reward ledger
- built-in reporting APIs, exports, or dashboards
- revenue or ROI reporting that needs package-owned business metrics
- cross-channel analytics that must work consistently across web, mobile, and API attribution flows

At that point the package would likely need a clearer analytics domain split between event capture, reporting storage, attribution history, reward operations, and presentation surfaces.

## Recommendation

For 2.x, document and stabilize reporting around `referral_links`, `referral_relationships`, `rewarded_at`, and package lifecycle events. Support application-owned querying and instrumentation, but defer dashboards, warehousing, ROI analysis, and broader analytics-platform concerns until the package has a much richer attribution and reward model.
