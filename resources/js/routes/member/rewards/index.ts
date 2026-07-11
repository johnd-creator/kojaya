import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MemberPortalController::redeem
* @see app/Http/Controllers/MemberPortalController.php:478
* @route '/member/rewards/{reward}/redeem'
*/
export const redeem = (args: { reward: string | { id: string } } | [reward: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redeem.url(args, options),
    method: 'post',
})

redeem.definition = {
    methods: ["post"],
    url: '/member/rewards/{reward}/redeem',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MemberPortalController::redeem
* @see app/Http/Controllers/MemberPortalController.php:478
* @route '/member/rewards/{reward}/redeem'
*/
redeem.url = (args: { reward: string | { id: string } } | [reward: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions) => {
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
* @see \App\Http\Controllers\MemberPortalController::redeem
* @see app/Http/Controllers/MemberPortalController.php:478
* @route '/member/rewards/{reward}/redeem'
*/
redeem.post = (args: { reward: string | { id: string } } | [reward: string | { id: string } ] | string | { id: string }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: redeem.url(args, options),
    method: 'post',
})

const rewards = {
    redeem: Object.assign(redeem, redeem),
}

export default rewards