import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\FinanceBankController::index
* @see app/Http/Controllers/FinanceBankController.php:15
* @route '/finance/bank-batches'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/finance/bank-batches',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\FinanceBankController::index
* @see app/Http/Controllers/FinanceBankController.php:15
* @route '/finance/bank-batches'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\FinanceBankController::index
* @see app/Http/Controllers/FinanceBankController.php:15
* @route '/finance/bank-batches'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FinanceBankController::index
* @see app/Http/Controllers/FinanceBankController.php:15
* @route '/finance/bank-batches'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\FinanceBankController::index
* @see app/Http/Controllers/FinanceBankController.php:15
* @route '/finance/bank-batches'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FinanceBankController::index
* @see app/Http/Controllers/FinanceBankController.php:15
* @route '/finance/bank-batches'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FinanceBankController::index
* @see app/Http/Controllers/FinanceBankController.php:15
* @route '/finance/bank-batches'
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
* @see \App\Http\Controllers\FinanceBankController::store
* @see app/Http/Controllers/FinanceBankController.php:26
* @route '/finance/bank-batches'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/finance/bank-batches',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\FinanceBankController::store
* @see app/Http/Controllers/FinanceBankController.php:26
* @route '/finance/bank-batches'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\FinanceBankController::store
* @see app/Http/Controllers/FinanceBankController.php:26
* @route '/finance/bank-batches'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\FinanceBankController::store
* @see app/Http/Controllers/FinanceBankController.php:26
* @route '/finance/bank-batches'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\FinanceBankController::store
* @see app/Http/Controllers/FinanceBankController.php:26
* @route '/finance/bank-batches'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\FinanceBankController::exportMethod
* @see app/Http/Controllers/FinanceBankController.php:58
* @route '/finance/bank-batches/{batch}/export'
*/
export const exportMethod = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(args, options),
    method: 'get',
})

exportMethod.definition = {
    methods: ["get","head"],
    url: '/finance/bank-batches/{batch}/export',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\FinanceBankController::exportMethod
* @see app/Http/Controllers/FinanceBankController.php:58
* @route '/finance/bank-batches/{batch}/export'
*/
exportMethod.url = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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

    return exportMethod.definition.url
            .replace('{batch}', parsedArgs.batch.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\FinanceBankController::exportMethod
* @see app/Http/Controllers/FinanceBankController.php:58
* @route '/finance/bank-batches/{batch}/export'
*/
exportMethod.get = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FinanceBankController::exportMethod
* @see app/Http/Controllers/FinanceBankController.php:58
* @route '/finance/bank-batches/{batch}/export'
*/
exportMethod.head = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportMethod.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\FinanceBankController::exportMethod
* @see app/Http/Controllers/FinanceBankController.php:58
* @route '/finance/bank-batches/{batch}/export'
*/
const exportMethodForm = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FinanceBankController::exportMethod
* @see app/Http/Controllers/FinanceBankController.php:58
* @route '/finance/bank-batches/{batch}/export'
*/
exportMethodForm.get = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FinanceBankController::exportMethod
* @see app/Http/Controllers/FinanceBankController.php:58
* @route '/finance/bank-batches/{batch}/export'
*/
exportMethodForm.head = (args: { batch: string | { id: string } } | [batch: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

exportMethod.form = exportMethodForm

/**
* @see \App\Http\Controllers\FinanceBankController::reconcile
* @see app/Http/Controllers/FinanceBankController.php:72
* @route '/finance/bank-batches/reconcile'
*/
export const reconcile = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reconcile.url(options),
    method: 'post',
})

reconcile.definition = {
    methods: ["post"],
    url: '/finance/bank-batches/reconcile',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\FinanceBankController::reconcile
* @see app/Http/Controllers/FinanceBankController.php:72
* @route '/finance/bank-batches/reconcile'
*/
reconcile.url = (options?: RouteQueryOptions) => {
    return reconcile.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\FinanceBankController::reconcile
* @see app/Http/Controllers/FinanceBankController.php:72
* @route '/finance/bank-batches/reconcile'
*/
reconcile.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reconcile.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\FinanceBankController::reconcile
* @see app/Http/Controllers/FinanceBankController.php:72
* @route '/finance/bank-batches/reconcile'
*/
const reconcileForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reconcile.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\FinanceBankController::reconcile
* @see app/Http/Controllers/FinanceBankController.php:72
* @route '/finance/bank-batches/reconcile'
*/
reconcileForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reconcile.url(options),
    method: 'post',
})

reconcile.form = reconcileForm

const bankBatches = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    export: Object.assign(exportMethod, exportMethod),
    reconcile: Object.assign(reconcile, reconcile),
}

export default bankBatches