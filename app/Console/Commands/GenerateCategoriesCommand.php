<?php

namespace App\Console\Commands;

use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\User;
use App\Services\ProductCategoryGeneratorService;
use Illuminate\Console\Command;
use Throwable;

class GenerateCategoriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:categories:generate
                            {--count=10000 : Number of categories to generate (1-100000)}
                            {--parent= : Existing category ID assigned as the parent}
                            {--author= : User ID recorded as the category author}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate varied sample NexoPOS categories with generic images';

    /**
     * Execute the console command.
     */
    public function handle( ProductCategoryGeneratorService $generator ): int
    {
        $count = filter_var( $this->option( 'count' ), FILTER_VALIDATE_INT );

        if ( $count === false || $count < 1 || $count > 100000 ) {
            $this->error( __( 'The count must be an integer between 1 and 100,000.' ) );

            return self::FAILURE;
        }

        $parent = $this->parentCategory();
        if ( $parent === false ) {
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
                author: $author,
                parent: $parent,
                afterCategoryCreated: static fn () => $progressBar->advance(),
            );
        } catch ( Throwable $throwable ) {
            $progressBar->finish();
            $this->newLine( 2 );
            $this->error( sprintf( __( 'Category generation stopped: %s' ), $throwable->getMessage() ) );

            return self::FAILURE;
        }

        $progressBar->finish();
        $this->newLine( 2 );
        $this->info( sprintf( __( '%s categories were generated successfully.' ), number_format( $generated ) ) );

        return self::SUCCESS;
    }

    private function parentCategory(): ProductCategory|false|null
    {
        $parentId = $this->option( 'parent' );

        if ( $parentId === null ) {
            return null;
        }

        $parent = ProductCategory::query()->find( (int) $parentId );
        if ( ! $parent instanceof ProductCategory ) {
            $this->error( __( 'The selected parent category does not exist.' ) );

            return false;
        }

        return $parent;
    }

    private function author(): ?User
    {
        $authorId = $this->option( 'author' );
        $author = $authorId === null
            ? Role::namespace( Role::ADMIN )?->users()->orderBy( 'id' )->first() ?? User::query()->orderBy( 'id' )->first()
            : User::query()->find( (int) $authorId );

        if ( ! $author instanceof User ) {
            $this->error( __( 'No category author was found. Create a user or provide a valid --author ID.' ) );

            return null;
        }

        return $author;
    }
}
