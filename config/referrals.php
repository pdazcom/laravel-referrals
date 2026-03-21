<?php

return [
    'programs' => [
        'example' => \Pdazcom\Referrals\Programs\ExampleProgram::class,
        // 'fixed-bonus' => \Pdazcom\Referrals\Programs\FixedRewardProgram::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Fixed Reward Amount
    |--------------------------------------------------------------------------
    |
    | The flat credit amount awarded by FixedRewardProgram on each qualifying
    | conversion. Override this value in your own subclass by redeclaring the
    | FIXED_AMOUNT constant, or change it here to apply globally.
    |
    */
    'fixed_reward_amount' => 10,
    'cookie_name' => 'ref',
    'code_generator' => \Pdazcom\Referrals\Generators\RandomStringCodeGenerator::class,
    'code_length' => 8,
    'code_generation_max_attempts' => 10,

    /*
    |--------------------------------------------------------------------------
    | Duplicate Reward Prevention
    |--------------------------------------------------------------------------
    |
    | When enabled, each referral relationship can only trigger a reward once.
    | After the reward is issued, the relationship is stamped with a
    | `rewarded_at` timestamp. Any subsequent ReferralCase event for the same
    | referral/program combination is silently skipped.
    |
    | Requires the 2026_03_21_000001_add_rewarded_at_to_referral_relationships
    | migration to be run.
    |
    | Set to true to enable. Disabled by default for backward compatibility.
    |
    */
    'prevent_duplicate_rewards' => false,

    /*
    |--------------------------------------------------------------------------
    | Self-Referral Prevention
    |--------------------------------------------------------------------------
    |
    | When enabled, a user cannot refer themselves using their own referral
    | link. If the referring user's ID matches the referral link owner's ID,
    | the relationship is silently skipped and a log entry is written.
    |
    | Set to true to enable. Disabled by default for backward compatibility.
    |
    */
    'prevent_self_referral' => false,

    /*
    |--------------------------------------------------------------------------
    | Reward Hooks
    |--------------------------------------------------------------------------
    |
    | Opt-in hooks that automatically dispatch referral events in response to
    | standard Laravel events. All hooks are disabled by default to keep the
    | package backward-compatible. Enable only what your application needs.
    |
    */
    'hooks' => [

        /*
        | Signup hook
        |
        | When enabled, listens to Illuminate\Auth\Events\Registered and
        | dispatches UserReferred automatically for any referral link IDs
        | stored in the request by the StoreReferralCode middleware.
        |
        | Requires the StoreReferralCode middleware to be active on your
        | registration route so referral data is present on the request.
        |
        | Set to true to enable.
        */
        'signup' => false,

        /*
        | First-purchase hook
        |
        | When enabled, listens to a configurable application event and
        | dispatches ReferralCase automatically for the configured programs.
        |
        | Options:
        |   enabled        - set to true to enable this hook
        |   event          - the fully-qualified event class to listen for
        |                    (e.g. \App\Events\OrderCreated::class)
        |   programs       - array of referral program names to reward
        |                    (must match names in referral_programs table)
        |   user_accessor  - property or method name on the event that
        |                    returns the Eloquent user model (default: 'user')
        |   reward_accessor - property or method name on the event to pass
        |                    as the $rewardObject to ReferralCase; when null
        |                    the event itself is passed as the reward object
        |
        | Example:
        |   'first_purchase' => [
        |       'enabled'         => true,
        |       'event'           => \App\Events\OrderCreated::class,
        |       'programs'        => ['welcome-bonus', 'first-purchase'],
        |       'user_accessor'   => 'user',
        |       'reward_accessor' => 'order',
        |   ],
        */
        'first_purchase' => [
            'enabled'         => false,
            'event'           => null,
            'programs'        => [],
            'user_accessor'   => 'user',
            'reward_accessor' => null,
        ],
    ],
];
