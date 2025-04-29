<?php
use App\Classes\Hook;
use App\Services\DateService;
use Illuminate\Support\Facades\Auth;

$dateService    =   app()->make( DateService::class );
?>
<script>
        /**
         * constant where is registered
         * global custom components
         * @param {Object}
         */
        window.nsExtraComponents     =   new Object;

        /**
         * describe a global NexoPOS object
         * @param {object} ns
         */
        window.ns   =   { nsExtraComponents };

        /**
         * store the server date
         * @param {string}
         */
        window.ns.date  =   {
            current : '{{ $dateService->toDateTimeString() }}',
            serverDate : '{{ $dateService->toDateTimeString() }}',
            timeZone: '{{ ns()->option->get( "ns_datetime_timezone", "Europe/London" ) }}',
            format: `{{ $dateService->convertFormatToMomment( ns()->option->get( 'ns_datetime_format', 'Y-m-d H:i:s' ) ) }}`
        }

        /**
         * Let's define the actual theme used
         */
        window.ns.theme     =   `{{ $theme }}`;

        /**
         * define the current language selected by the user or
         * the language that applies to the system by default.
         */
        window.ns.language      =   '{{ app()->getLocale() }}';
        window.ns.langFiles     =   <?php echo json_encode( Hook::filter( 'ns.langFiles', [
            'NexoPOS'   =>  asset( "/lang/" . app()->getLocale() . ".json" ),
        ]));?>

        @auth
        /**
         * We display only fillable values for the
         * logged user. The password might be displayed on an encrypted form.
         */
        window.ns.user              =   <?php echo json_encode( ns()->getUserDetails() );?>;
        window.ns.user.attributes   =   <?php echo json_encode( Auth::user()->attribute );?>;
        @endauth
        
        window.ns.cssFiles          =   <?php echo json_encode( ns()->simplifyManifest() );?>;

        /**
         * We'll store here the file mime types
         * that are supported by the media manager.
         */
        window.ns.medias            =   {
            mimes:  <?php echo json_encode( ns()->mediaService->getMimes() )?>,
            imageMimes: <?php echo json_encode( ns()->mediaService->getImageMimes() );?>
        }
    </script>