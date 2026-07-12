import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
import savings from './savings'
/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
export const components = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: components.url(options),
    method: 'get',
})

components.definition = {
    methods: ["get","head"],
    url: '/settings/components',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
components.url = (options?: RouteQueryOptions) => {
    return components.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
components.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: components.url(options),
    method: 'get',
})

/**
* @see \Inertia\Controller::__invoke
* @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
* @route '/settings/components'
*/
components.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: components.url(options),
    method: 'head',
})

const settings = {
    components: Object.assign(components, components),
    savings: Object.assign(savings, savings),
}

export default settings