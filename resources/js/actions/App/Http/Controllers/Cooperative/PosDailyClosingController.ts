import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::index
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:16
* @route '/cooperative/pos/closings'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/pos/closings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::index
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:16
* @route '/cooperative/pos/closings'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::index
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:16
* @route '/cooperative/pos/closings'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::index
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:16
* @route '/cooperative/pos/closings'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::index
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:16
* @route '/cooperative/pos/closings'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::index
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:16
* @route '/cooperative/pos/closings'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::index
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:16
* @route '/cooperative/pos/closings'
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
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::close
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:29
* @route '/cooperative/pos/closings'
*/
export const close = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(options),
    method: 'post',
})

close.definition = {
    methods: ["post"],
    url: '/cooperative/pos/closings',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::close
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:29
* @route '/cooperative/pos/closings'
*/
close.url = (options?: RouteQueryOptions) => {
    return close.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::close
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:29
* @route '/cooperative/pos/closings'
*/
close.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: close.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::close
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:29
* @route '/cooperative/pos/closings'
*/
const closeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\PosDailyClosingController::close
* @see app/Http/Controllers/Cooperative/PosDailyClosingController.php:29
* @route '/cooperative/pos/closings'
*/
closeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: close.url(options),
    method: 'post',
})

close.form = closeForm

const PosDailyClosingController = { index, close }

export default PosDailyClosingController