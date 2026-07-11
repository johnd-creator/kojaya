import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::submit
* @see app/Http/Controllers/MemberPortalController.php:209
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
* @see app/Http/Controllers/MemberPortalController.php:209
* @route '/member/onboarding'
*/
submit.url = (options?: RouteQueryOptions) => {
    return submit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::submit
* @see app/Http/Controllers/MemberPortalController.php:209
* @route '/member/onboarding'
*/
submit.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: submit.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MemberPortalController::steps
* @see app/Http/Controllers/MemberPortalController.php:220
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
* @see app/Http/Controllers/MemberPortalController.php:220
* @route '/member/onboarding/steps'
*/
steps.url = (options?: RouteQueryOptions) => {
    return steps.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberPortalController::steps
* @see app/Http/Controllers/MemberPortalController.php:220
* @route '/member/onboarding/steps'
*/
steps.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: steps.url(options),
    method: 'post',
})

const onboarding = {
    submit: Object.assign(submit, submit),
    steps: Object.assign(steps, steps),
}

export default onboarding