import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::index
* @see app/Http/Controllers/Api/V1/RewardApiController.php:18
* @route '/api/v1/rewards'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/rewards',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::index
* @see app/Http/Controllers/Api/V1/RewardApiController.php:18
* @route '/api/v1/rewards'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::index
* @see app/Http/Controllers/Api/V1/RewardApiController.php:18
* @route '/api/v1/rewards'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::index
* @see app/Http/Controllers/Api/V1/RewardApiController.php:18
* @route '/api/v1/rewards'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::index
* @see app/Http/Controllers/Api/V1/RewardApiController.php:18
* @route '/api/v1/rewards'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::index
* @see app/Http/Controllers/Api/V1/RewardApiController.php:18
* @route '/api/v1/rewards'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::index
* @see app/Http/Controllers/Api/V1/RewardApiController.php:18
* @route '/api/v1/rewards'
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
* @see \App\Http\Controllers\Api\V1\RewardApiController::redeem
* @see app/Http/Controllers/Api/V1/RewardApiController.php:35
* @route '/api/v1/rewards/{reward}/redeem'
*/
export const redeem = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redeem.url(args, options),
    method: 'post',
})

redeem.definition = {
    methods: ["post"],
    url: '/api/v1/rewards/{reward}/redeem',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::redeem
* @see app/Http/Controllers/Api/V1/RewardApiController.php:35
* @route '/api/v1/rewards/{reward}/redeem'
*/
redeem.url = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return redeem.definition.url
            .replace('{reward}', parsedArgs.reward.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::redeem
* @see app/Http/Controllers/Api/V1/RewardApiController.php:35
* @route '/api/v1/rewards/{reward}/redeem'
*/
redeem.post = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redeem.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::redeem
* @see app/Http/Controllers/Api/V1/RewardApiController.php:35
* @route '/api/v1/rewards/{reward}/redeem'
*/
const redeemForm = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redeem.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\RewardApiController::redeem
* @see app/Http/Controllers/Api/V1/RewardApiController.php:35
* @route '/api/v1/rewards/{reward}/redeem'
*/
redeemForm.post = (args: { reward: string | number | { id: string | number } } | [reward: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: redeem.url(args, options),
    method: 'post',
})

redeem.form = redeemForm

const RewardApiController = { index, redeem }

export default RewardApiController