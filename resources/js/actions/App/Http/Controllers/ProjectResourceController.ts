import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectResourceController::index
* @see app/Http/Controllers/ProjectResourceController.php:14
* @route '/projects/{project}/resources'
*/
export const index = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/projects/{project}/resources',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProjectResourceController::index
* @see app/Http/Controllers/ProjectResourceController.php:14
* @route '/projects/{project}/resources'
*/
index.url = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return index.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectResourceController::index
* @see app/Http/Controllers/ProjectResourceController.php:14
* @route '/projects/{project}/resources'
*/
index.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectResourceController::index
* @see app/Http/Controllers/ProjectResourceController.php:14
* @route '/projects/{project}/resources'
*/
index.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProjectResourceController::index
* @see app/Http/Controllers/ProjectResourceController.php:14
* @route '/projects/{project}/resources'
*/
const indexForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectResourceController::index
* @see app/Http/Controllers/ProjectResourceController.php:14
* @route '/projects/{project}/resources'
*/
indexForm.get = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\ProjectResourceController::index
* @see app/Http/Controllers/ProjectResourceController.php:14
* @route '/projects/{project}/resources'
*/
indexForm.head = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\ProjectResourceController::storeAsset
* @see app/Http/Controllers/ProjectResourceController.php:34
* @route '/projects/{project}/resources/assets'
*/
export const storeAsset = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeAsset.url(args, options),
    method: 'post',
})

storeAsset.definition = {
    methods: ["post"],
    url: '/projects/{project}/resources/assets',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectResourceController::storeAsset
* @see app/Http/Controllers/ProjectResourceController.php:34
* @route '/projects/{project}/resources/assets'
*/
storeAsset.url = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { project: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { project: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            project: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
    }

    return storeAsset.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectResourceController::storeAsset
* @see app/Http/Controllers/ProjectResourceController.php:34
* @route '/projects/{project}/resources/assets'
*/
storeAsset.post = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeAsset.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectResourceController::storeAsset
* @see app/Http/Controllers/ProjectResourceController.php:34
* @route '/projects/{project}/resources/assets'
*/
const storeAssetForm = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeAsset.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectResourceController::storeAsset
* @see app/Http/Controllers/ProjectResourceController.php:34
* @route '/projects/{project}/resources/assets'
*/
storeAssetForm.post = (args: { project: string | number | { id: string | number } } | [project: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeAsset.url(args, options),
    method: 'post',
})

storeAsset.form = storeAssetForm

/**
* @see \App\Http\Controllers\ProjectResourceController::updateAsset
* @see app/Http/Controllers/ProjectResourceController.php:64
* @route '/projects/{project}/resources/assets/{allocation}'
*/
export const updateAsset = (args: { project: string | number | { id: string | number }, allocation: string | number | { id: string | number } } | [project: string | number | { id: string | number }, allocation: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateAsset.url(args, options),
    method: 'put',
})

updateAsset.definition = {
    methods: ["put"],
    url: '/projects/{project}/resources/assets/{allocation}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\ProjectResourceController::updateAsset
* @see app/Http/Controllers/ProjectResourceController.php:64
* @route '/projects/{project}/resources/assets/{allocation}'
*/
updateAsset.url = (args: { project: string | number | { id: string | number }, allocation: string | number | { id: string | number } } | [project: string | number | { id: string | number }, allocation: string | number | { id: string | number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            allocation: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
        allocation: typeof args.allocation === 'object'
        ? args.allocation.id
        : args.allocation,
    }

    return updateAsset.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{allocation}', parsedArgs.allocation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectResourceController::updateAsset
* @see app/Http/Controllers/ProjectResourceController.php:64
* @route '/projects/{project}/resources/assets/{allocation}'
*/
updateAsset.put = (args: { project: string | number | { id: string | number }, allocation: string | number | { id: string | number } } | [project: string | number | { id: string | number }, allocation: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateAsset.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\ProjectResourceController::updateAsset
* @see app/Http/Controllers/ProjectResourceController.php:64
* @route '/projects/{project}/resources/assets/{allocation}'
*/
const updateAssetForm = (args: { project: string | number | { id: string | number }, allocation: string | number | { id: string | number } } | [project: string | number | { id: string | number }, allocation: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateAsset.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectResourceController::updateAsset
* @see app/Http/Controllers/ProjectResourceController.php:64
* @route '/projects/{project}/resources/assets/{allocation}'
*/
updateAssetForm.put = (args: { project: string | number | { id: string | number }, allocation: string | number | { id: string | number } } | [project: string | number | { id: string | number }, allocation: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateAsset.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateAsset.form = updateAssetForm

/**
* @see \App\Http\Controllers\ProjectResourceController::destroyAsset
* @see app/Http/Controllers/ProjectResourceController.php:93
* @route '/projects/{project}/resources/assets/{allocation}'
*/
export const destroyAsset = (args: { project: string | number | { id: string | number }, allocation: string | number | { id: string | number } } | [project: string | number | { id: string | number }, allocation: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyAsset.url(args, options),
    method: 'delete',
})

destroyAsset.definition = {
    methods: ["delete"],
    url: '/projects/{project}/resources/assets/{allocation}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ProjectResourceController::destroyAsset
* @see app/Http/Controllers/ProjectResourceController.php:93
* @route '/projects/{project}/resources/assets/{allocation}'
*/
destroyAsset.url = (args: { project: string | number | { id: string | number }, allocation: string | number | { id: string | number } } | [project: string | number | { id: string | number }, allocation: string | number | { id: string | number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            allocation: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
        allocation: typeof args.allocation === 'object'
        ? args.allocation.id
        : args.allocation,
    }

    return destroyAsset.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{allocation}', parsedArgs.allocation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectResourceController::destroyAsset
* @see app/Http/Controllers/ProjectResourceController.php:93
* @route '/projects/{project}/resources/assets/{allocation}'
*/
destroyAsset.delete = (args: { project: string | number | { id: string | number }, allocation: string | number | { id: string | number } } | [project: string | number | { id: string | number }, allocation: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyAsset.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\ProjectResourceController::destroyAsset
* @see app/Http/Controllers/ProjectResourceController.php:93
* @route '/projects/{project}/resources/assets/{allocation}'
*/
const destroyAssetForm = (args: { project: string | number | { id: string | number }, allocation: string | number | { id: string | number } } | [project: string | number | { id: string | number }, allocation: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroyAsset.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProjectResourceController::destroyAsset
* @see app/Http/Controllers/ProjectResourceController.php:93
* @route '/projects/{project}/resources/assets/{allocation}'
*/
destroyAssetForm.delete = (args: { project: string | number | { id: string | number }, allocation: string | number | { id: string | number } } | [project: string | number | { id: string | number }, allocation: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroyAsset.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroyAsset.form = destroyAssetForm

const ProjectResourceController = { index, storeAsset, updateAsset, destroyAsset }

export default ProjectResourceController