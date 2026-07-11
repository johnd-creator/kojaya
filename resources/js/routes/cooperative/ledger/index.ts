import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:21
* @route '/cooperative/ledger'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/ledger',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:21
* @route '/cooperative/ledger'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:21
* @route '/cooperative/ledger'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::index
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:21
* @route '/cooperative/ledger'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::cancelPayment
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:60
* @route '/cooperative/ledger/{entry}/cancel-payment'
*/
export const cancelPayment = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancelPayment.url(args, options),
    method: 'post',
})

cancelPayment.definition = {
    methods: ["post"],
    url: '/cooperative/ledger/{entry}/cancel-payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::cancelPayment
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:60
* @route '/cooperative/ledger/{entry}/cancel-payment'
*/
cancelPayment.url = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entry: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { entry: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            entry: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        entry: typeof args.entry === 'object'
        ? args.entry.id
        : args.entry,
    }

    return cancelPayment.definition.url
            .replace('{entry}', parsedArgs.entry.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::cancelPayment
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:60
* @route '/cooperative/ledger/{entry}/cancel-payment'
*/
cancelPayment.post = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancelPayment.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::revisePayment
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:67
* @route '/cooperative/ledger/{entry}/revise-payment'
*/
export const revisePayment = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: revisePayment.url(args, options),
    method: 'post',
})

revisePayment.definition = {
    methods: ["post"],
    url: '/cooperative/ledger/{entry}/revise-payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::revisePayment
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:67
* @route '/cooperative/ledger/{entry}/revise-payment'
*/
revisePayment.url = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entry: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { entry: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            entry: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        entry: typeof args.entry === 'object'
        ? args.entry.id
        : args.entry,
    }

    return revisePayment.definition.url
            .replace('{entry}', parsedArgs.entry.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeLedgerController::revisePayment
* @see app/Http/Controllers/Cooperative/CooperativeLedgerController.php:67
* @route '/cooperative/ledger/{entry}/revise-payment'
*/
revisePayment.post = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: revisePayment.url(args, options),
    method: 'post',
})

const ledger = {
    index: Object.assign(index, index),
    cancelPayment: Object.assign(cancelPayment, cancelPayment),
    revisePayment: Object.assign(revisePayment, revisePayment),
}

export default ledger