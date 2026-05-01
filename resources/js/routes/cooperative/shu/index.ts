import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:16
* @route '/cooperative/shu'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/shu',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:16
* @route '/cooperative/shu'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:16
* @route '/cooperative/shu'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:16
* @route '/cooperative/shu'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:16
* @route '/cooperative/shu'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:16
* @route '/cooperative/shu'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::index
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:16
* @route '/cooperative/shu'
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
* @see \App\Http\Controllers\Cooperative\AnnualShuController::close
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:36
* @route '/cooperative/shu/close'
*/
export const close = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(options),
    method: 'post',
})

close.definition = {
    methods: ["post"],
    url: '/cooperative/shu/close',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::close
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:36
* @route '/cooperative/shu/close'
*/
close.url = (options?: RouteQueryOptions) => {
    return close.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::close
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:36
* @route '/cooperative/shu/close'
*/
close.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::close
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:36
* @route '/cooperative/shu/close'
*/
const closeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\AnnualShuController::close
* @see app/Http/Controllers/Cooperative/AnnualShuController.php:36
* @route '/cooperative/shu/close'
*/
closeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(options),
    method: 'post',
})

close.form = closeForm

const shu = {
    index: Object.assign(index, index),
    close: Object.assign(close, close),
}

export default shu