import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../../wayfinder'
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

const PosDailyClosingController = { index, close }

export default PosDailyClosingController