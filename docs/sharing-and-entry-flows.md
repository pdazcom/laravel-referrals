# Sharing and Entry Flows

Use this guide when you need to decide how users should share referral invitations and how new users should enter them. After reading it, you will know when to use a link, when to show only a code, and how to verify that both paths create the same attribution result.

## What the package gives you

Each `ReferralLink` now exposes three related values:

| Property | What it contains | Best use |
| --- | --- | --- |
| `$link->referral_code` | Human-friendly code such as `INVITE2024` | Show in UI, chat, SMS, support replies, offline campaigns |
| `$link->referral_link` | Human-friendly share URL such as `/register?ref=INVITE2024` | Email, messaging apps, landing pages, QR codes |
| `$link->link` | Legacy UUID-based URL such as `/register?ref=550e8400-e29b-41d4-a716-446655440000` | Backward-compatible integrations that already store UUID links |

The middleware accepts both the legacy UUID code and the human-friendly referral code in the same `?ref=` query parameter. Manual entry uses the same resolver through `registerWithCode()`.

## Flow 1: Share a link in web or messaging surfaces

Choose this flow when users tap a link from email, chat, SMS, social posts, or a landing page.

```php
use Pdazcom\Referrals\Models\ReferralLink;

$link = ReferralLink::create([
    'user_id' => $user->id,
    'referral_program_id' => $program->id,
]);

return [
    'share_url' => $link->referral_link,
    'share_code' => $link->referral_code,
];
```

Why this works well:

- You give users a short, readable URL instead of a UUID.
- The same code can also be displayed separately if the app strips URL parameters.
- The middleware stores attribution in the referral cookie and removes the `ref` query parameter from the final URL after redirect.

## Flow 2: Share only the code

Choose this flow when the target surface does not handle deep links well, or when users often copy a code between devices.

Examples:

- A native mobile app shows `INVITE2024` on a referral screen.
- A support agent reads the code to a caller.
- A printed flyer or in-store poster includes only the code.

Your application can display the code directly:

```php
$code = $link->referral_code;
```

Later, your signup or onboarding UI collects that code and passes it to `registerWithCode()`.

## Flow 3: Accept manual entry during signup or onboarding

Choose this flow when the referred user types a code into a form instead of opening a link.

```php
use Illuminate\Http\Request;

public function store(Request $request)
{
    $data = $request->validate([
        'name' => ['required', 'string'],
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
        'referral_code' => ['nullable', 'string'],
    ]);

    $user = User::create($data);

    if (!empty($data['referral_code'])) {
        $accepted = $user->registerWithCode($data['referral_code']);

        if (!$accepted) {
            // Decide whether you want to ignore invalid codes or show feedback.
        }
    }

    return redirect('/dashboard');
}
```

Key behavior:

- `registerWithCode()` accepts both `$link->referral_code` and the legacy UUID `$link->code`.
- It returns `true` when the code resolves to a `ReferralLink`.
- It returns `false` when the code is unknown.
- It dispatches `UserReferred`, so the resulting attribution path matches the existing link-based flow.

## Flow selection guide

| If your product looks like this | Prefer |
| --- | --- |
| Traditional web app with registration links in email or chat | `referral_link` |
| Mobile app with a visible “Enter code” field | `referral_code` plus `registerWithCode()` |
| Support, affiliate, or creator workflows where codes are read aloud or copied manually | `referral_code` |
| Existing installation already stores UUID links in templates or CRM systems | Keep `link` for compatibility, migrate to `referral_link` when convenient |

## Trade-offs

### `referral_link`

Pros:

- Best click-through experience.
- Works with the existing middleware and cookie flow.
- Easy to paste into email, chat, and landing pages.

Trade-offs:

- Requires a browser or deep-link capable surface.
- Some apps remove query parameters when copying links manually.

### `referral_code`

Pros:

- Easy to display, dictate, or print.
- Works across devices and channels where links are unreliable.
- Matches the human-friendly URL shown by `referral_link`.

Trade-offs:

- Requires an input field or another manual-entry surface.
- You need to decide how to handle invalid code feedback in your UI.

### `link`

Pros:

- Keeps older integrations working with no changes.

Trade-offs:

- UUIDs are harder for humans to read, type, and share.
- New product surfaces should not prefer it unless compatibility is the main goal.

## Verification checklist

Use this checklist before you ship a new sharing surface:

1. Create a `ReferralLink` and record `$link->referral_code`, `$link->referral_link`, and `$link->link`.
2. Visit `$link->referral_link` in a browser and confirm:
   - the request redirects to the clean target URL
   - the referral cookie is written
   - signup creates a row in `referral_relationships`
3. Submit the same referral through your manual entry UI with `$link->referral_code` and confirm the same relationship is created.
4. Submit an unknown code and confirm your app handles the `false` return value from `registerWithCode()` the way you expect.
5. If you still support UUID integrations, submit `$link->code` through the same manual entry path and confirm it also resolves correctly.

## Related docs

- [README quickstart](../README.md#quickstart)
- [README usage](../README.md#usage)
