import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\RewardController::index
* @see app/Http/Controllers/Cooperative/RewardController.php:16
* @route '/cooperative/rewards'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/rewards',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\RewardController::index
* @see app/Http/Controllers/Cooperative/RewardController.php:16
* @route '/cooperative/rewards'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\RewardController::index
* @see app/Http/Controllers/Cooperative/RewardController.php:16
* @route '/cooperative/rewards'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardController::index
* @see app/Http/Controllers/Cooperative/RewardController.php:16
* @route '/cooperative/rewards'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardController::index
* @see app/Http/Controllers/Cooperative/RewardController.php:16
* @route '/cooperative/rewards'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardController::index
* @see app/Http/Controllers/Cooperative/RewardController.php:16
* @route '/cooperative/rewards'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardController::index
* @see app/Http/Controllers/Cooperative/RewardController.php:16
* @route '/cooperative/rewards'
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
* @see \App\Http\Controllers\Cooperative\RewardController::store
* @see app/Http/Controllers/Cooperative/RewardController.php:36
* @route '/cooperative/rewards'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/rewards',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\RewardController::store
* @see app/Http/Controllers/Cooperative/RewardController.php:36
* @route '/cooperative/rewards'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\RewardController::store
* @see app/Http/Controllers/Cooperative/RewardController.php:36
* @route '/cooperative/rewards'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardController::store
* @see app/Http/Controllers/Cooperative/RewardController.php:36
* @route '/cooperative/rewards'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardController::store
* @see app/Http/Controllers/Cooperative/RewardController.php:36
* @route '/cooperative/rewards'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Cooperative\RewardController::update
* @see app/Http/Controllers/Cooperative/RewardController.php:47
* @route '/cooperative/rewards/{reward}'
*/
export const update = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/cooperative/rewards/{reward}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Cooperative\RewardController::update
* @see app/Http/Controllers/Cooperative/RewardController.php:47
* @route '/cooperative/rewards/{reward}'
*/
update.url = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reward: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { reward: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            reward: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reward: typeof args.reward === 'object'
        ? args.reward.id
        : args.reward,
    }

    return update.definition.url
            .replace('{reward}', parsedArgs.reward.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\RewardController::update
* @see app/Http/Controllers/Cooperative/RewardController.php:47
* @route '/cooperative/rewards/{reward}'
*/
update.put = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardController::update
* @see app/Http/Controllers/Cooperative/RewardController.php:47
* @route '/cooperative/rewards/{reward}'
*/
const updateForm = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardController::update
* @see app/Http/Controllers/Cooperative/RewardController.php:47
* @route '/cooperative/rewards/{reward}'
*/
updateForm.put = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\Cooperative\RewardController::destroy
* @see app/Http/Controllers/Cooperative/RewardController.php:54
* @route '/cooperative/rewards/{reward}'
*/
export const destroy = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/cooperative/rewards/{reward}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Cooperative\RewardController::destroy
* @see app/Http/Controllers/Cooperative/RewardController.php:54
* @route '/cooperative/rewards/{reward}'
*/
destroy.url = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { reward: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { reward: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            reward: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        reward: typeof args.reward === 'object'
        ? args.reward.id
        : args.reward,
    }

    return destroy.definition.url
            .replace('{reward}', parsedArgs.reward.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\RewardController::destroy
* @see app/Http/Controllers/Cooperative/RewardController.php:54
* @route '/cooperative/rewards/{reward}'
*/
destroy.delete = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardController::destroy
* @see app/Http/Controllers/Cooperative/RewardController.php:54
* @route '/cooperative/rewards/{reward}'
*/
const destroyForm = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardController::destroy
* @see app/Http/Controllers/Cooperative/RewardController.php:54
* @route '/cooperative/rewards/{reward}'
*/
destroyForm.delete = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const RewardController = { index, store, update, destroy }

export default RewardController