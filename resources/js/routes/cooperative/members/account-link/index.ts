import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:277
* @route '/cooperative/members/{member}/account'
*/
export const update = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/cooperative/members/{member}/account',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:277
* @route '/cooperative/members/{member}/account'
*/
update.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { member: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { member: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            member: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        member: typeof args.member === 'object'
        ? args.member.id
        : args.member,
    }

    return update.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:277
* @route '/cooperative/members/{member}/account'
*/
update.patch = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

const accountLink = {
    update: Object.assign(update, update),
}

export default accountLink