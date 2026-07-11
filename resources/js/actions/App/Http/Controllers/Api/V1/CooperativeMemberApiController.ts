import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::index
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:33
* @route '/api/v1/members'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/members',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::index
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:33
* @route '/api/v1/members'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::index
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:33
* @route '/api/v1/members'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::index
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:33
* @route '/api/v1/members'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::store
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:58
* @route '/api/v1/members'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/v1/members',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::store
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:58
* @route '/api/v1/members'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::store
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:58
* @route '/api/v1/members'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resignationRequests
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:248
* @route '/api/v1/members/resignation-requests'
*/
export const resignationRequests = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: resignationRequests.url(options),
    method: 'get',
})

resignationRequests.definition = {
    methods: ["get","head"],
    url: '/api/v1/members/resignation-requests',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resignationRequests
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:248
* @route '/api/v1/members/resignation-requests'
*/
resignationRequests.url = (options?: RouteQueryOptions) => {
    return resignationRequests.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resignationRequests
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:248
* @route '/api/v1/members/resignation-requests'
*/
resignationRequests.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: resignationRequests.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resignationRequests
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:248
* @route '/api/v1/members/resignation-requests'
*/
resignationRequests.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: resignationRequests.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::show
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:101
* @route '/api/v1/members/{member}'
*/
export const show = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/members/{member}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::show
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:101
* @route '/api/v1/members/{member}'
*/
show.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::show
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:101
* @route '/api/v1/members/{member}'
*/
show.get = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::show
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:101
* @route '/api/v1/members/{member}'
*/
show.head = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::update
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:114
* @route '/api/v1/members/{member}'
*/
export const update = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/api/v1/members/{member}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::update
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:114
* @route '/api/v1/members/{member}'
*/
update.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::update
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:114
* @route '/api/v1/members/{member}'
*/
update.put = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::updateSensitiveData
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:155
* @route '/api/v1/members/{member}/sensitive-data'
*/
export const updateSensitiveData = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateSensitiveData.url(args, options),
    method: 'patch',
})

updateSensitiveData.definition = {
    methods: ["patch"],
    url: '/api/v1/members/{member}/sensitive-data',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::updateSensitiveData
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:155
* @route '/api/v1/members/{member}/sensitive-data'
*/
updateSensitiveData.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return updateSensitiveData.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::updateSensitiveData
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:155
* @route '/api/v1/members/{member}/sensitive-data'
*/
updateSensitiveData.patch = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateSensitiveData.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::linkAccount
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:180
* @route '/api/v1/members/{member}/account'
*/
export const linkAccount = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: linkAccount.url(args, options),
    method: 'patch',
})

linkAccount.definition = {
    methods: ["patch"],
    url: '/api/v1/members/{member}/account',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::linkAccount
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:180
* @route '/api/v1/members/{member}/account'
*/
linkAccount.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return linkAccount.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::linkAccount
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:180
* @route '/api/v1/members/{member}/account'
*/
linkAccount.patch = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: linkAccount.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::activate
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:210
* @route '/api/v1/members/{member}/activate'
*/
export const activate = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: activate.url(args, options),
    method: 'post',
})

activate.definition = {
    methods: ["post"],
    url: '/api/v1/members/{member}/activate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::activate
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:210
* @route '/api/v1/members/{member}/activate'
*/
activate.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return activate.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::activate
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:210
* @route '/api/v1/members/{member}/activate'
*/
activate.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: activate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resign
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:239
* @route '/api/v1/members/{member}/resign'
*/
export const resign = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resign.url(args, options),
    method: 'post',
})

resign.definition = {
    methods: ["post"],
    url: '/api/v1/members/{member}/resign',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resign
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:239
* @route '/api/v1/members/{member}/resign'
*/
resign.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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

    return resign.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resign
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:239
* @route '/api/v1/members/{member}/resign'
*/
resign.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::processResignationRequest
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:275
* @route '/api/v1/members/resignation-requests/{resignationRequest}/process'
*/
export const processResignationRequest = (args: { resignationRequest: string | number | { id: string | number } } | [resignationRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processResignationRequest.url(args, options),
    method: 'post',
})

processResignationRequest.definition = {
    methods: ["post"],
    url: '/api/v1/members/resignation-requests/{resignationRequest}/process',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::processResignationRequest
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:275
* @route '/api/v1/members/resignation-requests/{resignationRequest}/process'
*/
processResignationRequest.url = (args: { resignationRequest: string | number | { id: string | number } } | [resignationRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { resignationRequest: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { resignationRequest: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            resignationRequest: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        resignationRequest: typeof args.resignationRequest === 'object'
        ? args.resignationRequest.id
        : args.resignationRequest,
    }

    return processResignationRequest.definition.url
            .replace('{resignationRequest}', parsedArgs.resignationRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::processResignationRequest
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:275
* @route '/api/v1/members/resignation-requests/{resignationRequest}/process'
*/
processResignationRequest.post = (args: { resignationRequest: string | number | { id: string | number } } | [resignationRequest: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processResignationRequest.url(args, options),
    method: 'post',
})

const CooperativeMemberApiController = { index, store, resignationRequests, show, update, updateSensitiveData, linkAccount, activate, resign, processResignationRequest }

export default CooperativeMemberApiController