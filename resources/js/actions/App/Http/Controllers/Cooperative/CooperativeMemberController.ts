import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::exportMethod
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:242
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:242
* @route '/cooperative/members/export'
*/
exportMethod.url = (options?: RouteQueryOptions) => {
    return exportMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::exportMethod
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:242
* @route '/cooperative/members/export'
*/
exportMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::exportMethod
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:242
* @route '/cooperative/members/export'
*/
exportMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportMethod.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::exportMethod
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:242
* @route '/cooperative/members/export'
*/
const exportMethodForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::exportMethod
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:242
* @route '/cooperative/members/export'
*/
exportMethodForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::exportMethod
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:242
* @route '/cooperative/members/export'
*/
exportMethodForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: exportMethod.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

exportMethod.form = exportMethodForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::index
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:28
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:28
* @route '/cooperative/members'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::index
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:28
* @route '/cooperative/members'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::index
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:28
* @route '/cooperative/members'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::index
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:28
* @route '/cooperative/members'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::index
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:28
* @route '/cooperative/members'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::index
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:28
* @route '/cooperative/members'
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::create
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:89
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:89
* @route '/cooperative/members/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::create
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:89
* @route '/cooperative/members/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::create
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:89
* @route '/cooperative/members/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::create
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:89
* @route '/cooperative/members/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::create
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:89
* @route '/cooperative/members/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::create
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:89
* @route '/cooperative/members/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::store
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:100
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:100
* @route '/cooperative/members'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::store
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:100
* @route '/cooperative/members'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::store
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:100
* @route '/cooperative/members'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::store
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:100
* @route '/cooperative/members'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:124
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:124
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:124
* @route '/cooperative/members/{member}'
*/
show.get = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:124
* @route '/cooperative/members/{member}'
*/
show.head = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:124
* @route '/cooperative/members/{member}'
*/
const showForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:124
* @route '/cooperative/members/{member}'
*/
showForm.get = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::show
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:124
* @route '/cooperative/members/{member}'
*/
showForm.head = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:143
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:143
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:143
* @route '/cooperative/members/{member}/edit'
*/
edit.get = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:143
* @route '/cooperative/members/{member}/edit'
*/
edit.head = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:143
* @route '/cooperative/members/{member}/edit'
*/
const editForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:143
* @route '/cooperative/members/{member}/edit'
*/
editForm.get = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::edit
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:143
* @route '/cooperative/members/{member}/edit'
*/
editForm.head = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:158
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:158
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:158
* @route '/cooperative/members/{member}'
*/
update.put = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:158
* @route '/cooperative/members/{member}'
*/
update.patch = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:158
* @route '/cooperative/members/{member}'
*/
const updateForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:158
* @route '/cooperative/members/{member}'
*/
updateForm.put = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::update
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:158
* @route '/cooperative/members/{member}'
*/
updateForm.patch = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::destroy
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:232
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:232
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:232
* @route '/cooperative/members/{member}'
*/
destroy.delete = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::destroy
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:232
* @route '/cooperative/members/{member}'
*/
const destroyForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::destroy
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:232
* @route '/cooperative/members/{member}'
*/
destroyForm.delete = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::activate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:179
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:179
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:179
* @route '/cooperative/members/{member}/activate'
*/
activate.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: activate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::activate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:179
* @route '/cooperative/members/{member}/activate'
*/
const activateForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: activate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::activate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:179
* @route '/cooperative/members/{member}/activate'
*/
activateForm.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: activate.url(args, options),
    method: 'post',
})

activate.form = activateForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::deactivate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:207
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:207
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:207
* @route '/cooperative/members/{member}/deactivate'
*/
deactivate.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: deactivate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::deactivate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:207
* @route '/cooperative/members/{member}/deactivate'
*/
const deactivateForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deactivate.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::deactivate
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:207
* @route '/cooperative/members/{member}/deactivate'
*/
deactivateForm.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: deactivate.url(args, options),
    method: 'post',
})

deactivate.form = deactivateForm

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::resign
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:223
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:223
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
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:223
* @route '/cooperative/members/{member}/resign'
*/
resign.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::resign
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:223
* @route '/cooperative/members/{member}/resign'
*/
const resignForm = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resign.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cooperative\CooperativeMemberController::resign
* @see app/Http/Controllers/Cooperative/CooperativeMemberController.php:223
* @route '/cooperative/members/{member}/resign'
*/
resignForm.post = (args: { member: string | number | { id: string | number } } | [member: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resign.url(args, options),
    method: 'post',
})

resign.form = resignForm

const CooperativeMemberController = { exportMethod, index, create, store, show, edit, update, destroy, activate, deactivate, resign, export: exportMethod }

export default CooperativeMemberController