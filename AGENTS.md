<laravel-boost-guidelines>
=== .ai/laravel-inertia-react-refactoring-guide rules ===

# Laravel Inertia React UI Refactoring Guide

## Stack

Use the existing project stack and conventions:

- Laravel
- Inertia.js
- React with TypeScript
- Tailwind CSS
- shadcn/ui
- Laravel Boost

Inspect the existing routes, controllers, Inertia pages, layouts, components, theme, packages, and types before making changes.

## Objective

Refactor the interface to match the Figma design as closely as possible while keeping the implementation responsive, reusable, accessible, and maintainable.

Do not redesign the interface or change unrelated functionality.

## Layout

- Match the Figma spacing, alignment, typography, colours, borders, shadows, and proportions.
- Prefer standard Tailwind utilities such as `max-w-7xl`, `grid-cols-4`, `gap-6`, `px-6`, and `py-8`.
- Avoid arbitrary values unless required for visual accuracy.
- Avoid fixed widths and heights unless necessary.
- Prefer `w-full`, `max-w-*`, Grid, Flexbox, gaps, padding, margins, and aspect ratios.
- Build mobile-first and verify mobile, tablet, and desktop layouts.

## Theme

Configure reusable semantic tokens for:

- Primary and secondary colours
- Background and foreground colours
- Muted, accent, border, input, ring, and destructive colours
- Success, warning, and information states
- Repeated shadows and border radii

Use semantic utilities such as `bg-primary`, `text-muted-foreground`, and `shadow-card` instead of repeating raw values.

Configure **Inter** as the default application font.

## shadcn/ui

Use shadcn/ui components where appropriate.

Before creating a custom component:

1. Check `resources/js/components/ui`.
2. Reuse or extend an existing component.
3. Install the missing shadcn component when necessary.
4. Create a custom component only when no suitable shadcn primitive exists.

Use the project's configured package manager and install only required components.

Do not overwrite customised shadcn components without reviewing them first.

Use shadcn components for common UI patterns such as cards, buttons, inputs, selects, dialogs, dropdowns, tables, badges, tabs, sheets, tooltips, pagination, and skeletons.

## Cards

Use the shared shadcn card primitives for all card-based interfaces:

```tsx
<Card>
    <CardHeader>
        <CardTitle>Title</CardTitle>
        <CardDescription>Description</CardDescription>
    </CardHeader>

    <CardContent>Content</CardContent>

    <CardFooter>Actions</CardFooter>
</Card>
```

Feature-specific cards should compose these primitives instead of recreating card borders, padding, radii, backgrounds, or shadows.

## Page Structure

Organise pages by feature.

Each feature folder must contain:

- `index.tsx` for the main page
- Secondary pages as files such as `create.tsx`, `details.tsx`, `show.tsx`, or `edit.tsx`
- An optional `partials` folder for feature-specific components

Do not create separate folders for secondary pages.

```text
resources/js/pages/
  sponsors/
    index.tsx
    create.tsx
    details.tsx
    edit.tsx
    partials/
      sponsor-form.tsx
      sponsor-header.tsx
      sponsor-table.tsx
```

Place components shared across unrelated features in `resources/js/components`.

## Components

- Extract repeated sections into reusable components.
- Keep page components focused on composition.
- Keep feature-only components inside the feature's `partials` folder.
- Avoid duplicated markup, oversized components, unnecessary wrappers, and vague component names.
- Use semantic HTML and accessible interactions.

## Forms

Use Inertia's existing form pattern, normally `useForm`.

- Reuse the same form partial for create and edit pages.
- Use shadcn form controls.
- Display Laravel validation errors beside the relevant field.
- Include loading, disabled, success, and error states.
- Do not introduce React Hook Form unless the project already uses it or the form genuinely requires it.

## Laravel and Inertia

Laravel should handle:

- Routing
- Authorisation
- Validation
- Queries
- Pagination
- Filtering
- Sorting
- Business logic
- Data transformation

React should handle:

- Rendering
- Interaction
- Page composition
- Local UI state
- Responsive presentation

Use named routes and the project's route helper. Do not hard-code internal URLs.

## TypeScript

Use strict, reusable types for:

- Page props
- Models
- Forms
- Pagination
- Filters
- Component props
- Status values

Avoid `any`, duplicated types, and unnecessary assertions.

## Codex and Laravel Boost Workflow

Before editing:

1. Inspect the relevant files with Laravel Boost.
2. Review existing conventions and installed packages.
3. Identify reusable components and theme tokens.
4. Confirm the current Laravel-to-Inertia data flow.

During implementation:

1. Update theme tokens where required.
2. Install or improve shadcn primitives.
3. Create shared components.
4. Extract feature partials.
5. Refactor page files.
6. Preserve existing routes, props, and behaviour.
7. Remove obsolete duplicated code.

Do not modify unrelated parts of the application or add unnecessary packages.

## Verification

Run the repository's existing commands for:

- Formatting
- Type checking
- Frontend build

Also verify:

- Responsive layouts
- Figma accuracy
- Route references
- Imports
- Assets
- Validation errors
- Accessibility
- Browser console errors

Do not claim a check passed unless it was successfully executed.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/react (INERTIA_REACT) - v3
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
