import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
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

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::store
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:27
* @route '/cooperative/pos/transactions'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/pos/transactions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::store
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:27
* @route '/cooperative/pos/transactions'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::store
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:27
* @route '/cooperative/pos/transactions'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::store
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:27
* @route '/cooperative/pos/transactions'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosRegisterController::store
* @see app/Http/Controllers/Cooperative/PosRegisterController.php:27
* @route '/cooperative/pos/transactions'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const PosRegisterController = { index, store }

export default PosRegisterController