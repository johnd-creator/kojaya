import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
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

const transactions = {
    store: Object.assign(store, store),
}

export default transactions