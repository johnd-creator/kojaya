import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::dashboard
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:23
* @route '/cooperative/operator/dashboard'
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
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
export const approvalInbox = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: approvalInbox.url(options),
    method: 'get',
})

approvalInbox.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/approval-inbox',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
approvalInbox.url = (options?: RouteQueryOptions) => {
    return approvalInbox.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
approvalInbox.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: approvalInbox.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
approvalInbox.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: approvalInbox.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
const approvalInboxForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: approvalInbox.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
approvalInboxForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: approvalInbox.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::approvalInbox
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:32
* @route '/cooperative/operator/approval-inbox'
*/
approvalInboxForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: approvalInbox.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

approvalInbox.form = approvalInboxForm

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
export const exceptions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exceptions.url(options),
    method: 'get',
})

exceptions.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/exceptions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
exceptions.url = (options?: RouteQueryOptions) => {
    return exceptions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
exceptions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exceptions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
exceptions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exceptions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
const exceptionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exceptions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
exceptionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exceptions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exceptions
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:39
* @route '/cooperative/operator/exceptions'
*/
exceptionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exceptions.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

exceptions.form = exceptionsForm

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
export const analytics = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: analytics.url(options),
    method: 'get',
})

analytics.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/analytics',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
analytics.url = (options?: RouteQueryOptions) => {
    return analytics.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
analytics.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: analytics.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
analytics.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: analytics.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
const analyticsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: analytics.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
analyticsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: analytics.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::analytics
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:46
* @route '/cooperative/operator/analytics'
*/
analyticsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: analytics.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

analytics.form = analyticsForm

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closingPage
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
export const closingPage = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: closingPage.url(options),
    method: 'get',
})

closingPage.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/closing',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closingPage
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
closingPage.url = (options?: RouteQueryOptions) => {
    return closingPage.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closingPage
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
closingPage.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: closingPage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closingPage
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
closingPage.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: closingPage.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closingPage
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
const closingPageForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: closingPage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closingPage
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
closingPageForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: closingPage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closingPage
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:53
* @route '/cooperative/operator/closing'
*/
closingPageForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: closingPage.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

closingPage.form = closingPageForm

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:60
* @route '/cooperative/operator/closing/{period}'
*/
export const closing = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: closing.url(args, options),
    method: 'get',
})

closing.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/closing/{period}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:60
* @route '/cooperative/operator/closing/{period}'
*/
closing.url = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { period: args }
    }

    if (Array.isArray(args)) {
        args = {
            period: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        period: args.period,
    }

    return closing.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:60
* @route '/cooperative/operator/closing/{period}'
*/
closing.get = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: closing.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:60
* @route '/cooperative/operator/closing/{period}'
*/
closing.head = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: closing.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:60
* @route '/cooperative/operator/closing/{period}'
*/
const closingForm = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: closing.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:60
* @route '/cooperative/operator/closing/{period}'
*/
closingForm.get = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: closing.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::closing
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:60
* @route '/cooperative/operator/closing/{period}'
*/
closingForm.head = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: closing.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

closing.form = closingForm

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::completeClosingStep
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:79
* @route '/cooperative/operator/closing/{period}/steps'
*/
export const completeClosingStep = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: completeClosingStep.url(args, options),
    method: 'post',
})

completeClosingStep.definition = {
    methods: ["post"],
    url: '/cooperative/operator/closing/{period}/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::completeClosingStep
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:79
* @route '/cooperative/operator/closing/{period}/steps'
*/
completeClosingStep.url = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { period: args }
    }

    if (Array.isArray(args)) {
        args = {
            period: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        period: args.period,
    }

    return completeClosingStep.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::completeClosingStep
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:79
* @route '/cooperative/operator/closing/{period}/steps'
*/
completeClosingStep.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: completeClosingStep.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::completeClosingStep
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:79
* @route '/cooperative/operator/closing/{period}/steps'
*/
const completeClosingStepForm = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: completeClosingStep.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::completeClosingStep
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:79
* @route '/cooperative/operator/closing/{period}/steps'
*/
completeClosingStepForm.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: completeClosingStep.url(args, options),
    method: 'post',
})

completeClosingStep.form = completeClosingStepForm

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::lock
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:88
* @route '/cooperative/operator/closing/{period}/lock'
*/
export const lock = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lock.url(args, options),
    method: 'post',
})

lock.definition = {
    methods: ["post"],
    url: '/cooperative/operator/closing/{period}/lock',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::lock
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:88
* @route '/cooperative/operator/closing/{period}/lock'
*/
lock.url = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { period: args }
    }

    if (Array.isArray(args)) {
        args = {
            period: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        period: args.period,
    }

    return lock.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::lock
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:88
* @route '/cooperative/operator/closing/{period}/lock'
*/
lock.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lock.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::lock
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:88
* @route '/cooperative/operator/closing/{period}/lock'
*/
const lockForm = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lock.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::lock
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:88
* @route '/cooperative/operator/closing/{period}/lock'
*/
lockForm.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lock.url(args, options),
    method: 'post',
})

lock.form = lockForm

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::unlock
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:95
* @route '/cooperative/operator/closing/{period}/unlock'
*/
export const unlock = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unlock.url(args, options),
    method: 'post',
})

unlock.definition = {
    methods: ["post"],
    url: '/cooperative/operator/closing/{period}/unlock',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::unlock
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:95
* @route '/cooperative/operator/closing/{period}/unlock'
*/
unlock.url = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { period: args }
    }

    if (Array.isArray(args)) {
        args = {
            period: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        period: args.period,
    }

    return unlock.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::unlock
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:95
* @route '/cooperative/operator/closing/{period}/unlock'
*/
unlock.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unlock.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::unlock
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:95
* @route '/cooperative/operator/closing/{period}/unlock'
*/
const unlockForm = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlock.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::unlock
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:95
* @route '/cooperative/operator/closing/{period}/unlock'
*/
unlockForm.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlock.url(args, options),
    method: 'post',
})

unlock.form = unlockForm

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::reconcilePayment
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:104
* @route '/cooperative/operator/payments/{payment}/reconcile'
*/
export const reconcilePayment = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reconcilePayment.url(args, options),
    method: 'post',
})

reconcilePayment.definition = {
    methods: ["post"],
    url: '/cooperative/operator/payments/{payment}/reconcile',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::reconcilePayment
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:104
* @route '/cooperative/operator/payments/{payment}/reconcile'
*/
reconcilePayment.url = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return reconcilePayment.definition.url
            .replace('{payment}', parsedArgs.payment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::reconcilePayment
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:104
* @route '/cooperative/operator/payments/{payment}/reconcile'
*/
reconcilePayment.post = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reconcilePayment.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::reconcilePayment
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:104
* @route '/cooperative/operator/payments/{payment}/reconcile'
*/
const reconcilePaymentForm = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reconcilePayment.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::reconcilePayment
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:104
* @route '/cooperative/operator/payments/{payment}/reconcile'
*/
reconcilePaymentForm.post = (args: { payment: number | { id: number } } | [payment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reconcilePayment.url(args, options),
    method: 'post',
})

reconcilePayment.form = reconcilePaymentForm

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
export const exportMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})

exportMethod.definition = {
    methods: ["get","head"],
    url: '/cooperative/operator/export',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
exportMethod.url = (options?: RouteQueryOptions) => {
    return exportMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
exportMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
exportMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportMethod.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
const exportMethodForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
exportMethodForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\OperatorProcedureController::exportMethod
* @see app/Http/Controllers/Cooperative/OperatorProcedureController.php:113
* @route '/cooperative/operator/export'
*/
exportMethodForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

exportMethod.form = exportMethodForm

const OperatorProcedureController = { dashboard, approvalInbox, exceptions, analytics, closingPage, closing, completeClosingStep, lock, unlock, reconcilePayment, exportMethod, export: exportMethod }

export default OperatorProcedureController