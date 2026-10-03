# Read-only copies (shadows)

When a service needs another service's rows locally, for example to join, filter or sort on them,
it can keep a copy in its own database, kept up to date by events. Only the source service writes
the data.

```php
// billing: the source; the $shadowed fields are copied
final class Customer extends Model implements \Microservices\Contracts\Shadows\Shadowed
{
    use \Microservices\Traits\ShadowSource;

    protected array $shadowed = ['name'];
}

// foundation: the copy as billing describes it, for every service that keeps one
abstract class CustomerShadow extends \Microservices\Models\ShadowModel
{
    public static function owner(): string { return 'billing'; }

    public static function sourceTable(): string { return 'customers'; }
}

// orders: its copy, in the orders_customers table; it must live under app/, where copies are found
final class Customer extends \Foundation\Billing\Shadows\CustomerShadow {}
```

The keeper creates the copy's table with a migration extending
`Microservices\Migrations\ShadowMigration`, whose `table()` is `{keeper}_{source}`. The copy has
the source's key as its own, the shadowed columns and a `deleted_at`:

```php
return new class extends \Microservices\Migrations\ShadowMigration
{
    protected function source(): string { return 'customers'; }

    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->softDeletes();
        });
    }
};
```

```
billing: Customer saved / deleted ─► microservices.shadow.changed ─► orders consumer ─► CustomerShadow::sync()
```

A copy rejects any write that doesn't come from `sync()`, and a deleted source row becomes a soft
delete in the copy. Override `beforeSync()` to derive what the copy needs and the source never
announced. To fill a copy created after the source already had data:

```bash
php artisan microservices:shadows:want [--keepers=orders] [--sources=customers]   # on the keeper: ask the owners to send their rows again
php artisan microservices:shadows:announce customers [--keepers=orders]          # on the owner: send every row again
```

Both commands address the event through its `recipients`, so only those services update their
copy. A service in another language can be the source: see
[Services in other languages](other-languages.md#being-the-source-of-a-copy).
