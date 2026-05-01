import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ProjectTaskController::updateProgress
* @see app/Http/Controllers/ProjectTaskController.php:142
* @route '/projects/{project}/tasks/{task}/progress'
*/
export const updateProgress = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateProgress.url(args, options),
    method: 'post',
})

updateProgress.definition = {
    methods: ["post"],
    url: '/projects/{project}/tasks/{task}/progress',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProjectTaskController::updateProgress
* @see app/Http/Controllers/ProjectTaskController.php:142
* @route '/projects/{project}/tasks/{task}/progress'
*/
updateProgress.url = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            project: args[0],
            task: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        project: typeof args.project === 'object'
        ? args.project.id
        : args.project,
        task: typeof args.task === 'object'
        ? args.task.id
        : args.task,
    }

    return updateProgress.definition.url
            .replace('{project}', parsedArgs.project.toString())
            .replace('{task}', parsedArgs.task.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProjectTaskController::updateProgress
* @see app/Http/Controllers/ProjectTaskController.php:142
* @route '/projects/{project}/tasks/{task}/progress'
*/
updateProgress.post = (args: { project: string | { id: string }, task: string | { id: string } } | [project: string | { id: string }, task: string | { id: string } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateProgress.url(args, options),
    method: 'post',
})

const tasks = {
    updateProgress: Object.assign(updateProgress, updateProgress),
}

export default tasks