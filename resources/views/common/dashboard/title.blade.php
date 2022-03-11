<div class="page-inner-header mb-4">
    <h3 class="text-3xl text-primary font-bold">{!! $title ?? __( 'Unamed Page' ) !!}</h3>
    <p class="text-secondary">{{ $description ?? __( 'No description' ) }}</p>
</div>
@include( 'components.session-message' )