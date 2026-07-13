import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::index
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:17
* @route '/cooperative/redemptions'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/redemptions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::index
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:17
* @route '/cooperative/redemptions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::index
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:17
* @route '/cooperative/redemptions'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::index
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:17
* @route '/cooperative/redemptions'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::index
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:17
* @route '/cooperative/redemptions'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::index
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:17
* @route '/cooperative/redemptions'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::index
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:17
* @route '/cooperative/redemptions'
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
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::show
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:34
* @route '/cooperative/redemptions/{redemption}'
*/
export const show = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cooperative/redemptions/{redemption}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::show
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:34
* @route '/cooperative/redemptions/{redemption}'
*/
show.url = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { redemption: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { redemption: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            redemption: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        redemption: typeof args.redemption === 'object'
        ? args.redemption.id
        : args.redemption,
    }

    return show.definition.url
            .replace('{redemption}', parsedArgs.redemption.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::show
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:34
* @route '/cooperative/redemptions/{redemption}'
*/
show.get = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::show
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:34
* @route '/cooperative/redemptions/{redemption}'
*/
show.head = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::show
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:34
* @route '/cooperative/redemptions/{redemption}'
*/
const showForm = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::show
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:34
* @route '/cooperative/redemptions/{redemption}'
*/
showForm.get = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::show
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:34
* @route '/cooperative/redemptions/{redemption}'
*/
showForm.head = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::updateStatus
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:45
* @route '/cooperative/redemptions/{redemption}/status'
*/
export const updateStatus = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(args, options),
    method: 'put',
})

updateStatus.definition = {
    methods: ["put"],
    url: '/cooperative/redemptions/{redemption}/status',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::updateStatus
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:45
* @route '/cooperative/redemptions/{redemption}/status'
*/
updateStatus.url = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { redemption: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { redemption: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            redemption: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        redemption: typeof args.redemption === 'object'
        ? args.redemption.id
        : args.redemption,
    }

    return updateStatus.definition.url
            .replace('{redemption}', parsedArgs.redemption.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::updateStatus
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:45
* @route '/cooperative/redemptions/{redemption}/status'
*/
updateStatus.put = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateStatus.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::updateStatus
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:45
* @route '/cooperative/redemptions/{redemption}/status'
*/
const updateStatusForm = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStatus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\RewardRedemptionController::updateStatus
* @see app/Http/Controllers/Cooperative/RewardRedemptionController.php:45
* @route '/cooperative/redemptions/{redemption}/status'
*/
updateStatusForm.put = (args: { redemption: string | number | { id: string | number } } | [redemption: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStatus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateStatus.form = updateStatusForm

const redemptions = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
    updateStatus: Object.assign(updateStatus, updateStatus),
}

export default redemptions