import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:36
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
* @see app/Http/Controllers/MemberPortalController.php:36
* @route '/member'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:36
* @route '/member'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:36
* @route '/member'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:36
* @route '/member'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:36
* @route '/member'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::dashboard
* @see app/Http/Controllers/MemberPortalController.php:36
* @route '/member'
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
* @see \App\Http\Controllers\MemberPortalController::onboarding
* @see app/Http/Controllers/MemberPortalController.php:178
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
* @see app/Http/Controllers/MemberPortalController.php:178
* @route '/member/onboarding'
*/
onboarding.url = (options?: RouteQueryOptions) => {
    return onboarding.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::onboarding
* @see app/Http/Controllers/MemberPortalController.php:178
* @route '/member/onboarding'
*/
onboarding.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: onboarding.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::onboarding
* @see app/Http/Controllers/MemberPortalController.php:178
* @route '/member/onboarding'
*/
onboarding.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: onboarding.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::onboarding
* @see app/Http/Controllers/MemberPortalController.php:178
* @route '/member/onboarding'
*/
const onboardingForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: onboarding.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::onboarding
* @see app/Http/Controllers/MemberPortalController.php:178
* @route '/member/onboarding'
*/
onboardingForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: onboarding.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::onboarding
* @see app/Http/Controllers/MemberPortalController.php:178
* @route '/member/onboarding'
*/
onboardingForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: onboarding.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

onboarding.form = onboardingForm

/**
* @see \App\Http\Controllers\MemberPortalController::submitOnboarding
* @see app/Http/Controllers/MemberPortalController.php:212
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
* @see app/Http/Controllers/MemberPortalController.php:212
* @route '/member/onboarding'
*/
submitOnboarding.url = (options?: RouteQueryOptions) => {
    return submitOnboarding.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::submitOnboarding
* @see app/Http/Controllers/MemberPortalController.php:212
* @route '/member/onboarding'
*/
submitOnboarding.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submitOnboarding.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::submitOnboarding
* @see app/Http/Controllers/MemberPortalController.php:212
* @route '/member/onboarding'
*/
const submitOnboardingForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submitOnboarding.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::submitOnboarding
* @see app/Http/Controllers/MemberPortalController.php:212
* @route '/member/onboarding'
*/
submitOnboardingForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submitOnboarding.url(options),
    method: 'post',
})

submitOnboarding.form = submitOnboardingForm

/**
* @see \App\Http\Controllers\MemberPortalController::markOnboardingStep
* @see app/Http/Controllers/MemberPortalController.php:223
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
* @see app/Http/Controllers/MemberPortalController.php:223
* @route '/member/onboarding/steps'
*/
markOnboardingStep.url = (options?: RouteQueryOptions) => {
    return markOnboardingStep.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::markOnboardingStep
* @see app/Http/Controllers/MemberPortalController.php:223
* @route '/member/onboarding/steps'
*/
markOnboardingStep.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markOnboardingStep.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::markOnboardingStep
* @see app/Http/Controllers/MemberPortalController.php:223
* @route '/member/onboarding/steps'
*/
const markOnboardingStepForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markOnboardingStep.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::markOnboardingStep
* @see app/Http/Controllers/MemberPortalController.php:223
* @route '/member/onboarding/steps'
*/
markOnboardingStepForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markOnboardingStep.url(options),
    method: 'post',
})

markOnboardingStep.form = markOnboardingStepForm

/**
* @see \App\Http\Controllers\MemberPortalController::uploadPaymentProof
* @see app/Http/Controllers/MemberPortalController.php:528
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
* @see app/Http/Controllers/MemberPortalController.php:528
* @route '/member/payments/proof'
*/
uploadPaymentProof.url = (options?: RouteQueryOptions) => {
    return uploadPaymentProof.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::uploadPaymentProof
* @see app/Http/Controllers/MemberPortalController.php:528
* @route '/member/payments/proof'
*/
uploadPaymentProof.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: uploadPaymentProof.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::uploadPaymentProof
* @see app/Http/Controllers/MemberPortalController.php:528
* @route '/member/payments/proof'
*/
const uploadPaymentProofForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uploadPaymentProof.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::uploadPaymentProof
* @see app/Http/Controllers/MemberPortalController.php:528
* @route '/member/payments/proof'
*/
uploadPaymentProofForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: uploadPaymentProof.url(options),
    method: 'post',
})

uploadPaymentProof.form = uploadPaymentProofForm

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:488
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
* @see app/Http/Controllers/MemberPortalController.php:488
* @route '/member/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:488
* @route '/member/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:488
* @route '/member/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:488
* @route '/member/profile'
*/
const profileForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:488
* @route '/member/profile'
*/
profileForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::profile
* @see app/Http/Controllers/MemberPortalController.php:488
* @route '/member/profile'
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
* @see \App\Http\Controllers\MemberPortalController::updateProfile
* @see app/Http/Controllers/MemberPortalController.php:501
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
* @see app/Http/Controllers/MemberPortalController.php:501
* @route '/member/profile'
*/
updateProfile.url = (options?: RouteQueryOptions) => {
    return updateProfile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::updateProfile
* @see app/Http/Controllers/MemberPortalController.php:501
* @route '/member/profile'
*/
updateProfile.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProfile.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\MemberPortalController::updateProfile
* @see app/Http/Controllers/MemberPortalController.php:501
* @route '/member/profile'
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
* @see \App\Http\Controllers\MemberPortalController::updateProfile
* @see app/Http/Controllers/MemberPortalController.php:501
* @route '/member/profile'
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
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:521
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
* @see app/Http/Controllers/MemberPortalController.php:521
* @route '/member/notifications'
*/
notifications.url = (options?: RouteQueryOptions) => {
    return notifications.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:521
* @route '/member/notifications'
*/
notifications.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:521
* @route '/member/notifications'
*/
notifications.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: notifications.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:521
* @route '/member/notifications'
*/
const notificationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:521
* @route '/member/notifications'
*/
notificationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: notifications.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::notifications
* @see app/Http/Controllers/MemberPortalController.php:521
* @route '/member/notifications'
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
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:232
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
* @see app/Http/Controllers/MemberPortalController.php:232
* @route '/member/savings'
*/
savings.url = (options?: RouteQueryOptions) => {
    return savings.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:232
* @route '/member/savings'
*/
savings.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: savings.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:232
* @route '/member/savings'
*/
savings.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: savings.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:232
* @route '/member/savings'
*/
const savingsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savings.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:232
* @route '/member/savings'
*/
savingsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savings.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::savings
* @see app/Http/Controllers/MemberPortalController.php:232
* @route '/member/savings'
*/
savingsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: savings.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

savings.form = savingsForm

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:368
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
* @see app/Http/Controllers/MemberPortalController.php:368
* @route '/member/loans'
*/
loans.url = (options?: RouteQueryOptions) => {
    return loans.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:368
* @route '/member/loans'
*/
loans.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:368
* @route '/member/loans'
*/
loans.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loans.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:368
* @route '/member/loans'
*/
const loansForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:368
* @route '/member/loans'
*/
loansForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loans.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::loans
* @see app/Http/Controllers/MemberPortalController.php:368
* @route '/member/loans'
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
* @see \App\Http\Controllers\MemberPortalController::applyLoan
* @see app/Http/Controllers/MemberPortalController.php:380
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
* @see app/Http/Controllers/MemberPortalController.php:380
* @route '/member/loans'
*/
applyLoan.url = (options?: RouteQueryOptions) => {
    return applyLoan.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::applyLoan
* @see app/Http/Controllers/MemberPortalController.php:380
* @route '/member/loans'
*/
applyLoan.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyLoan.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::applyLoan
* @see app/Http/Controllers/MemberPortalController.php:380
* @route '/member/loans'
*/
const applyLoanForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applyLoan.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::applyLoan
* @see app/Http/Controllers/MemberPortalController.php:380
* @route '/member/loans'
*/
applyLoanForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applyLoan.url(options),
    method: 'post',
})

applyLoan.form = applyLoanForm

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:398
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
* @see app/Http/Controllers/MemberPortalController.php:398
* @route '/member/points'
*/
points.url = (options?: RouteQueryOptions) => {
    return points.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:398
* @route '/member/points'
*/
points.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: points.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:398
* @route '/member/points'
*/
points.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: points.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:398
* @route '/member/points'
*/
const pointsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: points.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:398
* @route '/member/points'
*/
pointsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: points.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::points
* @see app/Http/Controllers/MemberPortalController.php:398
* @route '/member/points'
*/
pointsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: points.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

points.form = pointsForm

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:409
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
* @see app/Http/Controllers/MemberPortalController.php:409
* @route '/member/rewards'
*/
rewards.url = (options?: RouteQueryOptions) => {
    return rewards.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:409
* @route '/member/rewards'
*/
rewards.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rewards.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:409
* @route '/member/rewards'
*/
rewards.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: rewards.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:409
* @route '/member/rewards'
*/
const rewardsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rewards.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:409
* @route '/member/rewards'
*/
rewardsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rewards.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::rewards
* @see app/Http/Controllers/MemberPortalController.php:409
* @route '/member/rewards'
*/
rewardsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: rewards.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

rewards.form = rewardsForm

/**
* @see \App\Http\Controllers\MemberPortalController::redeemReward
* @see app/Http/Controllers/MemberPortalController.php:430
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
* @see app/Http/Controllers/MemberPortalController.php:430
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
* @see app/Http/Controllers/MemberPortalController.php:430
* @route '/member/rewards/{reward}/redeem'
*/
redeemReward.post = (args: { reward: string | { id: string } } | [reward: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redeemReward.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::redeemReward
* @see app/Http/Controllers/MemberPortalController.php:430
* @route '/member/rewards/{reward}/redeem'
*/
const redeemRewardForm = (args: { reward: string | { id: string } } | [reward: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redeemReward.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::redeemReward
* @see app/Http/Controllers/MemberPortalController.php:430
* @route '/member/rewards/{reward}/redeem'
*/
redeemRewardForm.post = (args: { reward: string | { id: string } } | [reward: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redeemReward.url(args, options),
    method: 'post',
})

redeemReward.form = redeemRewardForm

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:447
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
* @see app/Http/Controllers/MemberPortalController.php:447
* @route '/member/transactions'
*/
transactions.url = (options?: RouteQueryOptions) => {
    return transactions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:447
* @route '/member/transactions'
*/
transactions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:447
* @route '/member/transactions'
*/
transactions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: transactions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:447
* @route '/member/transactions'
*/
const transactionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:447
* @route '/member/transactions'
*/
transactionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberPortalController::transactions
* @see app/Http/Controllers/MemberPortalController.php:447
* @route '/member/transactions'
*/
transactionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: transactions.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

transactions.form = transactionsForm

const MemberPortalController = { dashboard, onboarding, submitOnboarding, markOnboardingStep, uploadPaymentProof, profile, updateProfile, notifications, savings, loans, applyLoan, points, rewards, redeemReward, transactions }

export default MemberPortalController