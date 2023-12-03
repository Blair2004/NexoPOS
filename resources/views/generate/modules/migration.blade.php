@inject( 'SchemaService', 'App\Services\Schema' )<{{ '?php' }}
@inject( 'Str', 'Illuminate\Support\Str' )
/**
 * Table Migration
 * @package {{ config( 'nexopos.version' ) }}
**/

namespace Modules\{{ $module[ 'namespace' ] }}\Migrations;

use App\Classes\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        @if ( isset( $table, $schema ) )
            @php
            $SchemaService->renderSchema( compact( 'table', 'schema' ) )
            @endphp
        @else
        // ...
        @endif
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        @if ( isset( $table, $schema ) )
            @php
            $SchemaService->renderDrop( compact( 'table', 'schema' ) )
            @endphp
        @else
        // ...
        @endif
    }
};
