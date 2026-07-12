import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::dashboard
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:73
* @route '/api/v1/member/dashboard'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::dashboard
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:73
* @route '/api/v1/member/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::dashboard
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:73
* @route '/api/v1/member/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::dashboard
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:73
* @route '/api/v1/member/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::onboardingStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:102
* @route '/api/v1/member/onboarding/status'
*/
export const onboardingStatus = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: onboardingStatus.url(options),
    method: 'get',
})

onboardingStatus.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/onboarding/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::onboardingStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:102
* @route '/api/v1/member/onboarding/status'
*/
onboardingStatus.url = (options?: RouteQueryOptions) => {
    return onboardingStatus.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::onboardingStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:102
* @route '/api/v1/member/onboarding/status'
*/
onboardingStatus.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: onboardingStatus.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::onboardingStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:102
* @route '/api/v1/member/onboarding/status'
*/
onboardingStatus.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: onboardingStatus.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::markOnboardingStep
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:107
* @route '/api/v1/member/onboarding/steps'
*/
export const markOnboardingStep = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markOnboardingStep.url(options),
    method: 'post',
})

markOnboardingStep.definition = {
    methods: ["post"],
    url: '/api/v1/member/onboarding/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::markOnboardingStep
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:107
* @route '/api/v1/member/onboarding/steps'
*/
markOnboardingStep.url = (options?: RouteQueryOptions) => {
    return markOnboardingStep.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::markOnboardingStep
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:107
* @route '/api/v1/member/onboarding/steps'
*/
markOnboardingStep.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markOnboardingStep.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::statusJourney
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:331
* @route '/api/v1/member/status-journey'
*/
export const statusJourney = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statusJourney.url(options),
    method: 'get',
})

statusJourney.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/status-journey',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::statusJourney
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:331
* @route '/api/v1/member/status-journey'
*/
statusJourney.url = (options?: RouteQueryOptions) => {
    return statusJourney.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::statusJourney
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:331
* @route '/api/v1/member/status-journey'
*/
statusJourney.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statusJourney.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::statusJourney
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:331
* @route '/api/v1/member/status-journey'
*/
statusJourney.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statusJourney.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::profile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:117
* @route '/api/v1/member/profile'
*/
export const profile = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::profile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:117
* @route '/api/v1/member/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::profile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:117
* @route '/api/v1/member/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::profile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:117
* @route '/api/v1/member/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::updateProfile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:127
* @route '/api/v1/member/profile'
*/
export const updateProfile = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

updateProfile.definition = {
    methods: ["put"],
    url: '/api/v1/member/profile',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::updateProfile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:127
* @route '/api/v1/member/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::updateProfile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:127
* @route '/api/v1/member/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::resignationStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:144
* @route '/api/v1/member/resignation'
*/
export const resignationStatus = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: resignationStatus.url(options),
    method: 'get',
})

resignationStatus.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/resignation',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::resignationStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:144
* @route '/api/v1/member/resignation'
*/
resignationStatus.url = (options?: RouteQueryOptions) => {
    return resignationStatus.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::resignationStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:144
* @route '/api/v1/member/resignation'
*/
resignationStatus.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: resignationStatus.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::resignationStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:144
* @route '/api/v1/member/resignation'
*/
resignationStatus.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: resignationStatus.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::notifications
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:620
* @route '/api/v1/member/notifications'
*/
export const notifications = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

notifications.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::notifications
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:620
* @route '/api/v1/member/notifications'
*/
notifications.url = (options?: RouteQueryOptions) => {
    return notifications.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::notifications
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:620
* @route '/api/v1/member/notifications'
*/
notifications.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::notifications
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:620
* @route '/api/v1/member/notifications'
*/
notifications.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifications.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::submitResignation
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:154
* @route '/api/v1/member/resignation'
*/
export const submitResignation = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitResignation.url(options),
    method: 'post',
})

submitResignation.definition = {
    methods: ["post"],
    url: '/api/v1/member/resignation',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::submitResignation
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:154
* @route '/api/v1/member/resignation'
*/
submitResignation.url = (options?: RouteQueryOptions) => {
    return submitResignation.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::submitResignation
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:154
* @route '/api/v1/member/resignation'
*/
submitResignation.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitResignation.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::cancelResignation
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:166
* @route '/api/v1/member/resignation'
*/
export const cancelResignation = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: cancelResignation.url(options),
    method: 'delete',
})

cancelResignation.definition = {
    methods: ["delete"],
    url: '/api/v1/member/resignation',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::cancelResignation
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:166
* @route '/api/v1/member/resignation'
*/
cancelResignation.url = (options?: RouteQueryOptions) => {
    return cancelResignation.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::cancelResignation
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:166
* @route '/api/v1/member/resignation'
*/
cancelResignation.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: cancelResignation.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsSummary
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:182
* @route '/api/v1/member/savings/summary'
*/
export const savingsSummary = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savingsSummary.url(options),
    method: 'get',
})

savingsSummary.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/savings/summary',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsSummary
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:182
* @route '/api/v1/member/savings/summary'
*/
savingsSummary.url = (options?: RouteQueryOptions) => {
    return savingsSummary.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsSummary
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:182
* @route '/api/v1/member/savings/summary'
*/
savingsSummary.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savingsSummary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsSummary
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:182
* @route '/api/v1/member/savings/summary'
*/
savingsSummary.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: savingsSummary.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsLedger
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:203
* @route '/api/v1/member/savings/ledger'
*/
export const savingsLedger = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savingsLedger.url(options),
    method: 'get',
})

savingsLedger.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/savings/ledger',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsLedger
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:203
* @route '/api/v1/member/savings/ledger'
*/
savingsLedger.url = (options?: RouteQueryOptions) => {
    return savingsLedger.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsLedger
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:203
* @route '/api/v1/member/savings/ledger'
*/
savingsLedger.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savingsLedger.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsLedger
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:203
* @route '/api/v1/member/savings/ledger'
*/
savingsLedger.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: savingsLedger.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::requestSavingsWithdrawal
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:232
* @route '/api/v1/member/savings/withdraw'
*/
export const requestSavingsWithdrawal = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestSavingsWithdrawal.url(options),
    method: 'post',
})

requestSavingsWithdrawal.definition = {
    methods: ["post"],
    url: '/api/v1/member/savings/withdraw',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::requestSavingsWithdrawal
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:232
* @route '/api/v1/member/savings/withdraw'
*/
requestSavingsWithdrawal.url = (options?: RouteQueryOptions) => {
    return requestSavingsWithdrawal.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::requestSavingsWithdrawal
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:232
* @route '/api/v1/member/savings/withdraw'
*/
requestSavingsWithdrawal.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestSavingsWithdrawal.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::invoices
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:244
* @route '/api/v1/member/dues/invoices'
*/
export const invoices = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoices.url(options),
    method: 'get',
})

invoices.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/dues/invoices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::invoices
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:244
* @route '/api/v1/member/dues/invoices'
*/
invoices.url = (options?: RouteQueryOptions) => {
    return invoices.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::invoices
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:244
* @route '/api/v1/member/dues/invoices'
*/
invoices.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::invoices
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:244
* @route '/api/v1/member/dues/invoices'
*/
invoices.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: invoices.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showInvoice
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:259
* @route '/api/v1/member/dues/invoices/{invoice}'
*/
export const showInvoice = (args: { invoice: string | number | { id: string | number } } | [invoice: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showInvoice.url(args, options),
    method: 'get',
})

showInvoice.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/dues/invoices/{invoice}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showInvoice
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:259
* @route '/api/v1/member/dues/invoices/{invoice}'
*/
showInvoice.url = (args: { invoice: string | number | { id: string | number } } | [invoice: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return showInvoice.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showInvoice
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:259
* @route '/api/v1/member/dues/invoices/{invoice}'
*/
showInvoice.get = (args: { invoice: string | number | { id: string | number } } | [invoice: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showInvoice.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showInvoice
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:259
* @route '/api/v1/member/dues/invoices/{invoice}'
*/
showInvoice.head = (args: { invoice: string | number | { id: string | number } } | [invoice: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showInvoice.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::createPaymentIntent
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:271
* @route '/api/v1/member/dues/invoices/{invoice}/payment-intent'
*/
export const createPaymentIntent = (args: { invoice: string | number | { id: string | number } } | [invoice: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createPaymentIntent.url(args, options),
    method: 'post',
})

createPaymentIntent.definition = {
    methods: ["post"],
    url: '/api/v1/member/dues/invoices/{invoice}/payment-intent',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::createPaymentIntent
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:271
* @route '/api/v1/member/dues/invoices/{invoice}/payment-intent'
*/
createPaymentIntent.url = (args: { invoice: string | number | { id: string | number } } | [invoice: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { invoice: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { invoice: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            invoice: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        invoice: typeof args.invoice === 'object'
        ? args.invoice.id
        : args.invoice,
    }

    return createPaymentIntent.definition.url
            .replace('{invoice}', parsedArgs.invoice.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::createPaymentIntent
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:271
* @route '/api/v1/member/dues/invoices/{invoice}/payment-intent'
*/
createPaymentIntent.post = (args: { invoice: string | number | { id: string | number } } | [invoice: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createPaymentIntent.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::payments
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:312
* @route '/api/v1/member/payments'
*/
export const payments = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payments.url(options),
    method: 'get',
})

payments.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/payments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::payments
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:312
* @route '/api/v1/member/payments'
*/
payments.url = (options?: RouteQueryOptions) => {
    return payments.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::payments
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:312
* @route '/api/v1/member/payments'
*/
payments.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payments.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::payments
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:312
* @route '/api/v1/member/payments'
*/
payments.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payments.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showPayment
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:672
* @route '/api/v1/member/payments/{payment}'
*/
export const showPayment = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showPayment.url(args, options),
    method: 'get',
})

showPayment.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/payments/{payment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showPayment
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:672
* @route '/api/v1/member/payments/{payment}'
*/
showPayment.url = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return showPayment.definition.url
            .replace('{payment}', parsedArgs.payment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showPayment
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:672
* @route '/api/v1/member/payments/{payment}'
*/
showPayment.get = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showPayment.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showPayment
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:672
* @route '/api/v1/member/payments/{payment}'
*/
showPayment.head = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showPayment.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::paymentStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:358
* @route '/api/v1/member/payments/{payment}/status'
*/
export const paymentStatus = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: paymentStatus.url(args, options),
    method: 'get',
})

paymentStatus.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/payments/{payment}/status',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::paymentStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:358
* @route '/api/v1/member/payments/{payment}/status'
*/
paymentStatus.url = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::paymentStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:358
* @route '/api/v1/member/payments/{payment}/status'
*/
paymentStatus.get = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: paymentStatus.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::paymentStatus
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:358
* @route '/api/v1/member/payments/{payment}/status'
*/
paymentStatus.head = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: paymentStatus.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:383
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
export const qrisImage = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: qrisImage.url(args, options),
    method: 'get',
})

qrisImage.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/payments/{payment}/qris-image',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:383
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
qrisImage.url = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return qrisImage.definition.url
            .replace('{payment}', parsedArgs.payment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:383
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
qrisImage.get = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: qrisImage.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::qrisImage
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:383
* @route '/api/v1/member/payments/{payment}/qris-image'
*/
qrisImage.head = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: qrisImage.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::paymentReceipt
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:336
* @route '/api/v1/member/payments/{payment}/receipt'
*/
export const paymentReceipt = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: paymentReceipt.url(args, options),
    method: 'get',
})

paymentReceipt.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/payments/{payment}/receipt',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::paymentReceipt
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:336
* @route '/api/v1/member/payments/{payment}/receipt'
*/
paymentReceipt.url = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return paymentReceipt.definition.url
            .replace('{payment}', parsedArgs.payment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::paymentReceipt
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:336
* @route '/api/v1/member/payments/{payment}/receipt'
*/
paymentReceipt.get = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: paymentReceipt.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::paymentReceipt
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:336
* @route '/api/v1/member/payments/{payment}/receipt'
*/
paymentReceipt.head = (args: { payment: string | number | { id: string | number } } | [payment: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: paymentReceipt.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::uploadPaymentProof
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:420
* @route '/api/v1/member/payments/proof'
*/
export const uploadPaymentProof = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadPaymentProof.url(options),
    method: 'post',
})

uploadPaymentProof.definition = {
    methods: ["post"],
    url: '/api/v1/member/payments/proof',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::uploadPaymentProof
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:420
* @route '/api/v1/member/payments/proof'
*/
uploadPaymentProof.url = (options?: RouteQueryOptions) => {
    return uploadPaymentProof.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::uploadPaymentProof
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:420
* @route '/api/v1/member/payments/proof'
*/
uploadPaymentProof.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadPaymentProof.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::bills
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:687
* @route '/api/v1/member/bills'
*/
export const bills = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: bills.url(options),
    method: 'get',
})

bills.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/bills',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::bills
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:687
* @route '/api/v1/member/bills'
*/
bills.url = (options?: RouteQueryOptions) => {
    return bills.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::bills
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:687
* @route '/api/v1/member/bills'
*/
bills.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: bills.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::bills
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:687
* @route '/api/v1/member/bills'
*/
bills.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: bills.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showBill
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:781
* @route '/api/v1/member/bills/{bill}'
*/
export const showBill = (args: { bill: string | number } | [bill: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showBill.url(args, options),
    method: 'get',
})

showBill.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/bills/{bill}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showBill
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:781
* @route '/api/v1/member/bills/{bill}'
*/
showBill.url = (args: { bill: string | number } | [bill: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bill: args }
    }

    if (Array.isArray(args)) {
        args = {
            bill: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        bill: args.bill,
    }

    return showBill.definition.url
            .replace('{bill}', parsedArgs.bill.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showBill
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:781
* @route '/api/v1/member/bills/{bill}'
*/
showBill.get = (args: { bill: string | number } | [bill: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showBill.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::showBill
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:781
* @route '/api/v1/member/bills/{bill}'
*/
showBill.head = (args: { bill: string | number } | [bill: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showBill.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::createBillPaymentIntent
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:802
* @route '/api/v1/member/bills/{bill}/payment-intent'
*/
export const createBillPaymentIntent = (args: { bill: string | number } | [bill: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createBillPaymentIntent.url(args, options),
    method: 'post',
})

createBillPaymentIntent.definition = {
    methods: ["post"],
    url: '/api/v1/member/bills/{bill}/payment-intent',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::createBillPaymentIntent
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:802
* @route '/api/v1/member/bills/{bill}/payment-intent'
*/
createBillPaymentIntent.url = (args: { bill: string | number } | [bill: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { bill: args }
    }

    if (Array.isArray(args)) {
        args = {
            bill: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        bill: args.bill,
    }

    return createBillPaymentIntent.definition.url
            .replace('{bill}', parsedArgs.bill.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::createBillPaymentIntent
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:802
* @route '/api/v1/member/bills/{bill}/payment-intent'
*/
createBillPaymentIntent.post = (args: { bill: string | number } | [bill: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: createBillPaymentIntent.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loanOptions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:460
* @route '/api/v1/member/loans/options'
*/
export const loanOptions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loanOptions.url(options),
    method: 'get',
})

loanOptions.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/loans/options',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loanOptions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:460
* @route '/api/v1/member/loans/options'
*/
loanOptions.url = (options?: RouteQueryOptions) => {
    return loanOptions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loanOptions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:460
* @route '/api/v1/member/loans/options'
*/
loanOptions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loanOptions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loanOptions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:460
* @route '/api/v1/member/loans/options'
*/
loanOptions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loanOptions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loans
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:448
* @route '/api/v1/member/loans'
*/
export const loans = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loans.url(options),
    method: 'get',
})

loans.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/loans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loans
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:448
* @route '/api/v1/member/loans'
*/
loans.url = (options?: RouteQueryOptions) => {
    return loans.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loans
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:448
* @route '/api/v1/member/loans'
*/
loans.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loans
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:448
* @route '/api/v1/member/loans'
*/
loans.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loans.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::applyLoan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:472
* @route '/api/v1/member/loans'
*/
export const applyLoan = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyLoan.url(options),
    method: 'post',
})

applyLoan.definition = {
    methods: ["post"],
    url: '/api/v1/member/loans',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::applyLoan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:472
* @route '/api/v1/member/loans'
*/
applyLoan.url = (options?: RouteQueryOptions) => {
    return applyLoan.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::applyLoan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:472
* @route '/api/v1/member/loans'
*/
applyLoan.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyLoan.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:487
* @route '/api/v1/member/loans/{loan}'
*/
export const loan = (args: { loan: string | number | { id: string | number } } | [loan: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loan.url(args, options),
    method: 'get',
})

loan.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/loans/{loan}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:487
* @route '/api/v1/member/loans/{loan}'
*/
loan.url = (args: { loan: string | number | { id: string | number } } | [loan: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { loan: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            loan: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan: typeof args.loan === 'object'
        ? args.loan.id
        : args.loan,
    }

    return loan.definition.url
            .replace('{loan}', parsedArgs.loan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:487
* @route '/api/v1/member/loans/{loan}'
*/
loan.get = (args: { loan: string | number | { id: string | number } } | [loan: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loan.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:487
* @route '/api/v1/member/loans/{loan}'
*/
loan.head = (args: { loan: string | number | { id: string | number } } | [loan: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loan.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::requestLoanRestructure
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:498
* @route '/api/v1/member/loans/{loan}/restructure'
*/
export const requestLoanRestructure = (args: { loan: string | number | { id: string | number } } | [loan: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestLoanRestructure.url(args, options),
    method: 'post',
})

requestLoanRestructure.definition = {
    methods: ["post"],
    url: '/api/v1/member/loans/{loan}/restructure',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::requestLoanRestructure
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:498
* @route '/api/v1/member/loans/{loan}/restructure'
*/
requestLoanRestructure.url = (args: { loan: string | number | { id: string | number } } | [loan: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { loan: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            loan: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan: typeof args.loan === 'object'
        ? args.loan.id
        : args.loan,
    }

    return requestLoanRestructure.definition.url
            .replace('{loan}', parsedArgs.loan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::requestLoanRestructure
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:498
* @route '/api/v1/member/loans/{loan}/restructure'
*/
requestLoanRestructure.post = (args: { loan: string | number | { id: string | number } } | [loan: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestLoanRestructure.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::shu
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:512
* @route '/api/v1/member/shu'
*/
export const shu = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shu.url(options),
    method: 'get',
})

shu.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/shu',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::shu
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:512
* @route '/api/v1/member/shu'
*/
shu.url = (options?: RouteQueryOptions) => {
    return shu.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::shu
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:512
* @route '/api/v1/member/shu'
*/
shu.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shu.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::shu
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:512
* @route '/api/v1/member/shu'
*/
shu.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: shu.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::rewardRedemptions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:526
* @route '/api/v1/member/reward-redemptions'
*/
export const rewardRedemptions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rewardRedemptions.url(options),
    method: 'get',
})

rewardRedemptions.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/reward-redemptions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::rewardRedemptions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:526
* @route '/api/v1/member/reward-redemptions'
*/
rewardRedemptions.url = (options?: RouteQueryOptions) => {
    return rewardRedemptions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::rewardRedemptions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:526
* @route '/api/v1/member/reward-redemptions'
*/
rewardRedemptions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rewardRedemptions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::rewardRedemptions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:526
* @route '/api/v1/member/reward-redemptions'
*/
rewardRedemptions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: rewardRedemptions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::transactions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:555
* @route '/api/v1/member/transactions'
*/
export const transactions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

transactions.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::transactions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:555
* @route '/api/v1/member/transactions'
*/
transactions.url = (options?: RouteQueryOptions) => {
    return transactions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::transactions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:555
* @route '/api/v1/member/transactions'
*/
transactions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::transactions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:555
* @route '/api/v1/member/transactions'
*/
transactions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transactions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::unifiedTransactions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:859
* @route '/api/v1/member/transactions/unified'
*/
export const unifiedTransactions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unifiedTransactions.url(options),
    method: 'get',
})

unifiedTransactions.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/transactions/unified',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::unifiedTransactions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:859
* @route '/api/v1/member/transactions/unified'
*/
unifiedTransactions.url = (options?: RouteQueryOptions) => {
    return unifiedTransactions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::unifiedTransactions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:859
* @route '/api/v1/member/transactions/unified'
*/
unifiedTransactions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: unifiedTransactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::unifiedTransactions
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:859
* @route '/api/v1/member/transactions/unified'
*/
unifiedTransactions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: unifiedTransactions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::supportTickets
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:629
* @route '/api/v1/member/support-tickets'
*/
export const supportTickets = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: supportTickets.url(options),
    method: 'get',
})

supportTickets.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/support-tickets',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::supportTickets
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:629
* @route '/api/v1/member/support-tickets'
*/
supportTickets.url = (options?: RouteQueryOptions) => {
    return supportTickets.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::supportTickets
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:629
* @route '/api/v1/member/support-tickets'
*/
supportTickets.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: supportTickets.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::supportTickets
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:629
* @route '/api/v1/member/support-tickets'
*/
supportTickets.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: supportTickets.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::storeSupportTicket
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:639
* @route '/api/v1/member/support-tickets'
*/
export const storeSupportTicket = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeSupportTicket.url(options),
    method: 'post',
})

storeSupportTicket.definition = {
    methods: ["post"],
    url: '/api/v1/member/support-tickets',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::storeSupportTicket
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:639
* @route '/api/v1/member/support-tickets'
*/
storeSupportTicket.url = (options?: RouteQueryOptions) => {
    return storeSupportTicket.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::storeSupportTicket
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:639
* @route '/api/v1/member/support-tickets'
*/
storeSupportTicket.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeSupportTicket.url(options),
    method: 'post',
})

const MemberSelfServiceController = { dashboard, onboardingStatus, markOnboardingStep, statusJourney, profile, updateProfile, resignationStatus, notifications, submitResignation, cancelResignation, savingsSummary, savingsLedger, requestSavingsWithdrawal, invoices, showInvoice, createPaymentIntent, payments, showPayment, paymentStatus, qrisImage, paymentReceipt, uploadPaymentProof, bills, showBill, createBillPaymentIntent, loanOptions, loans, applyLoan, loan, requestLoanRestructure, shu, rewardRedemptions, transactions, unifiedTransactions, supportTickets, storeSupportTicket }

export default MemberSelfServiceController