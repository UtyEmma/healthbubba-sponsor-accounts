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
