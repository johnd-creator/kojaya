import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SwitchOrganizationController::switchMethod
* @see app/Http/Controllers/SwitchOrganizationController.php:17
* @route '/switch-organization'
*/
export const switchMethod = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: switchMethod.url(options),
    method: 'post',
})

switchMethod.definition = {
    methods: ["post"],
    url: '/switch-organization',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SwitchOrganizationController::switchMethod
* @see app/Http/Controllers/SwitchOrganizationController.php:17
* @route '/switch-organization'
*/
switchMethod.url = (options?: RouteQueryOptions) => {
    return switchMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SwitchOrganizationController::switchMethod
* @see app/Http/Controllers/SwitchOrganizationController.php:17
* @route '/switch-organization'
*/
switchMethod.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: switchMethod.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SwitchOrganizationController::switchMethod
* @see app/Http/Controllers/SwitchOrganizationController.php:17
* @route '/switch-organization'
*/
const switchMethodForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: switchMethod.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SwitchOrganizationController::switchMethod
* @see app/Http/Controllers/SwitchOrganizationController.php:17
* @route '/switch-organization'
*/
switchMethodForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: switchMethod.url(options),
    method: 'post',
})

switchMethod.form = switchMethodForm

const SwitchOrganizationController = { switchMethod, switch: switchMethod }

export default SwitchOrganizationController