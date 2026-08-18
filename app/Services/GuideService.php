<?php

namespace App\Services;

use App\Models\User;
use App\Services\UserOptions;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class GuideService
{
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DISMISSED = 'dismissed';

    public const PERMISSION_ALL = 'all';
    public const PERMISSION_ANY = 'any';

    /**
     * Registered experiences for the current application lifecycle/request.
     *
     * @var array<string, array>
     */
    protected array $experiences = [];

    /**
     * UserOptions instance for the current logged-in user.
     */
    protected UserOptions $userOptions;

    /**
     * Allows NexoPOS to override the permission mechanism if necessary.
     */
    protected ?Closure $permissionChecker = null;

    public function __construct(
        
    ) {
        // ...
    }

    public function initForUser( User $user): self
    {
        $this->userOptions = new UserOptions( $user->id );

        return $this;
    }

    /**
     * Register a new unique experience.
     *
     * @throws LogicException
     * @throws InvalidArgumentException
     */
    public function register(string $identifier, array $definition): static
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            throw new InvalidArgumentException(
                'A guide experience must have an identifier.'
            );
        }

        if (isset($this->experiences[$identifier])) {
            throw new LogicException(
                sprintf(
                    'The guide experience [%s] has already been registered.',
                    $identifier
                )
            );
        }

        $this->experiences[$identifier] = $this->normalizeExperience(
            $identifier,
            $definition
        );

        return $this;
    }

    /**
     * Register multiple experiences.
     *
     * Expected format:
     *
     * [
     *     'products.introduction' => [...],
     *     'gastro.introduction' => [...],
     * ]
     */
    public function registerMany(array $experiences): static
    {
        foreach ($experiences as $identifier => $definition) {
            $this->register($identifier, $definition);
        }

        return $this;
    }

    /**
     * Determine whether an experience has been registered.
     */
    public function has(string $identifier): bool
    {
        return isset($this->experiences[$identifier]);
    }

    /**
     * Return a registered experience.
     */
    public function get(string $identifier): array
    {
        if (! $this->has($identifier)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The guide experience [%s] is not registered.',
                    $identifier
                )
            );
        }

        return $this->experiences[$identifier];
    }

    /**
     * Return all registered experiences.
     */
    public function all(): array
    {
        return array_values($this->experiences);
    }

    /**
     * Return experiences the logged-in user is allowed to access.
     */
    public function available( string $route = '', string $path = '' ): array
    {
        $available = array_values(
            array_filter(
                $this->experiences,
                fn (array $experience) => $this->hasRequiredPermissions(
                    $experience
                )
            )
        );

        return collect( $available )
            ->filter( fn( $experience ) => $this->checkRouteEligibility( $experience, $route, $path ) )
            ->all();
    }

    /**
     * Return experiences that has already been completed by the user.
     */
    public function completed(): array
    {
        return array_values(
            array_filter(
                $this->experiences,
                fn (array $experience) => $this->isCompleted(
                    $experience['id']
                )
            )
        );
    }

    public function checkRouteEligibility( array $experience, string $currentRoute = '', string $currentPath = '' )
    {
        if ( isset( $experience[ 'required_routes' ] ) && $experience[ 'required_routes' ] && $currentRoute !== '' ) {
            if ( is_array( $experience[ 'required_routes' ] ) && ! in_array( $currentRoute, $experience[ 'required_routes' ] ) ) {
                return false;
            }
        }

        if ( isset( $experience[ 'required_path' ] ) && $experience[ 'required_path' ] && $currentPath !== '' ) {

        }

        return true;
    }

    /**
     * Return experiences that has already been dismissed by the user.
     */
    public function dismissed(): array
    {
        return array_values(
            array_filter(
                $this->experiences,
                fn (array $experience) => $this->isDismissed(
                    $experience['id']
                )
            )
        );
    }

    /**
     * Return experiences that should currently be proposed to the user.
     *
     * Completed or dismissed experiences of the current version are excluded.
     */
    public function pending( string $route = '', string $path = '' ): array
    {
        return array_values(
            array_filter(
                $this->available($route),
                fn (array $experience) => $this->shouldOffer(
                    $experience['id']
                )
            )
        );
    }

    /**
     * Determine whether the current user may run an experience.
     */
    public function canRun(string $identifier): bool
    {
        $experience = $this->get($identifier);

        return $this->hasRequiredPermissions($experience);
    }

    /**
     * Determine whether NexoPOS should offer an experience to the user.
     *
     * A new experience version becomes available again automatically.
     */
    public function shouldOffer(string $identifier, string | null $route = null ): bool
    {
        $experience = $this->get($identifier);

        if (! $this->hasRequiredPermissions($experience)) {
            return false;
        }

        $state = $this->state($identifier);

        /**
         * The user's saved state belongs to an older version.
         *
         * The experience may therefore be shown again.
         */
        if ($state['version'] < $experience['version']) {
            return true;
        }

        return ! in_array(
            $state['status'],
            [
                self::STATUS_COMPLETED,
                self::STATUS_DISMISSED,
            ],
            true
        );
    }

    /**
     * Get the current user's state for an experience.
     */
    public function state(string $identifier): array
    {
        $experience = $this->get($identifier);

        $state = $this->userOptions->get(
            $this->optionKey($identifier),
            null
        );

        if (! is_array($state)) {
            return $this->defaultState($experience);
        }

        /**
         * Protect against hash/key collision or malformed data.
         */
        if (($state['experience'] ?? null) !== $identifier) {
            return $this->defaultState($experience);
        }

        return array_merge(
            $this->defaultState($experience),
            $state
        );
    }

    /**
     * Start or resume an experience.
     */
    public function start(string $identifier): array
    {
        $experience = $this->get($identifier);

        $this->assertUserCanRun($experience);

        $state = $this->state($identifier);

        /**
         * If this is a newer experience version, start fresh.
         */
        if ($state['version'] < $experience['version']) {
            $state = $this->defaultState($experience);
        }

        if ($state['status'] === self::STATUS_COMPLETED) {
            return $state;
        }

        if ($state['status'] === self::STATUS_DISMISSED) {
            /**
             * Explicit start means the user intentionally started it again.
             */
            $state['dismissed_at'] = null;
        }

        $state['status'] = self::STATUS_IN_PROGRESS;
        $state['version'] = $experience['version'];

        if (! $state['started_at']) {
            $state['started_at'] = now()->toISOString();
        }

        if (! $state['current_step']) {
            $state['current_step'] = $this->firstStepId($experience);
        }

        $this->saveState($identifier, $state);

        return $state;
    }

    /**
     * Mark a specific step as completed.
     *
     * If all steps are completed, the experience itself is completed.
     */
    public function completeStep(
        string $identifier,
        string $stepIdentifier
    ): array {
        $experience = $this->get($identifier);

        $this->assertUserCanRun($experience);

        if (! $this->stepExists($experience, $stepIdentifier)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The step [%s] does not exist in experience [%s].',
                    $stepIdentifier,
                    $identifier
                )
            );
        }

        $state = $this->state($identifier);

        /**
         * An action against an old guide version starts the current version.
         */
        if ($state['version'] < $experience['version']) {
            $state = $this->start($identifier);
        }

        if ($state['status'] !== self::STATUS_IN_PROGRESS) {
            $state = $this->start($identifier);
        }

        if (! in_array(
            $stepIdentifier,
            $state['completed_steps'],
            true
        )) {
            $state['completed_steps'][] = $stepIdentifier;
        }

        $nextStep = $this->nextIncompleteStep(
            $experience,
            $state['completed_steps']
        );

        if ($nextStep === null) {
            return $this->complete($identifier, $state);
        }

        $state['current_step'] = $nextStep;

        $this->saveState($identifier, $state);

        return $state;
    }

    /**
     * Skip a step.
     *
     * Useful for informational or optional guide steps.
     */
    public function skipStep(
        string $identifier,
        string $stepIdentifier
    ): array {
        $experience = $this->get($identifier);

        $this->assertUserCanRun($experience);

        $step = $this->findStep($experience, $stepIdentifier);

        if ($step === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'The step [%s] does not exist in experience [%s].',
                    $stepIdentifier,
                    $identifier
                )
            );
        }

        if (! ($step['skippable'] ?? true)) {
            throw new RuntimeException(
                sprintf(
                    'The step [%s] cannot be skipped.',
                    $stepIdentifier
                )
            );
        }

        $state = $this->state($identifier);

        if ($state['version'] < $experience['version']) {
            $state = $this->start($identifier);
        }

        if (! in_array(
            $stepIdentifier,
            $state['skipped_steps'],
            true
        )) {
            $state['skipped_steps'][] = $stepIdentifier;
        }

        $finishedSteps = array_unique([
            ...$state['completed_steps'],
            ...$state['skipped_steps'],
        ]);

        $nextStep = $this->nextIncompleteStep(
            $experience,
            $finishedSteps
        );

        if ($nextStep === null) {
            return $this->complete($identifier, $state);
        }

        $state['current_step'] = $nextStep;
        $state['status'] = self::STATUS_IN_PROGRESS;

        $this->saveState($identifier, $state);

        return $state;
    }

    /**
     * Set the user's current step.
     *
     * Useful when the frontend navigates backward or directly to a step.
     */
    public function setCurrentStep(
        string $identifier,
        string $stepIdentifier
    ): array {
        $experience = $this->get($identifier);

        $this->assertUserCanRun($experience);

        if (! $this->stepExists($experience, $stepIdentifier)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Unknown experience step [%s].',
                    $stepIdentifier
                )
            );
        }

        $state = $this->state($identifier);

        if ($state['version'] < $experience['version']) {
            $state = $this->start($identifier);
        }

        $state['status'] = self::STATUS_IN_PROGRESS;
        $state['current_step'] = $stepIdentifier;

        $this->saveState($identifier, $state);

        return $state;
    }

    /**
     * Complete an experience.
     */
    public function complete(
        string $identifier,
        ?array $state = null
    ): array {
        $experience = $this->get($identifier);

        $this->assertUserCanRun($experience);

        $state ??= $this->state($identifier);

        $state['version'] = $experience['version'];
        $state['status'] = self::STATUS_COMPLETED;
        $state['current_step'] = null;
        $state['completed_at'] = now()->toISOString();
        $state['dismissed_at'] = null;

        $this->saveState($identifier, $state);

        return $state;
    }

    /**
     * Dismiss an experience for its current version.
     *
     * It will not be automatically proposed again until its version changes.
     */
    public function dismiss(string $identifier): array
    {
        $experience = $this->get($identifier);

        $this->assertUserCanRun($experience);

        $state = $this->state($identifier);

        $state['version'] = $experience['version'];
        $state['status'] = self::STATUS_DISMISSED;
        $state['current_step'] = null;
        $state['dismissed_at'] = now()->toISOString();

        $this->saveState($identifier, $state);

        return $state;
    }

    /**
     * Completely reset an experience for the current user.
     */
    public function reset(string $identifier): void
    {
        $this->get($identifier);

        $this->userOptions->delete(
            $this->optionKey($identifier)
        );
    }

    /**
     * Restart an experience even if it was completed or dismissed.
     */
    public function restart(string $identifier): array
    {
        $experience = $this->get($identifier);

        $this->assertUserCanRun($experience);

        $state = $this->defaultState($experience);

        $state['status'] = self::STATUS_IN_PROGRESS;
        $state['started_at'] = now()->toISOString();
        $state['current_step'] = $this->firstStepId($experience);

        $this->saveState($identifier, $state);

        return $state;
    }

    /**
     * Return an experience together with its current user state.
     *
     * This is useful directly from an API controller.
     */
    public function payload(string $identifier): array
    {
        $experience = $this->get($identifier);

        $this->assertUserCanRun($experience);

        return [
            'experience' => $experience,
            'state' => $this->state($identifier),
            'progress' => $this->progress($identifier),
        ];
    }

    /**
     * Return all authorized experiences with their user states.
     */
    public function payloads(): array
    {
        return array_map(
            fn (array $experience) => $this->payload(
                $experience['id']
            ),
            $this->available()
        );
    }

    /**
     * Calculate the current user's progress.
     */
    public function progress(string $identifier): array
    {
        $experience = $this->get($identifier);
        $state = $this->state($identifier);

        $total = count($experience['steps']);

        $finished = count(
            array_unique([
                ...$state['completed_steps'],
                ...$state['skipped_steps'],
            ])
        );

        return [
            'total' => $total,
            'completed' => min($finished, $total),

            'percentage' => $total > 0
                ? (int) round(($finished / $total) * 100)
                : 100,
        ];
    }

    /**
     * Determine whether the current version has already been completed.
     */
    public function isCompleted(string $identifier): bool
    {
        $experience = $this->get($identifier);
        $state = $this->state($identifier);

        return $state['status'] === self::STATUS_COMPLETED
            && $state['version'] >= $experience['version'];
    }

    /**
     * Determine wether the current version has been dismissed.
     */
    public function isDismissed(string $identifier): bool
    {
        $experience = $this->get($identifier);
        $state = $this->state($identifier);

        return $state['status'] === self::STATUS_DISMISSED
            && $state['version'] >= $experience['version'];
    }

    /**
     * Override how permissions are checked.
     *
     * Callback:
     *
     * function ($user, string $permission): bool
     */
    public function resolvePermissionsUsing(Closure $callback): static
    {
        $this->permissionChecker = $callback;

        return $this;
    }

    protected function normalizeExperience(
        string $identifier,
        array $definition
    ): array {
        /**
         * Validate experience title.
         */
        if (empty($definition['title'])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Experience [%s] requires a title.',
                    $identifier
                )
            );
        }

        /**
         * Normalize permissions.
         *
         * Supports:
         *
         * 'permission' => 'manage.options'
         *
         * or:
         *
         * 'permissions' => [
         *     'manage.options',
         *     'create.products',
         * ]
         */
        $permissions = $definition['permissions']
            ?? $definition['permission']
            ?? null;

        if (is_string($permissions)) {
            $permissions = [$permissions];
        }

        if (
            ! is_array($permissions)
            || count(array_filter($permissions)) === 0
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Experience [%s] must require at least one permission.',
                    $identifier
                )
            );
        }

        /**
         * Validate permission mode.
         */
        $permissionMode = $definition['permission_mode']
            ?? self::PERMISSION_ALL;

        if (! in_array(
            $permissionMode,
            [
                self::PERMISSION_ALL,
                self::PERMISSION_ANY,
            ],
            true
        )) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid permission mode [%s].',
                    $permissionMode
                )
            );
        }

        /**
         * Retrieve Guide::steps().
         */
        $steps = $definition['steps'] ?? [];

        if (! is_array($steps) || count($steps) === 0) {
            throw new InvalidArgumentException(
                sprintf(
                    'Experience [%s] must contain at least one step.',
                    $identifier
                )
            );
        }

        $normalizedSteps = [];
        $stepIdentifiers = [];

        foreach ($steps as $index => $step) {
            /**
             * Guide::step() should return an array.
             */
            if (! is_array($step)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Step #%d of experience [%s] is invalid.',
                        $index,
                        $identifier
                    )
                );
            }

            /**
             * Validate the unique step ID.
             */
            $stepId = trim(
                (string) ($step['id'] ?? '')
            );

            if ($stepId === '') {
                throw new InvalidArgumentException(
                    sprintf(
                        'Step #%d of experience [%s] requires an ID.',
                        $index,
                        $identifier
                    )
                );
            }

            if (in_array($stepId, $stepIdentifiers, true)) {
                throw new LogicException(
                    sprintf(
                        'Duplicate step [%s] in experience [%s].',
                        $stepId,
                        $identifier
                    )
                );
            }

            /**
             * Guide::step() contract:
             *
             * [
             *     'id' => string,
             *     'element' => string,
             *     'popover' => array,
             * ]
             */
            $element = $step['element'] ?? null;

            if (
                ! is_string($element)
                || trim($element) === ''
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Step [%s] of experience [%s] requires a valid element.',
                        $stepId,
                        $identifier
                    )
                );
            }

            /**
             * Validate Guide::popover().
             */
            $popover = $step['popover'] ?? null;

            if (! is_array($popover)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Step [%s] of experience [%s] requires a valid popover.',
                        $stepId,
                        $identifier
                    )
                );
            }

            /**
             * Guide::popover() contract:
             *
             * [
             *     'title' => string,
             *     'description' => string,
             *     'side' => string,
             *     'align' => string,
             * ]
             */
            if (
                ! array_key_exists('title', $popover)
                || ! is_string($popover['title'])
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Popover for step [%s] of experience [%s] requires a valid title.',
                        $stepId,
                        $identifier
                    )
                );
            }

            if (
                ! array_key_exists('description', $popover)
                || ! is_string($popover['description'])
            ) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Popover for step [%s] of experience [%s] requires a valid description.',
                        $stepId,
                        $identifier
                    )
                );
            }

            /**
             * These defaults match App\Classes\Guide::popover().
             */
            $side = $popover['side'] ?? 'top';
            $align = $popover['align'] ?? 'center';

            if (! in_array(
                $side,
                [
                    'top',
                    'right',
                    'bottom',
                    'left',
                ],
                true
            )) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid popover side [%s] for step [%s] of experience [%s].',
                        $side,
                        $stepId,
                        $identifier
                    )
                );
            }

            if (! in_array(
                $align,
                [
                    'start',
                    'center',
                    'end',
                ],
                true
            )) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid popover alignment [%s] for step [%s] of experience [%s].',
                        $align,
                        $stepId,
                        $identifier
                    )
                );
            }

            $stepIdentifiers[] = $stepId;

            /**
             * IMPORTANT:
             *
             * Guide is the contract.
             *
             * We validate the structure but don't translate it into another
             * representation such as selector/position/etc.
             *
             * We also preserve any additional properties that may later be
             * introduced by Guide::step() or Guide::popover().
             */
            $normalizedPopover = $popover;

            $normalizedPopover['side'] = $side;
            $normalizedPopover['align'] = $align;

            $normalizedStep = $step;

            $normalizedStep['id'] = $stepId;
            $normalizedStep['element'] = trim($element);
            $normalizedStep['popover'] = $normalizedPopover;

            $normalizedSteps[] = $normalizedStep;
        }

        /**
         * Return the normalized experience.
         */
        return [
            'id' => $identifier,

            'title' => $definition['title'],

            'icon' => $definition['icon']
                ?? null,

            'icon_class' => $definition['icon_class']
                ?? 'las la-star',

            'description' => $definition['description']
                ?? null,

            'required_routes' => $definition['required_routes']
                ?? null,

            'required_path' => $definition[ 'required_path' ] ?? null,

            /**
             * Increasing the version makes the guide available again
             * for users who completed an older version.
             */
            'version' => max(
                1,
                (int) ($definition['version'] ?? 1)
            ),

            'permissions' => array_values(
                array_unique(
                    array_filter($permissions)
                )
            ),

            'permission_mode' => $permissionMode,

            /**
             * Optional module namespace.
             */
            'module' => $definition['module']
                ?? null,

            /**
             * How the experience is expected to be offered.
             */
            'trigger' => $definition['trigger']
                ?? 'manual',

            /**
             * Driver.js-compatible steps generated by Guide.
             */
            'steps' => $normalizedSteps,

            /**
             * Arbitrary experience-level information.
             */
            'meta' => $definition['meta']
                ?? [],
        ];
    }

    /**
     * Default state of an experience for a user.
     */
    protected function defaultState(array $experience): array
    {
        return [
            'experience' => $experience['id'],
            'version' => $experience['version'],
            'status' => self::STATUS_NOT_STARTED,

            'current_step' => null,

            'completed_steps' => [],
            'skipped_steps' => [],

            'started_at' => null,
            'completed_at' => null,
            'dismissed_at' => null,
        ];
    }

    /**
     * Save state using the current logged user's UserOptions.
     */
    protected function saveState(
        string $identifier,
        array $state
    ): void {
        $this->userOptions->set(
            $this->optionKey($identifier),
            $state,
            null
        );
    }

    /**
     * Generate a safe user-option key.
     *
     * The experience ID is stored inside the value as well.
     */
    protected function optionKey(string $identifier): string
    {
        return sprintf(
            'nexopos_guidance_%s',
            sha1($identifier)
        );
    }

    /**
     * Ensure the current user is authorized.
     */
    protected function assertUserCanRun(array $experience): void
    {
        if (! $this->hasRequiredPermissions($experience)) {
            throw new RuntimeException(
                sprintf(
                    'The current user cannot access guide experience [%s].',
                    $experience['id']
                )
            );
        }
    }

    /**
     * Check all/any required experience permissions.
     */
    protected function hasRequiredPermissions(
        array $experience
    ): bool {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $permissions = $experience['permissions'];

        if ($experience['permission_mode'] === self::PERMISSION_ANY) {
            foreach ($permissions as $permission) {
                if ($this->userCan($user, $permission)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($permissions as $permission) {
            if (! $this->userCan($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Actual permission resolver.
     *
     * This method is intentionally isolated so NexoPOS can use its own
     * permission system without modifying the rest of the service.
     */
    protected function userCan(
        Authenticatable $user,
        string $permission
    ): bool {
        if ($this->permissionChecker) {
            return (bool) call_user_func(
                $this->permissionChecker,
                $user,
                $permission
            );
        }

        return Gate::forUser($user)->check($permission);
    }

    protected function firstStepId(array $experience): ?string
    {
        return $experience['steps'][0]['id']
            ?? null;
    }

    protected function stepExists(
        array $experience,
        string $stepIdentifier
    ): bool {
        return $this->findStep(
            $experience,
            $stepIdentifier
        ) !== null;
    }

    protected function findStep(
        array $experience,
        string $stepIdentifier
    ): ?array {
        foreach ($experience['steps'] as $step) {
            if ($step['id'] === $stepIdentifier) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Return the next unfinished step.
     */
    protected function nextIncompleteStep(
        array $experience,
        array $finishedSteps
    ): ?string {
        foreach ($experience['steps'] as $step) {
            if (! in_array(
                $step['id'],
                $finishedSteps,
                true
            )) {
                return $step['id'];
            }
        }

        return null;
    }
}