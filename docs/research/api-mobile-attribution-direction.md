# API-First and Mobile Attribution Direction

## Decision

Laravel Referrals 2.x should stay compatible with API-first and mobile attribution by making referral attachment possible without relying on browser cookies, while deferring package-owned mobile transport, probabilistic install matching, and attribution-operations concerns to future major-version work or the host application.

That means 2.x should preserve a narrow attribution core:

- accept a referral input from any channel
- resolve that input to a `ReferralLink`
- attach the referred user to that link through the existing relationship model
- keep reward handling downstream from the resulting relationship

2.x should not turn into a mobile attribution SDK, install-match engine, or package-owned cross-channel attribution platform.

## Why this boundary fits the package today

The package already owns a useful attribution center but currently exposes it mostly through web-oriented entry paths:

- `StoreReferralCode` captures `?ref=` from browser requests and stores referral state in a cookie
- `registerWithCode()` supports explicit code entry without the cookie flow
- `ReferralRelationship` records the accepted attribution result
- reward execution reacts to that relationship instead of caring how attribution arrived

That architecture is narrow enough to extend toward API and mobile use cases if 2.x avoids assuming that browser middleware is the only valid transport.

## What is changing in real product flows

Modern products increasingly need referral attribution in flows such as:

- SPAs that call backend APIs after the initial page load
- native mobile apps with code entry during onboarding
- installed-app deep links via Universal Links or Android App Links
- Android installs that pass campaign data through the Play Install Referrer
- backend-driven signup flows where the referral signal arrives as an API payload rather than an HTTP cookie
- mixed journeys where discovery starts on the web and conversion finishes later in a mobile app

These are realistic future directions, but they do not all have the same confidence or implementation cost.

## Directional Model for Future Attribution Inputs

The package should evolve around the idea that attribution has three layers:

### 1. Transport

How the referral signal arrives:

- browser query parameter
- cookie
- manual code entry form
- API request field
- mobile deep link payload
- install-referrer payload
- application-owned claim token

### 2. Resolution

How the application or package turns that signal into a package-owned referral identity:

- `referral_code` lookup
- legacy UUID code lookup
- direct `ReferralLink` identifier lookup
- application-owned token exchange that resolves to a `ReferralLink`

### 3. Attachment

How the referred user is finally connected to the resolved referral:

- create or confirm the `ReferralRelationship`
- dispatch existing package events
- let reward programs react later

This separation matters because 2.x can stay useful if it owns the attachment layer and keeps the resolution layer simple, while leaving many transport details to the application.

## Realistic Future Scenarios

These are the most relevant future API/mobile directions for likely package users.

### 1. Explicit code entry in mobile onboarding

This is the lowest-risk and most realistic mobile path.

Typical flow:

- the app shows a referral code field during signup or onboarding
- the mobile client sends the code to the backend
- the backend resolves it to a `ReferralLink`
- the package attaches the relationship

Why it matters:

- deterministic
- easy to explain
- privacy-friendly
- already aligned with `registerWithCode()`

This should be considered the baseline mobile-compatible path.

### 2. Installed-app deep linking

This is the next most practical direction for mobile support.

Typical flow:

- a user taps a referral link
- iOS Universal Links or Android App Links open the installed app
- the app receives the referral payload
- the backend exchanges that payload for attribution and continues signup or conversion

Why it matters:

- good user experience when the app is already installed
- deterministic enough to fit package-level support later
- keeps referral transport explicit rather than inferred

Important boundary:

2.x should stay compatible with this pattern, but it should not ship platform-specific deep-link integration logic.

### 3. Stateless API-first signup

This is the most important architectural direction beyond browser-only Laravel apps.

Typical flow:

- a frontend or mobile client collects a referral code or token
- the client submits it alongside signup or conversion data to an API
- the backend resolves and attaches attribution in the same request or as a follow-up action

Why it matters:

- fits SPAs, mobile apps, and headless backends
- removes dependence on middleware and browser cookies
- aligns with modern application architecture without requiring a new package domain model

This is the main 2.x compatibility target to preserve.

### 4. Application-owned claim tokens

Some teams will eventually want the application to issue an explicit claim token instead of passing raw codes through every step.

Typical flow:

- a referral link or code is captured early
- the application converts it into a short-lived internal token
- later API calls redeem the token and attach the referral

Why it matters:

- reduces coupling between client transport and package internals
- can support delayed or cross-device signup flows
- gives applications a place to add custom policy or validation

This is plausible future work, but it is not necessary for 2.x core.

### 5. Mobile install attribution and deferred deep linking

This is the highest-complexity direction and should be treated carefully.

Typical flow:

- a user taps a referral link before the app is installed
- the app store install flow intervenes
- attribution is reconstructed later from platform signals, install-referrer data, or a third-party provider

Why it matters:

- common product request
- strategically relevant for future growth
- technically and operationally much more complex than code entry or installed-app deep linking

Important reality:

post-install mobile attribution is often privacy-constrained, platform-specific, or probabilistic. The package should not promise deterministic package-owned deferred deep linking in 2.x.

## What 2.x Should Keep Flexible

### 1. Do not make browser cookies the conceptual source of truth

Cookies are a valid transport for the web flow, but they should not define the package boundary.

2.x should preserve the idea that attribution can also arrive from:

- request payloads
- code entry fields
- app deep-link payloads
- application-owned backend state

### 2. Do not require middleware as the only entry point

`StoreReferralCode` is useful, but future-compatible 2.x work should not assume all attribution begins in an HTTP middleware pass.

The package should remain compatible with:

- controllers
- API actions
- service-layer calls
- queued jobs
- event-driven application flows

### 3. Keep transport-specific confidence outside the core relationship model

Some future attribution sources will be deterministic, while others will be inferred or confidence-scored.

Examples:

- manual code entry is explicit
- installed-app deep links are usually strong signals
- install-referrer or third-party mobile attribution may carry caveats or confidence constraints

2.x should not overload `ReferralRelationship` to represent confidence tiers, attribution provenance, or campaign-resolution evidence.

### 4. Keep package core centered on resolved referral identity

Whether a user arrived from the web, a mobile app, or an API call, the package should keep converging on the same core question:

"Which `ReferralLink`, if any, should this user be attached to?"

That protects the package from transport sprawl.

## Recommended 2.x Compatibility Targets

These are the most defensible near-term directions.

### Accept in 2.x

- docs that frame browser cookie capture as one transport, not the entire attribution model
- small refactors that make explicit referral attachment easier from controllers or API endpoints
- keeping code-based resolution and relationship attachment usable outside the middleware flow
- narrow extension seams so applications can resolve referral payloads before handing a `ReferralLink` or code to the package
- examples for SPA, API, and mobile code-entry usage patterns

### Defer beyond 2.x

- package-owned iOS or Android SDK behavior
- deferred deep-link routing owned by the package
- install-attribution matching logic tied to platform APIs
- package-managed claim-token lifecycle
- confidence scoring, attribution provenance, or mobile campaign audit trails in package storage
- package-owned support for MMP integrations, ad-network postbacks, or attribution-provider webhooks

## What likely belongs in 3.x thinking

If the project decides to support mobile/API attribution as a first-class product surface, a future major version may need a broader domain model.

Potential 3.x concepts:

- an explicit attribution claim model separate from the final relationship
- source metadata such as channel, transport, platform, and confidence
- a two-step lifecycle of capture first, attachment later
- support for redeemable claim tokens or signed attribution payloads
- clearer separation between attribution capture, policy resolution, and reward execution

That would be a meaningful architecture expansion and should not be smuggled into 2.x behind small compatibility features.

## Design test for future issues

An API/mobile attribution proposal belongs in 2.x if both answers are yes:

1. Can it help applications attach a referral without assuming a browser cookie flow?
2. Can it be added without giving the package ownership of mobile transport, install matching, or attribution operations?

If the proposal requires the package to answer questions like "how was this app install matched, how confident is the attribution, which mobile platform signal won, or how should deferred deep linking be recovered?" it has moved into future-major-version territory.

## Trade-Offs

### What we gain

- clearer compatibility with SPA, API, and mobile code-entry flows
- a future-proof boundary that is not trapped inside Laravel web middleware
- better alignment with installed-app deep links and backend-driven onboarding
- a stable base for future architecture work without prematurely committing to mobile attribution infrastructure

### What we give up

- 2.x will not offer turnkey mobile attribution
- install-time and deferred deep-link scenarios remain application- or provider-owned
- some high-growth mobile use cases will still require custom backend logic or third-party tooling

These are acceptable trade-offs because the package is strongest today as referral infrastructure that can accept resolved referral inputs, not as a full mobile attribution stack.

## Recommendation

For 2.x, optimize for explicit referral attachment outside the browser-cookie path. Treat manual code entry, installed-app deep links, and API request payloads as the important compatibility targets. Keep mobile install attribution, deferred deep linking, claim-token lifecycle, and confidence-aware attribution models in future-major-version discussion unless the project is ready to adopt a materially larger architecture.
