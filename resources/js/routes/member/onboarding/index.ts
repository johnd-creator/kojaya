import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::submit
* @see app/Http/Controllers/MemberPortalController.php:212
* @route '/member/onboarding'
*/
export const submit = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

submit.definition = {
    methods: ["post"],
    url: '/member/onboarding',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::submit
* @see app/Http/Controllers/MemberPortalController.php:212
* @route '/member/onboarding'
*/
submit.url = (options?: RouteQueryOptions) => {
    return submit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::submit
* @see app/Http/Controllers/MemberPortalController.php:212
* @route '/member/onboarding'
*/
submit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::submit
* @see app/Http/Controllers/MemberPortalController.php:212
* @route '/member/onboarding'
*/
const submitForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::submit
* @see app/Http/Controllers/MemberPortalController.php:212
* @route '/member/onboarding'
*/
submitForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: submit.url(options),
    method: 'post',
})

submit.form = submitForm

/**
* @see \App\Http\Controllers\MemberPortalController::steps
* @see app/Http/Controllers/MemberPortalController.php:223
* @route '/member/onboarding/steps'
*/
export const steps = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: steps.url(options),
    method: 'post',
})

steps.definition = {
    methods: ["post"],
    url: '/member/onboarding/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::steps
* @see app/Http/Controllers/MemberPortalController.php:223
* @route '/member/onboarding/steps'
*/
steps.url = (options?: RouteQueryOptions) => {
    return steps.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::steps
* @see app/Http/Controllers/MemberPortalController.php:223
* @route '/member/onboarding/steps'
*/
steps.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: steps.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::steps
* @see app/Http/Controllers/MemberPortalController.php:223
* @route '/member/onboarding/steps'
*/
const stepsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: steps.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::steps
* @see app/Http/Controllers/MemberPortalController.php:223
* @route '/member/onboarding/steps'
*/
stepsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: steps.url(options),
    method: 'post',
})

steps.form = stepsForm

const onboarding = {
    submit: Object.assign(submit, submit),
    steps: Object.assign(steps, steps),
}

export default onboarding