import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Settings\SavingsController::edit
* @see app/Http/Controllers/Settings/SavingsController.php:14
* @route '/settings/savings'
*/
export const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/settings/savings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Settings\SavingsController::edit
* @see app/Http/Controllers/Settings/SavingsController.php:14
* @route '/settings/savings'
*/
edit.url = (options?: RouteQueryOptions) => {
    return edit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\SavingsController::edit
* @see app/Http/Controllers/Settings/SavingsController.php:14
* @route '/settings/savings'
*/
edit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Settings\SavingsController::edit
* @see app/Http/Controllers/Settings/SavingsController.php:14
* @route '/settings/savings'
*/
edit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Settings\SavingsController::update
* @see app/Http/Controllers/Settings/SavingsController.php:26
* @route '/settings/savings'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/settings/savings',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Settings\SavingsController::update
* @see app/Http/Controllers/Settings/SavingsController.php:26
* @route '/settings/savings'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Settings\SavingsController::update
* @see app/Http/Controllers/Settings/SavingsController.php:26
* @route '/settings/savings'
*/
update.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

const savings = {
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
}

export default savings