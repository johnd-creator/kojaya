import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::index
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:14
* @route '/cooperative/loan-types'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/loan-types',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::index
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:14
* @route '/cooperative/loan-types'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::index
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:14
* @route '/cooperative/loan-types'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::index
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:14
* @route '/cooperative/loan-types'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::store
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:23
* @route '/cooperative/loan-types'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/loan-types',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::store
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:23
* @route '/cooperative/loan-types'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::store
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:23
* @route '/cooperative/loan-types'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::update
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:35
* @route '/cooperative/loan-types/{loan_type}'
*/
export const update = (args: { loan_type: string | number } | [loan_type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/cooperative/loan-types/{loan_type}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::update
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:35
* @route '/cooperative/loan-types/{loan_type}'
*/
update.url = (args: { loan_type: string | number } | [loan_type: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan_type: args }
    }

    if (Array.isArray(args)) {
        args = {
            loan_type: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan_type: args.loan_type,
    }

    return update.definition.url
            .replace('{loan_type}', parsedArgs.loan_type.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::update
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:35
* @route '/cooperative/loan-types/{loan_type}'
*/
update.put = (args: { loan_type: string | number } | [loan_type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::destroy
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:47
* @route '/cooperative/loan-types/{loan_type}'
*/
export const destroy = (args: { loan_type: string | number } | [loan_type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/cooperative/loan-types/{loan_type}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::destroy
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:47
* @route '/cooperative/loan-types/{loan_type}'
*/
destroy.url = (args: { loan_type: string | number } | [loan_type: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { loan_type: args }
    }

    if (Array.isArray(args)) {
        args = {
            loan_type: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        loan_type: args.loan_type,
    }

    return destroy.definition.url
            .replace('{loan_type}', parsedArgs.loan_type.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\LoanTypeController::destroy
* @see app/Http/Controllers/Cooperative/LoanTypeController.php:47
* @route '/cooperative/loan-types/{loan_type}'
*/
destroy.delete = (args: { loan_type: string | number } | [loan_type: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const loanTypes = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default loanTypes