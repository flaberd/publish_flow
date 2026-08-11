# Project conventions

## Livewire components: no single-file components

This project uses `livewire/livewire` (^4.3), which supports single-file
components (SFC/MFC, the `⚡name.blade.php` files with a `new class extends
Component { ... }` PHP block inline in the Blade template). **Don't use that
style here.**

Always write Livewire components as two separate files:

- `app/Livewire/ComponentName.php` — the PHP component class.
- `resources/views/livewire/component-name.blade.php` — the Blade view only
  (no inline PHP class). Livewire resolves this automatically by convention
  when the class has no `render()` method.

Wire up page-level components in `routes/web.php` with
`Route::livewire('/uri', ComponentName::class)`.

`config/livewire.php` reflects this: `make_command.type` is `class` (not
`sfc`), so `php artisan make:livewire Foo` scaffolds the two-file form by
default.
