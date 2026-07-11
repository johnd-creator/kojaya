import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\PointController::index
* @see app/Http/Controllers/Cooperative/PointController.php:14
* @route '/cooperative/points'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/points',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\PointController::index
* @see app/Http/Controllers/Cooperative/PointController.php:14
* @route '/cooperative/points'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\PointController::index
* @see app/Http/Controllers/Cooperative/PointController.php:14
* @route '/cooperative/points'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\PointController::index
* @see app/Http/Controllers/Cooperative/PointController.php:14
* @route '/cooperative/points'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const points = {
    index: Object.assign(index, index),
}

export default points