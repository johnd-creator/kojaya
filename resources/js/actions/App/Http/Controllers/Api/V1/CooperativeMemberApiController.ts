import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::index
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:23
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
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:23
* @route '/api/v1/members'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::index
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:23
* @route '/api/v1/members'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::index
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:23
* @route '/api/v1/members'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::index
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:23
* @route '/api/v1/members'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::index
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:23
* @route '/api/v1/members'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::index
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:23
* @route '/api/v1/members'
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
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::store
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:45
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
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:45
* @route '/api/v1/members'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::store
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:45
* @route '/api/v1/members'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::store
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:45
* @route '/api/v1/members'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::store
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:45
* @route '/api/v1/members'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resignationRequests
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:158
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
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:158
* @route '/api/v1/members/resignation-requests'
*/
resignationRequests.url = (options?: RouteQueryOptions) => {
    return resignationRequests.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resignationRequests
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:158
* @route '/api/v1/members/resignation-requests'
*/
resignationRequests.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: resignationRequests.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resignationRequests
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:158
* @route '/api/v1/members/resignation-requests'
*/
resignationRequests.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: resignationRequests.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resignationRequests
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:158
* @route '/api/v1/members/resignation-requests'
*/
const resignationRequestsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: resignationRequests.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resignationRequests
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:158
* @route '/api/v1/members/resignation-requests'
*/
resignationRequestsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: resignationRequests.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resignationRequests
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:158
* @route '/api/v1/members/resignation-requests'
*/
resignationRequestsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: resignationRequests.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

resignationRequests.form = resignationRequestsForm

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::show
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:82
* @route '/api/v1/members/{member}'
*/
export const show = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/members/{member}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::show
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:82
* @route '/api/v1/members/{member}'
*/
show.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:82
* @route '/api/v1/members/{member}'
*/
show.get = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::show
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:82
* @route '/api/v1/members/{member}'
*/
show.head = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::show
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:82
* @route '/api/v1/members/{member}'
*/
const showForm = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::show
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:82
* @route '/api/v1/members/{member}'
*/
showForm.get = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::show
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:82
* @route '/api/v1/members/{member}'
*/
showForm.head = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::update
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:91
* @route '/api/v1/members/{member}'
*/
export const update = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/api/v1/members/{member}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::update
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:91
* @route '/api/v1/members/{member}'
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
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::update
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:91
* @route '/api/v1/members/{member}'
*/
update.put = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::update
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:91
* @route '/api/v1/members/{member}'
*/
const updateForm = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::update
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:91
* @route '/api/v1/members/{member}'
*/
updateForm.put = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::activate
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:122
* @route '/api/v1/members/{member}/activate'
*/
export const activate = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: activate.url(args, options),
    method: 'post',
})

activate.definition = {
    methods: ["post"],
    url: '/api/v1/members/{member}/activate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::activate
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:122
* @route '/api/v1/members/{member}/activate'
*/
activate.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:122
* @route '/api/v1/members/{member}/activate'
*/
activate.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: activate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::activate
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:122
* @route '/api/v1/members/{member}/activate'
*/
const activateForm = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: activate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::activate
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:122
* @route '/api/v1/members/{member}/activate'
*/
activateForm.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: activate.url(args, options),
    method: 'post',
})

activate.form = activateForm

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resign
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:149
* @route '/api/v1/members/{member}/resign'
*/
export const resign = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resign.url(args, options),
    method: 'post',
})

resign.definition = {
    methods: ["post"],
    url: '/api/v1/members/{member}/resign',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resign
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:149
* @route '/api/v1/members/{member}/resign'
*/
resign.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:149
* @route '/api/v1/members/{member}/resign'
*/
resign.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resign
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:149
* @route '/api/v1/members/{member}/resign'
*/
const resignForm = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::resign
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:149
* @route '/api/v1/members/{member}/resign'
*/
resignForm.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resign.url(args, options),
    method: 'post',
})

resign.form = resignForm

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::processResignationRequest
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:182
* @route '/api/v1/members/resignation-requests/{resignationRequest}/process'
*/
export const processResignationRequest = (args: { resignationRequest: number | { id: number } } | [resignationRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processResignationRequest.url(args, options),
    method: 'post',
})

processResignationRequest.definition = {
    methods: ["post"],
    url: '/api/v1/members/resignation-requests/{resignationRequest}/process',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::processResignationRequest
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:182
* @route '/api/v1/members/resignation-requests/{resignationRequest}/process'
*/
processResignationRequest.url = (args: { resignationRequest: number | { id: number } } | [resignationRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:182
* @route '/api/v1/members/resignation-requests/{resignationRequest}/process'
*/
processResignationRequest.post = (args: { resignationRequest: number | { id: number } } | [resignationRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processResignationRequest.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::processResignationRequest
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:182
* @route '/api/v1/members/resignation-requests/{resignationRequest}/process'
*/
const processResignationRequestForm = (args: { resignationRequest: number | { id: number } } | [resignationRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: processResignationRequest.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CooperativeMemberApiController::processResignationRequest
* @see app/Http/Controllers/Api/V1/CooperativeMemberApiController.php:182
* @route '/api/v1/members/resignation-requests/{resignationRequest}/process'
*/
processResignationRequestForm.post = (args: { resignationRequest: number | { id: number } } | [resignationRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: processResignationRequest.url(args, options),
    method: 'post',
})

processResignationRequest.form = processResignationRequestForm

const CooperativeMemberApiController = { index, store, resignationRequests, show, update, activate, resign, processResignationRequest }

export default CooperativeMemberApiController