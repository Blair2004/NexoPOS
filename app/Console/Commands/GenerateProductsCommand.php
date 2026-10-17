<?php

namespace App\Console\Commands;

use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use App\Models\User;
use App\Services\ProductGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class GenerateProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:products:generate
                            {--count=10000 : Number of products to generate (1-100000)}
                            {--category=* : Limit generation to one or more category IDs}
                            {--unit-group= : Unit group ID to assign}
                            {--author= : User ID recorded as the product author}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate varied sample NexoPOS products with generic images';

    /**
     * Execute the console command.
     */
    public function handle( ProductGeneratorService $generator ): int
    {
        $count = filter_var( $this->option( 'count' ), FILTER_VALIDATE_INT );

        if ( $count === false || $count < 1 || $count > 100000 ) {
            $this->error( __( 'The count must be an integer between 1 and 100,000.' ) );

            return self::FAILURE;
        }

        $categories = $this->categories();
        if ( $categories === null ) {
            return self::FAILURE;
        }

        $unitGroup = $this->unitGroup();
        if ( ! $unitGroup instanceof UnitGroup ) {
            return self::FAILURE;
        }

        $author = $this->author();
        if ( ! $author instanceof User ) {
            return self::FAILURE;
        }

        $progressBar = $this->output->createProgressBar( $count );
        $progressBar->start();

        try {
            $generated = $generator->generate(
                count: $count,
                categories: $categories,
                unitGroup: $unitGroup,
                author: $author,
                taxGroup: TaxGroup::query()->orderBy( 'id' )->first(),
                afterProductCreated: static fn () => $progressBar->advance(),
            );
        } catch ( Throwable $throwable ) {
            $progressBar->finish();
            $this->newLine( 2 );
            $this->error( sprintf( __( 'Product generation stopped: %s' ), $throwable->getMessage() ) );

            return self::FAILURE;
        }

        $progressBar->finish();
        $this->newLine( 2 );
        $this->info( sprintf( __( '%s products were generated successfully.' ), number_format( $generated ) ) );

        return self::SUCCESS;
    }

    /** @return Collection<int, ProductCategory>|null */
    private function categories(): ?Collection
    {
        $categoryIds = collect( $this->option( 'category' ) )
            ->filter( static fn ( mixed $categoryId ): bool => $categoryId !== null && $categoryId !== '' )
            ->map( static fn ( mixed $categoryId ): int => (int) $categoryId )
            ->unique()
            ->values();

        $categories = ProductCategory::query()
            ->when( $categoryIds->isNotEmpty(), static fn ( Builder $query ): Builder => $query->whereIn( 'id', $categoryIds ) )
            ->orderBy( 'id' )
            ->get( [ 'id', 'name' ] );

        if ( $categories->isEmpty() ) {
            $this->error( __( 'No product categories were found. Create a category before running this command.' ) );

            return null;
        }

        if ( $categoryIds->isNotEmpty() && $categories->count() !== $categoryIds->count() ) {
            $this->error( __( 'One or more selected product categories do not exist.' ) );

            return null;
        }

        return $categories;
    }

    private function unitGroup(): ?UnitGroup
    {
        $query = UnitGroup::query()->with( 'units:id,group_id,value' )->orderBy( 'id' );
        $unitGroupId = $this->option( 'unit-group' );

        $unitGroup = $unitGroupId === null
            ? $query->whereHas( 'units' )->first()
            : $query->find( (int) $unitGroupId );

        if ( ! $unitGroup instanceof UnitGroup || $unitGroup->units->isEmpty() ) {
            $this->error( __( 'No usable unit group was found. Select a unit group containing at least one unit.' ) );

            return null;
        }

        return $unitGroup;
    }

    private function author(): ?User
    {
        $authorId = $this->option( 'author' );
        $author = $authorId === null
            ? Role::namespace( Role::ADMIN )?->users()->orderBy( 'id' )->first() ?? User::query()->orderBy( 'id' )->first()
            : User::query()->find( (int) $authorId );

        if ( ! $author instanceof User ) {
            $this->error( __( 'No product author was found. Create a user or provide a valid --author ID.' ) );

            return null;
        }

        return $author;
    }
}
