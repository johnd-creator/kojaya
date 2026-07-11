import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
import resignations from './resignations'
import sensitiveData from './sensitive-data'
import accountLink from './account-link'
import openingBalance from './opening-balance'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::exportMethod
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:391
* @route '/cooperative/members/export'
*/
export const exportMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})

exportMethod.definition = {
    methods: ["get","head"],
    url: '/cooperative/members/export',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::exportMethod
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:391
* @route '/cooperative/members/export'
*/
exportMethod.url = (options?: RouteQueryOptions) => {
    return exportMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::exportMethod
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:391
* @route '/cooperative/members/export'
*/
exportMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::exportMethod
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:391
* @route '/cooperative/members/export'
*/
exportMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportMethod.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::index
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:35
* @route '/cooperative/members'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cooperative/members',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::index
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:35
* @route '/cooperative/members'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::index
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:35
* @route '/cooperative/members'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::index
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:35
* @route '/cooperative/members'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::create
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:111
* @route '/cooperative/members/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cooperative/members/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::create
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:111
* @route '/cooperative/members/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::create
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:111
* @route '/cooperative/members/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::create
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:111
* @route '/cooperative/members/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::store
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:127
* @route '/cooperative/members'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cooperative/members',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::store
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:127
* @route '/cooperative/members'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::store
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:127
* @route '/cooperative/members'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:165
* @route '/cooperative/members/{member}'
*/
export const show = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cooperative/members/{member}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:165
* @route '/cooperative/members/{member}'
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:165
* @route '/cooperative/members/{member}'
*/
show.get = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:165
* @route '/cooperative/members/{member}'
*/
show.head = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:195
* @route '/cooperative/members/{member}/edit'
*/
export const edit = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/cooperative/members/{member}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:195
* @route '/cooperative/members/{member}/edit'
*/
edit.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return edit.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:195
* @route '/cooperative/members/{member}/edit'
*/
edit.get = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:195
* @route '/cooperative/members/{member}/edit'
*/
edit.head = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:219
* @route '/cooperative/members/{member}'
*/
export const update = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/cooperative/members/{member}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:219
* @route '/cooperative/members/{member}'
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:219
* @route '/cooperative/members/{member}'
*/
update.put = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:219
* @route '/cooperative/members/{member}'
*/
update.patch = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::destroy
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:379
* @route '/cooperative/members/{member}'
*/
export const destroy = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/cooperative/members/{member}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::destroy
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:379
* @route '/cooperative/members/{member}'
*/
destroy.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::destroy
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:379
* @route '/cooperative/members/{member}'
*/
destroy.delete = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::activate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:327
* @route '/cooperative/members/{member}/activate'
*/
export const activate = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: activate.url(args, options),
    method: 'post',
})

activate.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/activate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::activate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:327
* @route '/cooperative/members/{member}/activate'
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::activate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:327
* @route '/cooperative/members/{member}/activate'
*/
activate.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: activate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::deactivate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:357
* @route '/cooperative/members/{member}/deactivate'
*/
export const deactivate = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deactivate.url(args, options),
    method: 'post',
})

deactivate.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/deactivate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::deactivate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:357
* @route '/cooperative/members/{member}/deactivate'
*/
deactivate.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return deactivate.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::deactivate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:357
* @route '/cooperative/members/{member}/deactivate'
*/
deactivate.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deactivate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::resign
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:370
* @route '/cooperative/members/{member}/resign'
*/
export const resign = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resign.url(args, options),
    method: 'post',
})

resign.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/resign',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::resign
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:370
* @route '/cooperative/members/{member}/resign'
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::resign
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:370
* @route '/cooperative/members/{member}/resign'
*/
resign.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::validate
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:20
* @route '/cooperative/members/{member}/validate'
*/
export const validate = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validate.url(args, options),
    method: 'post',
})

validate.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/validate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::validate
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:20
* @route '/cooperative/members/{member}/validate'
*/
validate.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return validate.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::validate
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:20
* @route '/cooperative/members/{member}/validate'
*/
validate.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approveFinal
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:35
* @route '/cooperative/members/{member}/approve-final'
*/
export const approveFinal = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approveFinal.url(args, options),
    method: 'post',
})

approveFinal.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/approve-final',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approveFinal
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:35
* @route '/cooperative/members/{member}/approve-final'
*/
approveFinal.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return approveFinal.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::approveFinal
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:35
* @route '/cooperative/members/{member}/approve-final'
*/
approveFinal.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approveFinal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::requestRevision
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:50
* @route '/cooperative/members/{member}/request-revision'
*/
export const requestRevision = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestRevision.url(args, options),
    method: 'post',
})

requestRevision.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/request-revision',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::requestRevision
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:50
* @route '/cooperative/members/{member}/request-revision'
*/
requestRevision.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return requestRevision.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::requestRevision
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:50
* @route '/cooperative/members/{member}/request-revision'
*/
requestRevision.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestRevision.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::reject
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:63
* @route '/cooperative/members/{member}/reject'
*/
export const reject = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/cooperative/members/{member}/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::reject
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:63
* @route '/cooperative/members/{member}/reject'
*/
reject.url = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return reject.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberValidationController::reject
* @see app/Http/Controllers/Cooperative/CooperativeMemberValidationController.php:63
* @route '/cooperative/members/{member}/reject'
*/
reject.post = (args: { member: number | { id: number } } | [member: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(args, options),
    method: 'post',
})

const members = {
    export: Object.assign(exportMethod, exportMethod),
    resignations: Object.assign(resignations, resignations),
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    sensitiveData: Object.assign(sensitiveData, sensitiveData),
    accountLink: Object.assign(accountLink, accountLink),
    activate: Object.assign(activate, activate),
    deactivate: Object.assign(deactivate, deactivate),
    resign: Object.assign(resign, resign),
    validate: Object.assign(validate, validate),
    approveFinal: Object.assign(approveFinal, approveFinal),
    requestRevision: Object.assign(requestRevision, requestRevision),
    reject: Object.assign(reject, reject),
    openingBalance: Object.assign(openingBalance, openingBalance),
}

export default members