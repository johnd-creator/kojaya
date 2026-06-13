import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BankReconciliationController::index
* @see app/Http/Controllers/BankReconciliationController.php:11
* @route '/finance/bank-reconciliation'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/finance/bank-reconciliation',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BankReconciliationController::index
* @see app/Http/Controllers/BankReconciliationController.php:11
* @route '/finance/bank-reconciliation'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BankReconciliationController::index
* @see app/Http/Controllers/BankReconciliationController.php:11
* @route '/finance/bank-reconciliation'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BankReconciliationController::index
* @see app/Http/Controllers/BankReconciliationController.php:11
* @route '/finance/bank-reconciliation'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BankReconciliationController::index
* @see app/Http/Controllers/BankReconciliationController.php:11
* @route '/finance/bank-reconciliation'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BankReconciliationController::index
* @see app/Http/Controllers/BankReconciliationController.php:11
* @route '/finance/bank-reconciliation'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BankReconciliationController::index
* @see app/Http/Controllers/BankReconciliationController.php:11
* @route '/finance/bank-reconciliation'
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
* @see \App\Http\Controllers\BankReconciliationController::show
* @see app/Http/Controllers/BankReconciliationController.php:24
* @route '/finance/bank-reconciliation/{batch}'
*/
export const show = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/finance/bank-reconciliation/{batch}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BankReconciliationController::show
* @see app/Http/Controllers/BankReconciliationController.php:24
* @route '/finance/bank-reconciliation/{batch}'
*/
show.url = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { batch: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { batch: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            batch: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        batch: typeof args.batch === 'object'
        ? args.batch.id
        : args.batch,
    }

    return show.definition.url
            .replace('{batch}', parsedArgs.batch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BankReconciliationController::show
* @see app/Http/Controllers/BankReconciliationController.php:24
* @route '/finance/bank-reconciliation/{batch}'
*/
show.get = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BankReconciliationController::show
* @see app/Http/Controllers/BankReconciliationController.php:24
* @route '/finance/bank-reconciliation/{batch}'
*/
show.head = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\BankReconciliationController::show
* @see app/Http/Controllers/BankReconciliationController.php:24
* @route '/finance/bank-reconciliation/{batch}'
*/
const showForm = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BankReconciliationController::show
* @see app/Http/Controllers/BankReconciliationController.php:24
* @route '/finance/bank-reconciliation/{batch}'
*/
showForm.get = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\BankReconciliationController::show
* @see app/Http/Controllers/BankReconciliationController.php:24
* @route '/finance/bank-reconciliation/{batch}'
*/
showForm.head = (args: { batch: string | number | { id: string | number } } | [batch: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const BankReconciliationController = { index, show }

export default BankReconciliationController