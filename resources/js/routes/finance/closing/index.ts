import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import steps from './steps'
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
* @see \App\Http\Controllers\Finance\FinanceClosingController::show
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
export const show = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/finance/closing/{period}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::show
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
show.url = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{period}', parsedArgs.period.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::show
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
show.get = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::show
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
show.head = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::show
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
const showForm = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::show
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
showForm.get = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Finance\FinanceClosingController::show
* @see app/Http/Controllers/Finance/FinanceClosingController.php:23
* @route '/finance/closing/{period}'
*/
showForm.head = (args: { period: string | number } | [period: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

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

const closing = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    steps: Object.assign(steps, steps),
    lock: Object.assign(lock, lock),
    unlock: Object.assign(unlock, unlock),
}

export default closing