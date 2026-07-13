import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosAnnualShuController::index
* @see app/Http/Controllers/Cooperative/PosAnnualShuController.php:14
* @route '/cooperative/pos/shu'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/shu',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosAnnualShuController::index
* @see app/Http/Controllers/Cooperative/PosAnnualShuController.php:14
* @route '/cooperative/pos/shu'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosAnnualShuController::index
* @see app/Http/Controllers/Cooperative/PosAnnualShuController.php:14
* @route '/cooperative/pos/shu'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosAnnualShuController::index
* @see app/Http/Controllers/Cooperative/PosAnnualShuController.php:14
* @route '/cooperative/pos/shu'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosAnnualShuController::index
* @see app/Http/Controllers/Cooperative/PosAnnualShuController.php:14
* @route '/cooperative/pos/shu'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosAnnualShuController::index
* @see app/Http/Controllers/Cooperative/PosAnnualShuController.php:14
* @route '/cooperative/pos/shu'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosAnnualShuController::index
* @see app/Http/Controllers/Cooperative/PosAnnualShuController.php:14
* @route '/cooperative/pos/shu'
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

const shu = {
    index: Object.assign(index, index),
}

export default shu