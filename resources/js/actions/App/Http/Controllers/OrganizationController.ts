import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\OrganizationController::index
* @see app/Http/Controllers/OrganizationController.php:14
* @route '/organizations'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/organizations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrganizationController::index
* @see app/Http/Controllers/OrganizationController.php:14
* @route '/organizations'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationController::index
* @see app/Http/Controllers/OrganizationController.php:14
* @route '/organizations'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrganizationController::index
* @see app/Http/Controllers/OrganizationController.php:14
* @route '/organizations'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrganizationController::create
* @see app/Http/Controllers/OrganizationController.php:0
* @route '/organizations/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/organizations/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrganizationController::create
* @see app/Http/Controllers/OrganizationController.php:0
* @route '/organizations/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationController::create
* @see app/Http/Controllers/OrganizationController.php:0
* @route '/organizations/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrganizationController::create
* @see app/Http/Controllers/OrganizationController.php:0
* @route '/organizations/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrganizationController::store
* @see app/Http/Controllers/OrganizationController.php:29
* @route '/organizations'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/organizations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OrganizationController::store
* @see app/Http/Controllers/OrganizationController.php:29
* @route '/organizations'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationController::store
* @see app/Http/Controllers/OrganizationController.php:29
* @route '/organizations'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrganizationController::edit
* @see app/Http/Controllers/OrganizationController.php:0
* @route '/organizations/{organization}/edit'
*/
export const edit = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/organizations/{organization}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OrganizationController::edit
* @see app/Http/Controllers/OrganizationController.php:0
* @route '/organizations/{organization}/edit'
*/
edit.url = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { organization: args }
    }

    if (Array.isArray(args)) {
        args = {
            organization: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        organization: args.organization,
    }

    return edit.definition.url
            .replace('{organization}', parsedArgs.organization.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationController::edit
* @see app/Http/Controllers/OrganizationController.php:0
* @route '/organizations/{organization}/edit'
*/
edit.get = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OrganizationController::edit
* @see app/Http/Controllers/OrganizationController.php:0
* @route '/organizations/{organization}/edit'
*/
edit.head = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\OrganizationController::update
* @see app/Http/Controllers/OrganizationController.php:41
* @route '/organizations/{organization}'
*/
export const update = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/organizations/{organization}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\OrganizationController::update
* @see app/Http/Controllers/OrganizationController.php:41
* @route '/organizations/{organization}'
*/
update.url = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { organization: args }
    }

    if (Array.isArray(args)) {
        args = {
            organization: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        organization: args.organization,
    }

    return update.definition.url
            .replace('{organization}', parsedArgs.organization.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationController::update
* @see app/Http/Controllers/OrganizationController.php:41
* @route '/organizations/{organization}'
*/
update.put = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\OrganizationController::update
* @see app/Http/Controllers/OrganizationController.php:41
* @route '/organizations/{organization}'
*/
update.patch = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\OrganizationController::destroy
* @see app/Http/Controllers/OrganizationController.php:54
* @route '/organizations/{organization}'
*/
export const destroy = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/organizations/{organization}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\OrganizationController::destroy
* @see app/Http/Controllers/OrganizationController.php:54
* @route '/organizations/{organization}'
*/
destroy.url = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { organization: args }
    }

    if (Array.isArray(args)) {
        args = {
            organization: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        organization: args.organization,
    }

    return destroy.definition.url
            .replace('{organization}', parsedArgs.organization.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrganizationController::destroy
* @see app/Http/Controllers/OrganizationController.php:54
* @route '/organizations/{organization}'
*/
destroy.delete = (args: { organization: string | number } | [organization: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const OrganizationController = { index, create, store, edit, update, destroy }

export default OrganizationController