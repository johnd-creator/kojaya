import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\EmployeeContractController::index
* @see app/Http/Controllers/EmployeeContractController.php:13
* @route '/employees/{employee}/contracts'
*/
export const index = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/employees/{employee}/contracts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EmployeeContractController::index
* @see app/Http/Controllers/EmployeeContractController.php:13
* @route '/employees/{employee}/contracts'
*/
index.url = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { employee: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { employee: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            employee: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employee: typeof args.employee === 'object'
        ? args.employee.id
        : args.employee,
    }

    return index.definition.url
            .replace('{employee}', parsedArgs.employee.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeContractController::index
* @see app/Http/Controllers/EmployeeContractController.php:13
* @route '/employees/{employee}/contracts'
*/
index.get = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeContractController::index
* @see app/Http/Controllers/EmployeeContractController.php:13
* @route '/employees/{employee}/contracts'
*/
index.head = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EmployeeContractController::index
* @see app/Http/Controllers/EmployeeContractController.php:13
* @route '/employees/{employee}/contracts'
*/
const indexForm = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeContractController::index
* @see app/Http/Controllers/EmployeeContractController.php:13
* @route '/employees/{employee}/contracts'
*/
indexForm.get = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EmployeeContractController::index
* @see app/Http/Controllers/EmployeeContractController.php:13
* @route '/employees/{employee}/contracts'
*/
indexForm.head = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\EmployeeContractController::store
* @see app/Http/Controllers/EmployeeContractController.php:25
* @route '/employees/{employee}/contracts'
*/
export const store = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/employees/{employee}/contracts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EmployeeContractController::store
* @see app/Http/Controllers/EmployeeContractController.php:25
* @route '/employees/{employee}/contracts'
*/
store.url = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { employee: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { employee: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            employee: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employee: typeof args.employee === 'object'
        ? args.employee.id
        : args.employee,
    }

    return store.definition.url
            .replace('{employee}', parsedArgs.employee.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeContractController::store
* @see app/Http/Controllers/EmployeeContractController.php:25
* @route '/employees/{employee}/contracts'
*/
store.post = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeContractController::store
* @see app/Http/Controllers/EmployeeContractController.php:25
* @route '/employees/{employee}/contracts'
*/
const storeForm = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeContractController::store
* @see app/Http/Controllers/EmployeeContractController.php:25
* @route '/employees/{employee}/contracts'
*/
storeForm.post = (args: { employee: number | { id: number } } | [employee: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\EmployeeContractController::update
* @see app/Http/Controllers/EmployeeContractController.php:40
* @route '/employees/{employee}/contracts/{contract}'
*/
export const update = (args: { employee: number | { id: number }, contract: number | { id: number } } | [employee: number | { id: number }, contract: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put","patch"],
    url: '/employees/{employee}/contracts/{contract}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\EmployeeContractController::update
* @see app/Http/Controllers/EmployeeContractController.php:40
* @route '/employees/{employee}/contracts/{contract}'
*/
update.url = (args: { employee: number | { id: number }, contract: number | { id: number } } | [employee: number | { id: number }, contract: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            employee: args[0],
            contract: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        employee: typeof args.employee === 'object'
        ? args.employee.id
        : args.employee,
        contract: typeof args.contract === 'object'
        ? args.contract.id
        : args.contract,
    }

    return update.definition.url
            .replace('{employee}', parsedArgs.employee.toString())
            .replace('{contract}', parsedArgs.contract.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmployeeContractController::update
* @see app/Http/Controllers/EmployeeContractController.php:40
* @route '/employees/{employee}/contracts/{contract}'
*/
update.put = (args: { employee: number | { id: number }, contract: number | { id: number } } | [employee: number | { id: number }, contract: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\EmployeeContractController::update
* @see app/Http/Controllers/EmployeeContractController.php:40
* @route '/employees/{employee}/contracts/{contract}'
*/
update.patch = (args: { employee: number | { id: number }, contract: number | { id: number } } | [employee: number | { id: number }, contract: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\EmployeeContractController::update
* @see app/Http/Controllers/EmployeeContractController.php:40
* @route '/employees/{employee}/contracts/{contract}'
*/
const updateForm = (args: { employee: number | { id: number }, contract: number | { id: number } } | [employee: number | { id: number }, contract: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeContractController::update
* @see app/Http/Controllers/EmployeeContractController.php:40
* @route '/employees/{employee}/contracts/{contract}'
*/
updateForm.put = (args: { employee: number | { id: number }, contract: number | { id: number } } | [employee: number | { id: number }, contract: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EmployeeContractController::update
* @see app/Http/Controllers/EmployeeContractController.php:40
* @route '/employees/{employee}/contracts/{contract}'
*/
updateForm.patch = (args: { employee: number | { id: number }, contract: number | { id: number } } | [employee: number | { id: number }, contract: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const contracts = {
    index: Object.assign(index, index),
    store: Object.assign(store, store),
    update: Object.assign(update, update),
}

export default contracts