
## Model Events and Listeners Pattern

**CRITICAL**: NexoPOS follows a strict event-driven architecture for model lifecycle hooks. When models use the `$dispatchesEvents` property, **NEVER** add logic directly in model `boot()` methods using static event listeners like `static::deleting()`, `static::created()`, etc.

### ❌ WRONG Approach:
```php
class MyModel extends Model
{
    protected $dispatchesEvents = [
        'deleting' => MyModelBeforeDeletedEvent::class,
    ];

    protected static function boot()
    {
        parent::boot();
        
        // ❌ DO NOT DO THIS - bypasses event system
        static::deleting(function ($model) {
            // Delete related records...
        });
    }
}
```

### ✅ CORRECT Approach:
```php
// In the model - only define events
class MyModel extends Model
{
    protected $dispatchesEvents = [
        'deleting' => MyModelBeforeDeletedEvent::class,
        'deleted' => MyModelAfterDeletedEvent::class,
    ];
    
    // Relationships only
    public function relatedRecords()
    {
        return $this->hasMany(RelatedModel::class);
    }
}

// In the listener - delegate to services or jobs
class MyModelBeforeDeletedListener
{
    public function handle(MyModelBeforeDeletedEvent $event): void
    {
        $model = $event->myModel;
        
        // ✅ Delegate to service for business logic
        app(MyModelService::class)->handleDeletion($model);
        
        // OR
        
        // ✅ Dispatch job for async/long-running operations
        DeleteRelatedRecordsJob::dispatch($model->id);
    }
}

// In the service - implement business logic
class MyModelService
{
    public function handleDeletion(MyModel $model): void
    {
        // Business logic here
        $model->relatedRecords()->each(function ($record) {
            if ($record->file_path && file_exists($record->file_path)) {
                unlink($record->file_path);
            }
            $record->delete();
        });
    }
}

// OR in a job - for async operations
class DeleteRelatedRecordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $modelId) {}

    public function handle(): void
    {
        $model = MyModel::find($this->modelId);
        
        if ($model) {
            // Delete related records asynchronously
            $model->relatedRecords()->each(function ($record) {
                if ($record->file_path && file_exists($record->file_path)) {
                    unlink($record->file_path);
                }
                $record->delete();
            });
        }
    }
}
```

### Why This Pattern?

1. **Single Responsibility**: Listeners orchestrate, services/jobs execute
2. **Testability**: Business logic in services can be unit tested easily
3. **Reusability**: Services can be called from multiple places (controllers, commands, listeners)
4. **Performance**: Jobs enable async operations for time-consuming tasks
5. **Maintainability**: Clear separation between event handling and business logic
6. **Event Flow**: Respects NexoPOS event architecture

### Listener Responsibilities

Listeners should **ONLY**:
- ✅ Receive the event
- ✅ Extract data from the event
- ✅ Call a service method
- ✅ Dispatch a job
- ✅ Simple logging or notifications

Listeners should **NEVER**:
- ❌ Contain complex business logic
- ❌ Perform database queries directly (beyond simple finds)
- ❌ Execute file operations
- ❌ Make HTTP requests
- ❌ Transform or calculate data

### When to Use boot() Method

The `boot()` method should **ONLY** be used for:
- Setting up model traits
- Configuring global scopes
- Setting default attribute values

```php
protected static function boot()
{
    parent::boot();
    
    // ✅ OK: Adding global scope
    static::addGlobalScope('active', function (Builder $builder) {
        $builder->where('status', 'active');
    });
    
    // ✅ OK: Setting defaults
    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = Str::uuid();
        }
    });
}
```

### Implementation Flow

1. **Model** → Define events in `$dispatchesEvents`
2. **Listener** → Receive event, delegate to service/job
3. **Service** → Implement synchronous business logic
4. **Job** → Implement asynchronous operations

### When to Use Service vs Job

**Use Service when:**
- Operation is synchronous and must complete before continuing
- Logic needs to return a value
- Operation is quick (< 1 second)
- Must complete within the same request lifecycle

**Use Job when:**
- Operation is time-consuming (file operations, API calls)
- Can be processed asynchronously
- Doesn't need immediate feedback
- Handles large datasets
- Needs retry capability on failure

### Finding the Right Listener

1. Check the model's `$dispatchesEvents` property
2. Find the event class (e.g., `BackupBeforeDeletedEvent`)
3. Locate the corresponding listener (e.g., `BackupBeforeDeletedListener`)
4. In the listener, delegate to a service or dispatch a job