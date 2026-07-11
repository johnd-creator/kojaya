import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
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
export const show = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:165
* @route '/cooperative/members/{member}'
*/
show.get = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:165
* @route '/cooperative/members/{member}'
*/
show.head = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:195
* @route '/cooperative/members/{member}/edit'
*/
export const edit = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
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
edit.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
edit.get = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:195
* @route '/cooperative/members/{member}/edit'
*/
edit.head = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:219
* @route '/cooperative/members/{member}'
*/
export const update = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:219
* @route '/cooperative/members/{member}'
*/
update.put = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:219
* @route '/cooperative/members/{member}'
*/
update.patch = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::destroy
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:379
* @route '/cooperative/members/{member}'
*/
export const destroy = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
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
destroy.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
destroy.delete = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::updateSensitiveData
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:257
* @route '/cooperative/members/{member}/sensitive-data'
*/
export const updateSensitiveData = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateSensitiveData.url(args, options),
    method: 'patch',
})

updateSensitiveData.definition = {
    methods: ["patch"],
    url: '/cooperative/members/{member}/sensitive-data',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::updateSensitiveData
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:257
* @route '/cooperative/members/{member}/sensitive-data'
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::updateSensitiveData
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:257
* @route '/cooperative/members/{member}/sensitive-data'
*/
updateSensitiveData.patch = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateSensitiveData.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::linkAccount
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:277
* @route '/cooperative/members/{member}/account'
*/
export const linkAccount = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: linkAccount.url(args, options),
    method: 'patch',
})

linkAccount.definition = {
    methods: ["patch"],
    url: '/cooperative/members/{member}/account',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::linkAccount
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:277
* @route '/cooperative/members/{member}/account'
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::linkAccount
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:277
* @route '/cooperative/members/{member}/account'
*/
linkAccount.patch = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: linkAccount.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::activate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:327
* @route '/cooperative/members/{member}/activate'
*/
export const activate = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::activate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:327
* @route '/cooperative/members/{member}/activate'
*/
activate.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: activate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::deactivate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:357
* @route '/cooperative/members/{member}/deactivate'
*/
export const deactivate = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
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
deactivate.url = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
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
deactivate.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deactivate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::resign
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:370
* @route '/cooperative/members/{member}/resign'
*/
export const resign = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::resign
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:370
* @route '/cooperative/members/{member}/resign'
*/
resign.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resign.url(args, options),
    method: 'post',
})

const CooperativeMemberController = { exportMethod, index, create, store, show, edit, update, destroy, updateSensitiveData, linkAccount, activate, deactivate, resign, export: exportMethod }

export default CooperativeMemberController