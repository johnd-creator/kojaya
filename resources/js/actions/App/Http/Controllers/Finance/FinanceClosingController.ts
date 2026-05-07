import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::index
* @see app/Http/Controllers/Finance/FinanceClosingController.php:18
* @route '/finance/closing'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/finance/closing',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::index
* @see app/Http/Controllers/Finance/FinanceClosingController.php:18
* @route '/finance/closing'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::index
* @see app/Http/Controllers/Finance/FinanceClosingController.php:18
* @route '/finance/closing'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::index
* @see app/Http/Controllers/Finance/FinanceClosingController.php:18
* @route '/finance/closing'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::index
* @see app/Http/Controllers/Finance/FinanceClosingController.php:18
* @route '/finance/closing'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::index
* @see app/Http/Controllers/Finance/FinanceClosingController.php:18
* @route '/finance/closing'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::index
* @see app/Http/Controllers/Finance/FinanceClosingController.php:18
* @route '/finance/closing'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::closing
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
export const closing = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: closing.url(args, options),
    method: 'get',
})

closing.definition = {
    methods: ["get","head"],
    url: '/finance/closing/{period}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::closing
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
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
* @see \App\Http\Controllers\Finance\FinanceClosingController::closing
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
closing.get = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: closing.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::closing
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
closing.head = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: closing.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::closing
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
const closingForm = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: closing.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::closing
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
closingForm.get = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: closing.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::closing
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
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
* @see \App\Http\Controllers\Finance\FinanceClosingController::completeClosingStep
* @see app/Http/Controllers/Finance/FinanceClosingController.php:44
* @route '/finance/closing/{period}/steps'
*/
export const completeClosingStep = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: completeClosingStep.url(args, options),
    method: 'post',
})

completeClosingStep.definition = {
    methods: ["post"],
    url: '/finance/closing/{period}/steps',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::completeClosingStep
* @see app/Http/Controllers/Finance/FinanceClosingController.php:44
* @route '/finance/closing/{period}/steps'
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
* @see \App\Http\Controllers\Finance\FinanceClosingController::completeClosingStep
* @see app/Http/Controllers/Finance/FinanceClosingController.php:44
* @route '/finance/closing/{period}/steps'
*/
completeClosingStep.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: completeClosingStep.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::completeClosingStep
* @see app/Http/Controllers/Finance/FinanceClosingController.php:44
* @route '/finance/closing/{period}/steps'
*/
const completeClosingStepForm = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: completeClosingStep.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::completeClosingStep
* @see app/Http/Controllers/Finance/FinanceClosingController.php:44
* @route '/finance/closing/{period}/steps'
*/
completeClosingStepForm.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: completeClosingStep.url(args, options),
    method: 'post',
})

completeClosingStep.form = completeClosingStepForm

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::lock
* @see app/Http/Controllers/Finance/FinanceClosingController.php:53
* @route '/finance/closing/{period}/lock'
*/
export const lock = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lock.url(args, options),
    method: 'post',
})

lock.definition = {
    methods: ["post"],
    url: '/finance/closing/{period}/lock',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::lock
* @see app/Http/Controllers/Finance/FinanceClosingController.php:53
* @route '/finance/closing/{period}/lock'
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
* @see \App\Http\Controllers\Finance\FinanceClosingController::lock
* @see app/Http/Controllers/Finance/FinanceClosingController.php:53
* @route '/finance/closing/{period}/lock'
*/
lock.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: lock.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::lock
* @see app/Http/Controllers/Finance/FinanceClosingController.php:53
* @route '/finance/closing/{period}/lock'
*/
const lockForm = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lock.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::lock
* @see app/Http/Controllers/Finance/FinanceClosingController.php:53
* @route '/finance/closing/{period}/lock'
*/
lockForm.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: lock.url(args, options),
    method: 'post',
})

lock.form = lockForm

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::unlock
* @see app/Http/Controllers/Finance/FinanceClosingController.php:60
* @route '/finance/closing/{period}/unlock'
*/
export const unlock = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unlock.url(args, options),
    method: 'post',
})

unlock.definition = {
    methods: ["post"],
    url: '/finance/closing/{period}/unlock',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::unlock
* @see app/Http/Controllers/Finance/FinanceClosingController.php:60
* @route '/finance/closing/{period}/unlock'
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
* @see \App\Http\Controllers\Finance\FinanceClosingController::unlock
* @see app/Http/Controllers/Finance/FinanceClosingController.php:60
* @route '/finance/closing/{period}/unlock'
*/
unlock.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unlock.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::unlock
* @see app/Http/Controllers/Finance/FinanceClosingController.php:60
* @route '/finance/closing/{period}/unlock'
*/
const unlockForm = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlock.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::unlock
* @see app/Http/Controllers/Finance/FinanceClosingController.php:60
* @route '/finance/closing/{period}/unlock'
*/
unlockForm.post = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unlock.url(args, options),
    method: 'post',
})

unlock.form = unlockForm

const FinanceClosingController = { index, closing, completeClosingStep, lock, unlock }

export default FinanceClosingController