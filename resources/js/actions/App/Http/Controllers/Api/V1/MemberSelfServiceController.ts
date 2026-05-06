import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::dashboard
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:25
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:25
* @route '/api/v1/member/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::dashboard
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:25
* @route '/api/v1/member/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::dashboard
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:25
* @route '/api/v1/member/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::dashboard
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:25
* @route '/api/v1/member/dashboard'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::dashboard
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:25
* @route '/api/v1/member/dashboard'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::dashboard
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:25
* @route '/api/v1/member/dashboard'
*/
dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dashboard.form = dashboardForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::profile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:46
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:46
* @route '/api/v1/member/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::profile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:46
* @route '/api/v1/member/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::profile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:46
* @route '/api/v1/member/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::profile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:46
* @route '/api/v1/member/profile'
*/
const profileForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::profile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:46
* @route '/api/v1/member/profile'
*/
profileForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::profile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:46
* @route '/api/v1/member/profile'
*/
profileForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

profile.form = profileForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::updateProfile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:56
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:56
* @route '/api/v1/member/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::updateProfile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:56
* @route '/api/v1/member/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::updateProfile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:56
* @route '/api/v1/member/profile'
*/
const updateProfileForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateProfile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::updateProfile
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:56
* @route '/api/v1/member/profile'
*/
updateProfileForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateProfile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateProfile.form = updateProfileForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsSummary
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:81
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:81
* @route '/api/v1/member/savings/summary'
*/
savingsSummary.url = (options?: RouteQueryOptions) => {
    return savingsSummary.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsSummary
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:81
* @route '/api/v1/member/savings/summary'
*/
savingsSummary.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savingsSummary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsSummary
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:81
* @route '/api/v1/member/savings/summary'
*/
savingsSummary.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: savingsSummary.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsSummary
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:81
* @route '/api/v1/member/savings/summary'
*/
const savingsSummaryForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savingsSummary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsSummary
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:81
* @route '/api/v1/member/savings/summary'
*/
savingsSummaryForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savingsSummary.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsSummary
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:81
* @route '/api/v1/member/savings/summary'
*/
savingsSummaryForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savingsSummary.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

savingsSummary.form = savingsSummaryForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsLedger
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:112
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:112
* @route '/api/v1/member/savings/ledger'
*/
savingsLedger.url = (options?: RouteQueryOptions) => {
    return savingsLedger.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsLedger
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:112
* @route '/api/v1/member/savings/ledger'
*/
savingsLedger.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savingsLedger.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsLedger
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:112
* @route '/api/v1/member/savings/ledger'
*/
savingsLedger.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: savingsLedger.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsLedger
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:112
* @route '/api/v1/member/savings/ledger'
*/
const savingsLedgerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savingsLedger.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsLedger
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:112
* @route '/api/v1/member/savings/ledger'
*/
savingsLedgerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savingsLedger.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::savingsLedger
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:112
* @route '/api/v1/member/savings/ledger'
*/
savingsLedgerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savingsLedger.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

savingsLedger.form = savingsLedgerForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::invoices
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:143
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:143
* @route '/api/v1/member/dues/invoices'
*/
invoices.url = (options?: RouteQueryOptions) => {
    return invoices.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::invoices
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:143
* @route '/api/v1/member/dues/invoices'
*/
invoices.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::invoices
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:143
* @route '/api/v1/member/dues/invoices'
*/
invoices.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: invoices.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::invoices
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:143
* @route '/api/v1/member/dues/invoices'
*/
const invoicesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::invoices
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:143
* @route '/api/v1/member/dues/invoices'
*/
invoicesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoices.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::invoices
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:143
* @route '/api/v1/member/dues/invoices'
*/
invoicesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoices.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

invoices.form = invoicesForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::payments
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:154
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:154
* @route '/api/v1/member/payments'
*/
payments.url = (options?: RouteQueryOptions) => {
    return payments.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::payments
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:154
* @route '/api/v1/member/payments'
*/
payments.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: payments.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::payments
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:154
* @route '/api/v1/member/payments'
*/
payments.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: payments.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::payments
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:154
* @route '/api/v1/member/payments'
*/
const paymentsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payments.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::payments
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:154
* @route '/api/v1/member/payments'
*/
paymentsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payments.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::payments
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:154
* @route '/api/v1/member/payments'
*/
paymentsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: payments.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

payments.form = paymentsForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::uploadPaymentProof
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:164
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:164
* @route '/api/v1/member/payments/proof'
*/
uploadPaymentProof.url = (options?: RouteQueryOptions) => {
    return uploadPaymentProof.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::uploadPaymentProof
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:164
* @route '/api/v1/member/payments/proof'
*/
uploadPaymentProof.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadPaymentProof.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::uploadPaymentProof
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:164
* @route '/api/v1/member/payments/proof'
*/
const uploadPaymentProofForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uploadPaymentProof.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::uploadPaymentProof
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:164
* @route '/api/v1/member/payments/proof'
*/
uploadPaymentProofForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uploadPaymentProof.url(options),
    method: 'post',
})

uploadPaymentProof.form = uploadPaymentProofForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loans
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:190
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:190
* @route '/api/v1/member/loans'
*/
loans.url = (options?: RouteQueryOptions) => {
    return loans.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loans
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:190
* @route '/api/v1/member/loans'
*/
loans.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loans
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:190
* @route '/api/v1/member/loans'
*/
loans.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loans.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loans
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:190
* @route '/api/v1/member/loans'
*/
const loansForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loans
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:190
* @route '/api/v1/member/loans'
*/
loansForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loans
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:190
* @route '/api/v1/member/loans'
*/
loansForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loans.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

loans.form = loansForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::applyLoan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:201
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:201
* @route '/api/v1/member/loans'
*/
applyLoan.url = (options?: RouteQueryOptions) => {
    return applyLoan.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::applyLoan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:201
* @route '/api/v1/member/loans'
*/
applyLoan.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyLoan.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::applyLoan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:201
* @route '/api/v1/member/loans'
*/
const applyLoanForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applyLoan.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::applyLoan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:201
* @route '/api/v1/member/loans'
*/
applyLoanForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applyLoan.url(options),
    method: 'post',
})

applyLoan.form = applyLoanForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:214
* @route '/api/v1/member/loans/{loan}'
*/
export const loan = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loan.url(args, options),
    method: 'get',
})

loan.definition = {
    methods: ["get","head"],
    url: '/api/v1/member/loans/{loan}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:214
* @route '/api/v1/member/loans/{loan}'
*/
loan.url = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:214
* @route '/api/v1/member/loans/{loan}'
*/
loan.get = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loan.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:214
* @route '/api/v1/member/loans/{loan}'
*/
loan.head = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loan.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:214
* @route '/api/v1/member/loans/{loan}'
*/
const loanForm = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loan.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:214
* @route '/api/v1/member/loans/{loan}'
*/
loanForm.get = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loan.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::loan
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:214
* @route '/api/v1/member/loans/{loan}'
*/
loanForm.head = (args: { loan: number | { id: number } } | [loan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loan.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

loan.form = loanForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::shu
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:225
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:225
* @route '/api/v1/member/shu'
*/
shu.url = (options?: RouteQueryOptions) => {
    return shu.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::shu
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:225
* @route '/api/v1/member/shu'
*/
shu.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shu.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::shu
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:225
* @route '/api/v1/member/shu'
*/
shu.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: shu.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::shu
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:225
* @route '/api/v1/member/shu'
*/
const shuForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: shu.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::shu
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:225
* @route '/api/v1/member/shu'
*/
shuForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: shu.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::shu
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:225
* @route '/api/v1/member/shu'
*/
shuForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: shu.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

shu.form = shuForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::notifications
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:239
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:239
* @route '/api/v1/member/notifications'
*/
notifications.url = (options?: RouteQueryOptions) => {
    return notifications.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::notifications
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:239
* @route '/api/v1/member/notifications'
*/
notifications.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::notifications
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:239
* @route '/api/v1/member/notifications'
*/
notifications.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifications.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::notifications
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:239
* @route '/api/v1/member/notifications'
*/
const notificationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::notifications
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:239
* @route '/api/v1/member/notifications'
*/
notificationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::notifications
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:239
* @route '/api/v1/member/notifications'
*/
notificationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

notifications.form = notificationsForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::supportTickets
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:247
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:247
* @route '/api/v1/member/support-tickets'
*/
supportTickets.url = (options?: RouteQueryOptions) => {
    return supportTickets.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::supportTickets
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:247
* @route '/api/v1/member/support-tickets'
*/
supportTickets.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: supportTickets.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::supportTickets
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:247
* @route '/api/v1/member/support-tickets'
*/
supportTickets.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: supportTickets.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::supportTickets
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:247
* @route '/api/v1/member/support-tickets'
*/
const supportTicketsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: supportTickets.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::supportTickets
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:247
* @route '/api/v1/member/support-tickets'
*/
supportTicketsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: supportTickets.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::supportTickets
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:247
* @route '/api/v1/member/support-tickets'
*/
supportTicketsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: supportTickets.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

supportTickets.form = supportTicketsForm

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::storeSupportTicket
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:256
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
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:256
* @route '/api/v1/member/support-tickets'
*/
storeSupportTicket.url = (options?: RouteQueryOptions) => {
    return storeSupportTicket.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::storeSupportTicket
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:256
* @route '/api/v1/member/support-tickets'
*/
storeSupportTicket.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeSupportTicket.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::storeSupportTicket
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:256
* @route '/api/v1/member/support-tickets'
*/
const storeSupportTicketForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeSupportTicket.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\MemberSelfServiceController::storeSupportTicket
* @see app/Http/Controllers/Api/V1/MemberSelfServiceController.php:256
* @route '/api/v1/member/support-tickets'
*/
storeSupportTicketForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeSupportTicket.url(options),
    method: 'post',
})

storeSupportTicket.form = storeSupportTicketForm

const MemberSelfServiceController = { dashboard, profile, updateProfile, savingsSummary, savingsLedger, invoices, payments, uploadPaymentProof, loans, applyLoan, loan, shu, notifications, supportTickets, storeSupportTicket }

export default MemberSelfServiceController