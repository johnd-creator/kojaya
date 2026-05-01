import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import transactions from './transactions'
/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::index
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:18
* @route '/cooperative/pos'
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

const pos = {
    index: Object.assign(index, index),
    transactions: Object.assign(transactions, transactions),
}

export default pos