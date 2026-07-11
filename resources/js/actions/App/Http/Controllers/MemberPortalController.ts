import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:38
* @route '/member'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/member',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:38
* @route '/member'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:38
* @route '/member'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:38
* @route '/member'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::onboarding
* @see app/Http/Controllers/MemberPortalController.php:175
* @route '/member/onboarding'
*/
export const onboarding = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: onboarding.url(options),
    method: 'get',
})

onboarding.definition = {
    methods: ["get","head"],
    url: '/member/onboarding',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::onboarding
* @see app/Http/Controllers/MemberPortalController.php:175
* @route '/member/onboarding'
*/
onboarding.url = (options?: RouteQueryOptions) => {
    return onboarding.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::onboarding
* @see app/Http/Controllers/MemberPortalController.php:175
* @route '/member/onboarding'
*/
onboarding.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: onboarding.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::onboarding
* @see app/Http/Controllers/MemberPortalController.php:175
* @route '/member/onboarding'
*/
onboarding.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: onboarding.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::submitOnboarding
* @see app/Http/Controllers/MemberPortalController.php:209
* @route '/member/onboarding'
*/
export const submitOnboarding = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitOnboarding.url(options),
    method: 'post',
})

submitOnboarding.definition = {
    methods: ["post"],
    url: '/member/onboarding',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::submitOnboarding
* @see app/Http/Controllers/MemberPortalController.php:209
* @route '/member/onboarding'
*/
submitOnboarding.url = (options?: RouteQueryOptions) => {
    return submitOnboarding.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::submitOnboarding
* @see app/Http/Controllers/MemberPortalController.php:209
* @route '/member/onboarding'
*/
submitOnboarding.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitOnboarding.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::markOnboardingStep
* @see app/Http/Controllers/MemberPortalController.php:220
* @route '/member/onboarding/steps'
*/
export const markOnboardingStep = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markOnboardingStep.url(options),
    method: 'post',
})

markOnboardingStep.definition = {
    methods: ["post"],
    url: '/member/onboarding/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::markOnboardingStep
* @see app/Http/Controllers/MemberPortalController.php:220
* @route '/member/onboarding/steps'
*/
markOnboardingStep.url = (options?: RouteQueryOptions) => {
    return markOnboardingStep.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::markOnboardingStep
* @see app/Http/Controllers/MemberPortalController.php:220
* @route '/member/onboarding/steps'
*/
markOnboardingStep.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markOnboardingStep.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::uploadPaymentProof
* @see app/Http/Controllers/MemberPortalController.php:576
* @route '/member/payments/proof'
*/
export const uploadPaymentProof = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadPaymentProof.url(options),
    method: 'post',
})

uploadPaymentProof.definition = {
    methods: ["post"],
    url: '/member/payments/proof',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::uploadPaymentProof
* @see app/Http/Controllers/MemberPortalController.php:576
* @route '/member/payments/proof'
*/
uploadPaymentProof.url = (options?: RouteQueryOptions) => {
    return uploadPaymentProof.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::uploadPaymentProof
* @see app/Http/Controllers/MemberPortalController.php:576
* @route '/member/payments/proof'
*/
uploadPaymentProof.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadPaymentProof.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::createPaymentIntent
* @see app/Http/Controllers/MemberPortalController.php:609
* @route '/member/payments/intent'
*/
export const createPaymentIntent = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createPaymentIntent.url(options),
    method: 'post',
})

createPaymentIntent.definition = {
    methods: ["post"],
    url: '/member/payments/intent',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::createPaymentIntent
* @see app/Http/Controllers/MemberPortalController.php:609
* @route '/member/payments/intent'
*/
createPaymentIntent.url = (options?: RouteQueryOptions) => {
    return createPaymentIntent.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::createPaymentIntent
* @see app/Http/Controllers/MemberPortalController.php:609
* @route '/member/payments/intent'
*/
createPaymentIntent.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createPaymentIntent.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::paymentStatus
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
*/
export const paymentStatus = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: paymentStatus.url(args, options),
    method: 'get',
})

paymentStatus.definition = {
    methods: ["get","head"],
    url: '/member/payments/{payment}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::paymentStatus
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
*/
paymentStatus.url = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { payment: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { payment: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            payment: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        payment: typeof args.payment === 'object'
        ? args.payment.id
        : args.payment,
    }

    return paymentStatus.definition.url
            .replace('{payment}', parsedArgs.payment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::paymentStatus
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
*/
paymentStatus.get = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: paymentStatus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::paymentStatus
* @see app/Http/Controllers/MemberPortalController.php:719
* @route '/member/payments/{payment}/status'
*/
paymentStatus.head = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: paymentStatus.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:536
* @route '/member/profile'
*/
export const profile = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/member/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:536
* @route '/member/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:536
* @route '/member/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:536
* @route '/member/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::updateProfile
* @see app/Http/Controllers/MemberPortalController.php:549
* @route '/member/profile'
*/
export const updateProfile = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

updateProfile.definition = {
    methods: ["put"],
    url: '/member/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\MemberPortalController::updateProfile
* @see app/Http/Controllers/MemberPortalController.php:549
* @route '/member/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::updateProfile
* @see app/Http/Controllers/MemberPortalController.php:549
* @route '/member/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:569
* @route '/member/notifications'
*/
export const notifications = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

notifications.definition = {
    methods: ["get","head"],
    url: '/member/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:569
* @route '/member/notifications'
*/
notifications.url = (options?: RouteQueryOptions) => {
    return notifications.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:569
* @route '/member/notifications'
*/
notifications.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:569
* @route '/member/notifications'
*/
notifications.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifications.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:229
* @route '/member/savings'
*/
export const savings = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savings.url(options),
    method: 'get',
})

savings.definition = {
    methods: ["get","head"],
    url: '/member/savings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:229
* @route '/member/savings'
*/
savings.url = (options?: RouteQueryOptions) => {
    return savings.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:229
* @route '/member/savings'
*/
savings.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savings.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:229
* @route '/member/savings'
*/
savings.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: savings.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:416
* @route '/member/loans'
*/
export const loans = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loans.url(options),
    method: 'get',
})

loans.definition = {
    methods: ["get","head"],
    url: '/member/loans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:416
* @route '/member/loans'
*/
loans.url = (options?: RouteQueryOptions) => {
    return loans.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:416
* @route '/member/loans'
*/
loans.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:416
* @route '/member/loans'
*/
loans.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loans.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::applyLoan
* @see app/Http/Controllers/MemberPortalController.php:428
* @route '/member/loans'
*/
export const applyLoan = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyLoan.url(options),
    method: 'post',
})

applyLoan.definition = {
    methods: ["post"],
    url: '/member/loans',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::applyLoan
* @see app/Http/Controllers/MemberPortalController.php:428
* @route '/member/loans'
*/
applyLoan.url = (options?: RouteQueryOptions) => {
    return applyLoan.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::applyLoan
* @see app/Http/Controllers/MemberPortalController.php:428
* @route '/member/loans'
*/
applyLoan.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyLoan.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:446
* @route '/member/points'
*/
export const points = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: points.url(options),
    method: 'get',
})

points.definition = {
    methods: ["get","head"],
    url: '/member/points',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:446
* @route '/member/points'
*/
points.url = (options?: RouteQueryOptions) => {
    return points.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:446
* @route '/member/points'
*/
points.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: points.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:446
* @route '/member/points'
*/
points.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: points.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:457
* @route '/member/rewards'
*/
export const rewards = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rewards.url(options),
    method: 'get',
})

rewards.definition = {
    methods: ["get","head"],
    url: '/member/rewards',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:457
* @route '/member/rewards'
*/
rewards.url = (options?: RouteQueryOptions) => {
    return rewards.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:457
* @route '/member/rewards'
*/
rewards.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rewards.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:457
* @route '/member/rewards'
*/
rewards.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: rewards.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::redeemReward
* @see app/Http/Controllers/MemberPortalController.php:478
* @route '/member/rewards/{reward}/redeem'
*/
export const redeemReward = (args: { reward: string | { id: string } } | [reward: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redeemReward.url(args, options),
    method: 'post',
})

redeemReward.definition = {
    methods: ["post"],
    url: '/member/rewards/{reward}/redeem',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::redeemReward
* @see app/Http/Controllers/MemberPortalController.php:478
* @route '/member/rewards/{reward}/redeem'
*/
redeemReward.url = (args: { reward: string | { id: string } } | [reward: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reward: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { reward: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            reward: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reward: typeof args.reward === 'object'
        ? args.reward.id
        : args.reward,
    }

    return redeemReward.definition.url
            .replace('{reward}', parsedArgs.reward.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::redeemReward
* @see app/Http/Controllers/MemberPortalController.php:478
* @route '/member/rewards/{reward}/redeem'
*/
redeemReward.post = (args: { reward: string | { id: string } } | [reward: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redeemReward.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:495
* @route '/member/transactions'
*/
export const transactions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

transactions.definition = {
    methods: ["get","head"],
    url: '/member/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:495
* @route '/member/transactions'
*/
transactions.url = (options?: RouteQueryOptions) => {
    return transactions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:495
* @route '/member/transactions'
*/
transactions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:495
* @route '/member/transactions'
*/
transactions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transactions.url(options),
    method: 'head',
})

const MemberPortalController = { dashboard, onboarding, submitOnboarding, markOnboardingStep, uploadPaymentProof, createPaymentIntent, paymentStatus, profile, updateProfile, notifications, savings, loans, applyLoan, points, rewards, redeemReward, transactions }

export default MemberPortalController